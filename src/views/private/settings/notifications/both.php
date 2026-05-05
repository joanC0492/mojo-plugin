<?php

add_action('admin_init', 'custom_notifications_editor_buttons');

function custom_notifications_editor_buttons()
{
    if (isset($_GET['page']) && in_array($_GET['page'], ['notifications', 'admin-notifications', 'owner-notifications'])) {
        add_filter('mce_external_plugins', 'add_notification_placeholders_plugin');
        add_filter('mce_buttons', 'register_notification_placeholders_button');
    }
}

function register_notification_placeholders_button($buttons)
{
    array_push($buttons, 'notification_placeholders');
    return $buttons;
}

function add_notification_placeholders_plugin($plugin_array)
{
    $plugin_array['notification_placeholders'] = JS . '/private/notification-placeholders.js?r=' . time();
    return $plugin_array;
}
