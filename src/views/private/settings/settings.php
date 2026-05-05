<?php

require_once __DIR__ . '../../../../services/SettingService.php';
require_once 'PropertiesCalendarTable.php';

add_action('admin_menu', 'general_admin_menu');

function general_admin_menu()
{
    // Menú principal
    add_menu_page(
        'Calendar System',      // Título de la página
        'Calendar System',                 // Título en el menú
        'manage_options',             // Capacidad necesaria
        'calendar-system',           // Slug del menú
        'general_admin_page',      // Función callback
        'dashicons-calendar',       // Icono
        25
    );

    add_submenu_page(
        'calendar-system',              // Slug del menú padre
        'General',              // Título de la página
        'General',              // Título en el menú
        'manage_options',                // Capacidad necesaria
        'general',             // Slug del submenú
        'contacts_system_page'         // Función callback
    );

    add_submenu_page(
        'calendar-system',              // Slug del menú padre
        'Seasons',              // Título de la página
        'Seasons',              // Título en el menú
        'manage_options',                // Capacidad necesaria
        'seasons',             // Slug del submenú
        'seasons_system_page'         // Función callback
    );

    add_submenu_page(
        'calendar-system',              // Slug del menú padre
        'Admin Notifications',              // Título de la página
        'Admin Notifications',              // Título en el menú
        'manage_options',                // Capacidad necesaria
        'admin-notifications',             // Slug del submenú
        'admin_notifications_system_page'         // Función callback
    );

    add_submenu_page(
        'calendar-system',              // Slug del menú padre
        'Owner Notifications',              // Título de la página
        'Owner Notifications',              // Título en el menú
        'manage_options',                // Capacidad necesaria
        'owner-notifications',             // Slug del submenú
        'owner_notifications_system_page'         // Función callback
    );

    /*add_submenu_page(
        'calendar-system',              // Slug del menú padre
        'Error Alerts',              // Título de la página
        'Error Alerts',              // Título en el menú
        'manage_options',                // Capacidad necesaria
        'error-alerts',             // Slug del submenú
        'error_system_page'         // Función callback
    );*/

    add_submenu_page(
        'admin.php',                     // Slug del menú padre (oculto)
        'Edit Calendar of Property',                 // Título de la página
        'Edit Calendar of Property',                 // Título en el menú (oculto)
        'manage_options',                // Capacidad necesaria
        'calendar-system-edit',         // Slug del submenú
        'calendar_system_edit_page_callback' // Función callback
    );
}

function general_admin_page()
{
    $table = new PropertiesCalendarTable();
    $table->prepare_items();
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Choose Your Property</h1>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Leaked list of properties</h2>
        <form id="posts-filter" method="post">
            <?php $table->display(); ?>
        </form>
    </div>
<?php
}

function get_request_email(): string
{
    $contacts = (new SettingService())->getContacts(1);
    if (!$contacts || empty($contacts->request_email)) {
        return '';
    }
    $email = trim($contacts->request_email);
    return $email;
}

function get_rent_email(): string
{
    $contacts = (new SettingService())->getContacts(1);
    if (!$contacts || empty($contacts->rent_email)) {
        return '';
    }
    $email = trim($contacts->rent_email);
    return $email;
}

function get_exchange_email(): string
{
    $contacts = (new SettingService())->getContacts(1);
    if (!$contacts || empty($contacts->exchange_email)) {
        return '';
    }
    $email = trim($contacts->exchange_email);
    return $email;
}

function get_contact_us_email(): string
{
    $contacts = (new SettingService())->getContacts(1);
    if (!$contacts || empty($contacts->contact_us_email)) {
        return '';
    }
    $email = trim($contacts->contact_us_email);
    return $email;
}

require_once __DIR__ . '/contacts.php';
require_once __DIR__ . '/seasons.php';
// require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/notifications/admin.php';
require_once __DIR__ . '/notifications/both.php';
require_once __DIR__ . '/notifications/owner.php';
require_once __DIR__ . '/calendar_edit.php';
require_once __DIR__ . '/rewrite_rules.php';
