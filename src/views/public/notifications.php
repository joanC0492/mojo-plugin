<?php

function get_notifications()
{
    if (!is_admin() && !current_user_can('administrator') && !isset($_SESSION['mojo_owner_id'])) {
        wp_redirect(home_url('dashboard'));
        exit;
    }

    $owner_id = isset($_SESSION['mojo_owner_id']) ? intval($_SESSION['mojo_owner_id']) : null;

    if (!is_int($owner_id)) {
        return '';
    }
?>

    <main class="mojo_plugin mojo_notifications-widget">
        <?php get_mojo_header(); ?>
        <div class="mojo_panel-body">
            <div class="mojo_container">
                <div class="mojo_notifications-top">
                    <h1>Notification Center</h1>
                    <?php get_back_dashboard(); ?>
                </div>
                <div class="mojo_notifications--list">
                    <?php echo get_notifications_list($owner_id); ?>
                </div>
                <div class="mojo_notifications-bottom">
                    <a href="" class="load">Load More</a>
                </div>
            </div>
        </div>
    </main>
<?php
}
add_shortcode('mojo_notifications', 'get_notifications'); // [mojo_notifications]