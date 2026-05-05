<?php

require_once 'PropertiesTable.php';
require_once __DIR__ . '../../../../services/PropertyService.php';

add_action('admin_menu', 'property_admin_menu');

function property_admin_menu()
{
    // Guarda el hook suffix
    $hook = add_menu_page(
        'Properties Management',      // Título de la página
        'Properties',                 // Título en el menú
        'manage_options',             // Capacidad necesaria
        'properties-admin',           // Slug del menú
        'properties_admin_page',      // Función callback
        'dashicons-admin-home',       // Icono
        26
    );

    add_submenu_page(
        'properties-admin',              // Slug del menú padre
        'Add New Property',              // Título de la página
        'Add New Property',              // Título en el menú
        'manage_options',                // Capacidad necesaria
        'properties-create',             // Slug del submenú
        'properties_create_page'         // Función callback
    );

    add_submenu_page(
        'admin.php',                     // Slug del menú padre (oculto)
        'Edit Property',                 // Título de la página
        'Edit Property',                 // Título en el menú (oculto)
        'manage_options',                // Capacidad necesaria
        'properties-admin-edit',         // Slug del submenú
        'properties_admin_edit_page_callback' // Función callback
    );

    // Screen Options (per-page)
    add_action("load-$hook", function () {
        add_screen_option('per_page', [
            'label'   => 'Properties per page',
            'default' => 20,
            'option'  => 'properties_per_page',
        ]);
    });
}

// Necesario para guardar el valor en opciones de pantalla
add_filter('set-screen-option', function ($status, $option, $value) {
    if ($option === 'properties_per_page') {
        return (int) $value;
    }
    return $status;
}, 10, 3);

function properties_admin_page()
{
    $table = new PropertiesTable();
    $table->prepare_items();

    $service = new PropertyService();

    $all     = $service->countProperties();
    $active  = $service->countProperties(1);
    $inactive = $service->countProperties(0);
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Properties</h1>
        <a href="<?php echo admin_url('admin.php?page=properties-create'); ?>" class="page-title-action">Add New Property</a>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Leaked list of properties</h2>

        <?php
        $post_status = $_GET['post_status'] ?? 'all';

        function is_current($status, $current)
        {
            return $status === $current ? 'class="current"' : '';
        }
        ?>
        <ul class="subsubsub">
            <li class="all">
                <a <?php echo is_current('all', $post_status); ?> href="<?php echo admin_url('admin.php?page=properties-admin'); ?>">
                    All <span class="count">(<?php echo $all; ?>)</span>
                </a> |
            </li>
            <li class="active">
                <a <?php echo is_current('active', $post_status); ?> href="<?php echo admin_url('admin.php?page=properties-admin&post_status=active'); ?>">
                    Active <span class="count">(<?php echo $active; ?>)</span>
                </a> |
            </li>
            <li class="inactive">
                <a <?php echo is_current('inactive', $post_status); ?> href="<?php echo admin_url('admin.php?page=properties-admin&post_status=inactive'); ?>">
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

require_once __DIR__ . '/properties_edit.php';
require_once __DIR__ . '/properties_new.php';