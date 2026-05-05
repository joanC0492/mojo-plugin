<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once __DIR__ . '/../../../services/PropertyService.php';

class PropertiesTable extends WP_List_Table
{

    private $items_data;
    private $property_service;

    public function __construct()
    {
        $this->property_service = new PropertyService();

        parent::__construct([
            'singular' => 'Property',
            'plural'   => 'Properties',
            'ajax'     => false,
        ]);
    }

    public function get_columns()
    {
        return [
            'cb'       => '<input type="checkbox" />',
            'name'      => 'Name',
            'code'    => 'Ref.',
            'shares'    => 'Qty Shares',
            'owners'    => 'Owners',
        ];
    }

    public function get_bulk_actions()
    {
        return [
            'activate'   => 'Mark as Active',
            'deactivate' => 'Mark as Inactive',
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
        return sprintf('<input type="checkbox" name="property[]" value="%s" />', esc_attr($id));
    }

    public function column_name($item)
    {
        $edit_url = admin_url('admin.php?page=properties-admin-edit&id=' . $item['id']);

        // URL para eliminar esta fila vía acción masiva simulada
        $delete_url = wp_nonce_url(
            add_query_arg([
                'page'       => 'properties-admin',
                'action'     => 'delete',
                'property[]' => (int)$item['id'],
            ], admin_url('admin.php')),
            'bulk-' . $this->_args['plural']   // <<< dinámico
        );

        $actions = [
            'edit' => sprintf('<a href="%s">Edit</a>', esc_url($edit_url)),
            'delete' => sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'Are you completely sure you want to delete property ' . $item['name'] . '? This action will delete the property, the calendars associated with it and the dates already booked.\');">Delete</a>', esc_url($delete_url)),
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
        $per_page     = $this->get_items_per_page('properties_per_page', 20);
        $current_page = $this->get_pagenum();
        $page_number  = max(0, $current_page - 1); // repo usa base 0

        // Totales y datos de la página
        $total_items  = $this->property_service->countProperties($status);
        $this->items  = $this->property_service->getAllPropertiesPaginated($status, $per_page, $page_number);

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

        // Nonce del link/bulk (bulk- + plural)
        if (in_array($action, ['activate', 'deactivate', 'delete'], true)) {
            check_admin_referer('bulk-' . $this->_args['plural']);  // <<< dinámico
        }

        // ✅ IDs desde GET/POST
        $ids_raw = $_REQUEST['property'] ?? [];
        $property_ids = is_array($ids_raw) ? $ids_raw : [$ids_raw];
        $property_ids = array_filter(array_map('intval', $property_ids));

        if (empty($property_ids)) {
            add_settings_error('properties_notices', 'no_selection', 'Please select at least one property.', 'error');
            return;
        }

        $deleted = 0;
        $blocked = [];

        foreach ($property_ids as $id) {
            if ($id <= 0) {
                add_settings_error('properties_notices', 'invalid_' . $id, 'Invalid ID.', 'error');
                continue;
            }

            if ($action === 'activate') {
                $this->property_service->setActiveStatus($id, 1);
            } elseif ($action === 'deactivate') {
                $this->property_service->setActiveStatus($id, 0);
            } elseif ($action === 'delete') {
                // ✅ borrar SIEMPRE en cascada (sin bloqueos)
                if ($this->property_service->deletePropertyCascade($id)) {
                    $deleted++;
                } else {
                    add_settings_error('properties_notices', 'delete_fail_' . $id, 'Failed to delete property.', 'error');
                }
            }
        }

        if ($deleted) {
            add_settings_error('properties_notices', 'deleted_ok', sprintf('%d propertie(s) deleted successfully.', $deleted), 'updated');
        }
    }
}
