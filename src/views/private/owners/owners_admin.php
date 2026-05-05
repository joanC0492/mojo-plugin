<?php

require_once 'OwnersTable.php';
require_once __DIR__ . '../../../../services/OwnerService.php';

add_action('admin_menu', 'owner_admin_menu');

function owner_admin_menu()
{
    // Guarda el hook suffix
    $hook = add_menu_page(
        'Owners Management',
        'Owners',
        'manage_options',
        'owners-admin',
        'owners_admin_page',
        'dashicons-businessperson',
        26
    );

    add_submenu_page(
        'owners-admin',
        'Add New Owner',
        'Add New Owner',
        'manage_options',
        'owners-create',
        'owners_create_page'
    );

    add_submenu_page(
        'admin.php',
        'Edit Owner',
        'Edit Owner',
        'manage_options',
        'owners-admin-edit',
        'owners_admin_edit_page_callback'
    );

    // Screen Options (per-page)
    add_action("load-$hook", function () {
        add_screen_option('per_page', [
            'label'   => 'Owners per page',
            'default' => 20,
            'option'  => 'owners_per_page',
        ]);
    });
}

// Necesario para guardar el valor en opciones de pantalla
add_filter('set-screen-option', function ($status, $option, $value) {
    if ($option === 'owners_per_page') {
        return (int) $value;
    }
    return $status;
}, 10, 3);

function owners_admin_page()
{
    $table = new OwnersTable();
    $table->prepare_items();
    
    $service = new OwnerService();

    $all     = $service->countOwners();
    $active  = $service->countOwners(1);
    $inactive = $service->countOwners(0);
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Owners</h1>
        <a href="<?php echo admin_url('admin.php?page=owners-create'); ?>" class="page-title-action">Add New Owner</a>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Leaked list of owners</h2>

        <?php
        $post_status = $_GET['post_status'] ?? 'all';

        function is_current($status, $current)
        {
            return $status === $current ? 'class="current"' : '';
        }
        ?>
        <ul class="subsubsub">
            <li class="all">
                <a <?php echo is_current('all', $post_status); ?> href="<?php echo admin_url('admin.php?page=owners-admin'); ?>">
                    All <span class="count">(<?php echo $all; ?>)</span>
                </a> |
            </li>
            <li class="active">
                <a <?php echo is_current('active', $post_status); ?> href="<?php echo admin_url('admin.php?page=owners-admin&post_status=active'); ?>">
                    Active <span class="count">(<?php echo $active; ?>)</span>
                </a> |
            </li>
            <li class="inactive">
                <a <?php echo is_current('inactive', $post_status); ?> href="<?php echo admin_url('admin.php?page=owners-admin&post_status=inactive'); ?>">
                    Inactive <span class="count">(<?php echo $inactive; ?>)</span>
                </a>
            </li>
        </ul>
        <br><br><br>
        <form id="posts-filter" method="post">
            <?php $table->display(); ?>
        </form>
    </div>
<?php
}

require_once __DIR__ . '/owners_edit.php';
require_once __DIR__ . '/owners_new.php';
