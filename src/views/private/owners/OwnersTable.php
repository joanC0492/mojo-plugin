<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once __DIR__ . '/../../../services/OwnerService.php';

class OwnersTable extends WP_List_Table
{

    private $items_data;
    private $owner_service;

    public function __construct()
    {
        $this->owner_service = new OwnerService();

        parent::__construct([
            'singular' => 'Owner',
            'plural'   => 'Owners',
            'ajax'     => false,
        ]);
    }

    public function get_columns()
    {
        return [
            'cb'       => '<input type="checkbox" />',
            'name'      => 'Name',
            'email'    => 'Email',
            'password'    => 'Password',
            'phone'    => 'Phone',
            'properties'    => 'Properties',
        ];
    }

    public function get_bulk_actions()
    {
        return [
            'activate'   => 'Mark as Active',
            'deactivate' => 'Mark as Inactive',
            'send_email' => 'Send Credentials',
            'delete'     => 'Delete',
        ];
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name] ?? '';
    }

    public function column_cb($item)
    {
        $id = isset($item['id']) ? $item['id'] : '';
        return sprintf('<input type="checkbox" name="owner[]" value="%s" />', esc_attr($id));
    }

    public function column_name($item)
    {
        $edit_url = admin_url('admin.php?page=owners-admin-edit&id=' . $item['id']);

        // URL para eliminar esta fila vía acción masiva simulada
        $delete_url = wp_nonce_url(
            add_query_arg([
                'page'   => 'owners-admin',
                'action' => 'delete',
                'owner[]' => (int)$item['id'],     // WP_List_Table espera array
            ], admin_url('admin.php')),
            'bulk-Owners'                           // nonce: 'bulk-' . $this->_args['plural']
        );

        $actions = [
            'edit' => sprintf('<a href="%s">Edit</a>', esc_url($edit_url)),
            'delete' => sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'Delete ' . $item['name'] . ' as owner?\');">Delete</a>', esc_url($delete_url)),
        ];

        if ($item['status'] == 0) {
            $col_name = '<strong><a class="row-title" href="' . esc_url($edit_url) . '">' . esc_html($item['name']) . '</a> — <span class="post-state">Inactive</span></strong>';
        } else {
            $col_name = '<strong><a class="row-title" href="' . esc_url($edit_url) . '">' . esc_html($item['name']) . '</a></strong>';
        }

        return sprintf('%1$s %2$s', $col_name, $this->row_actions($actions));
    }

    public function prepare_items()
    {
        if ($this->current_action()) {
            $this->process_bulk_action();
        }

        // Filtro por estado
        $status = null;
        if (isset($_GET['post_status'])) {
            $status = ($_GET['post_status'] === 'active') ? 1 : 0;
        }

        // Columnas
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Paginación desde Screen Options
        $per_page     = $this->get_items_per_page('owners_per_page', 20);
        $current_page = $this->get_pagenum();
        $page_number  = max(0, $current_page - 1); // repo usa base 0

        // Totales y página actual desde BD (NO array_slice)
        $total_items  = $this->owner_service->countOwners($status);
        $this->items  = $this->owner_service->getAllOwnersPaginated($status, $per_page, $page_number);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => max(1, (int)ceil($total_items / $per_page)),
        ]);
    }

    private function process_bulk_action()
    {
        $action = $this->current_action();
        if (!$action) return;

        // valida nonce (tu link usa 'bulk-Owners')
        if (in_array($action, ['activate', 'deactivate', 'send_email', 'delete'], true)) {
            check_admin_referer('bulk-Owners');
        }

        // ✅ soporta GET (link de fila) y POST (bulk)
        $owner_ids_raw = $_REQUEST['owner'] ?? [];
        $owner_ids = is_array($owner_ids_raw) ? $owner_ids_raw : [$owner_ids_raw];
        $owner_ids = array_filter(array_map('intval', $owner_ids));

        if (empty($owner_ids)) {
            add_settings_error('owners_notices', 'no_selection', 'Please select at least one owner.', 'error');
            return;
        }

        $deleted = 0;
        $blocked = [];

        foreach ($owner_ids as $id) {
            if ($id <= 0) {
                add_settings_error('owners_notices', 'invalid_id_' . $id, 'Invalid ID.', 'error');
                continue;
            }

            if ($action === 'activate') {
                $this->owner_service->setActiveStatus($id, 1);
            } elseif ($action === 'deactivate') {
                $this->owner_service->setActiveStatus($id, 0);
            } elseif ($action === 'send_email') {
                $this->owner_service->sendEmailForOwner($id);
            } elseif ($action === 'delete') {
                if ($this->owner_service->isLinkedToProperty($id)) {
                    $owner = $this->owner_service->getOwner($id, false); // array
                    $name  = $owner['name'] ?? ('ID ' . $id);
                    add_settings_error('owners_notices', 'linked_' . $id, "The owner '{$name}' cannot be deleted while linked to one or more properties", 'error');
                    echo '<div class="notice notice-error"><p>The owner ' . $name . ' cannot be deleted while linked to one or more properties</p></div>';
                } else {
                    if ($this->owner_service->deleteOwner($id)) {
                        echo '<div class="notice notice-error"><p>Owner deleted</p></div>';
                        $deleted++;
                    } else {
                        add_settings_error('owners_notices', 'delete_fail_' . $id, 'Failed to delete owner.', 'error');
                        echo '<div class="notice notice-error"><p>Failed to delete owner</p></div>';
                    }
                }
            }
        }

        if ($deleted) {
            add_settings_error('owners_notices', 'deleted_ok', sprintf('%d owner(s) deleted successfully.', $deleted), 'updated');
        }
    }
}
