<?php

/**
 * Plugin Name: Calendar System
 * Description: A plugin to enable booking for property co-owners 
 * Version: 1.0
 * Author: WordpressOngoing
 * Author URI: https://wordpressongoing.com
 **/

define('NOT_APPEAR', false);
define('MEDIA', plugin_dir_url(__FILE__) . 'assets/media');
define('FONTS', plugin_dir_url(__FILE__) . 'assets/fonts');
define('CSS', plugin_dir_url(__FILE__) . 'assets/css');
define('JS', plugin_dir_url(__FILE__) . 'assets/js');
define('LIBRARIES', plugin_dir_url(__FILE__) . 'assets/libraries');

define('MAIL', plugin_dir_path(__FILE__) . 'src/views/mail');
define('LIBS', plugin_dir_path(__FILE__) . 'libs/dompdf');

define('COLOR_CODES', ['#80B3FF', '#FFC95C', '#FF80E5', '#AC98FE', '#66D9FF', '#F28449', '#85F26D', '#FF6161']);
define('SELECTED_COLOR_IN_CALENDAR_DEFAULT', '#FF0000');
define('NOT_ASSIGNED_ID', 1);
define('THEME_VERSION', '7.0.8');

// social icons
define('LOGO_MAIL', MEDIA . '/white-logo.png');
define('IG_ICON', MEDIA . '/social/instagram-icon.png');
define('FB_ICON', MEDIA . '/social/facebook-icon.png');
define('IN_ICON', MEDIA . '/social/linkedin-icon.png');
define('YT_ICON', MEDIA . '/social/youtube-icon.png');

$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
define('IS_LOCALHOST', $is_localhost);

require_once __DIR__ . '/src/services/CalendarService.php';

/**
 * Helper de logging (envía al debug.log con prefijo)
 */
if (!function_exists('cs_log')) {
    function cs_log($msg, array $context = [])
    {
        $prefix = '[CalendarSystem] ';
        if (!empty($context)) {
            // Evita warnings por binarios/objetos recursivos
            foreach ($context as $k => $v) {
                if (is_object($v) || is_array($v)) {
                    $context[$k] = print_r($v, true);
                }
            }
            $msg .= ' | ' . json_encode($context);
        }
        error_log($prefix . $msg);
    }
}

// --------------------------------------------------------------------
// JS -> PHP logger
add_action('wp_ajax_mojo_panel_jslog', 'mojo_panel_jslog');
add_action('wp_ajax_nopriv_mojo_panel_jslog', 'mojo_panel_jslog');

function mojo_panel_jslog()
{
    // Mensaje
    $message = isset($_POST['message'])
        ? sanitize_text_field(wp_unslash($_POST['message']))
        : '';

    // Contexto opcional (JSON)
    $context = [];
    if (!empty($_POST['context'])) {
        $raw = wp_unslash($_POST['context']);
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Asegura que nada rompa el error_log
            foreach ($decoded as $k => $v) {
                $context[$k] = is_scalar($v) ? $v : print_r($v, true);
            }
        }
    }

    if ($message === '') {
        wp_send_json_error(['message' => 'Empty log message']);
    }

    // Usa tu helper ya definido arriba en el plugin
    if (function_exists('cs_log')) {
        cs_log($message, $context);
    } else {
        // fallback
        error_log('[CalendarSystem] ' . $message . ' | ' . json_encode($context));
    }

    wp_send_json_success(['logged' => true]);
}


add_action('admin_notices', 'cs_recommend_wp_crontrol');
function cs_recommend_wp_crontrol()
{
    // Solo mostrar a usuarios que pueden instalar plugins
    if (!current_user_can('install_plugins')) {
        return;
    }

    // Verifica si WP Crontrol está activo
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

    if (!is_plugin_active('wp-crontrol/wp-crontrol.php')) {
        $plugin_url = 'https://wordpress.org/plugins/wp-crontrol/';

        echo '<div class="notice notice-info is-dismissible">
            <p><strong>Recommendation:</strong> To control automatic events, it is recommended to install the plugin <a target="_blank" href="' . esc_url($plugin_url) . '">WP Crontrol</a>.</p>
        </div>';
    }
}

register_activation_hook(__FILE__, 'my_plugin_execute_sql_script');
function my_plugin_execute_sql_script()
{
    global $wpdb;

    $nombre_tabla = 'cs_properties';

    $tabla_existe = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $nombre_tabla
    ));

    if ($tabla_existe === $nombre_tabla) {
        error_log('The table already exists. The SQL script will not be executed.');
        return;
    }

    // Ruta al archivo SQL
    $ruta_sql = plugin_dir_path(__FILE__) . 'script.sql';

    if (!file_exists($ruta_sql)) {
        error_log('SQL file not found: ' . $ruta_sql);
        return;
    }

    // Leer el contenido del archivo
    $sql = file_get_contents($ruta_sql);

    // Separar las sentencias SQL (básico, puede mejorarse si el archivo es complejo)
    $sentencias = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($sentencias as $sentencia) {
        if (!empty($sentencia)) {
            $wpdb->query($sentencia);
        }
    }

    error_log('SQL script executed successfully.');

    if (function_exists('custom_properties_rewrite_rules')) {
        custom_properties_rewrite_rules();
    }

    flush_rewrite_rules();
}

// --------------------------------------------------------------------

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('generalcss', CSS . '/general.css', array(), THEME_VERSION, 'all');
    wp_enqueue_style('notiscss', CSS . '/notifications/style.css', array(), THEME_VERSION, 'all');

    wp_enqueue_script('alerts', LIBRARIES . '/sweetalert/sweetalert.js', array(), null, true);
    wp_enqueue_script('notifications', JS . '/public/notification.js?r=' . time(), array(), null, true);
    wp_enqueue_script('logoutjs', JS . '/public/logout.js', array('jquery'), null, true);

    if (strpos($_SERVER['REQUEST_URI'], '/property/') !== false) {
        wp_enqueue_style('propertycss', CSS . '/sProperty/style.css', array(), THEME_VERSION, 'all');

        wp_enqueue_script('quote', JS . '/public/quote.js', array(), null, true);
        wp_enqueue_style('splidecss', LIBRARIES . '/splide/splide.min.css', array(), null, 'all');
        wp_enqueue_script('splidejs', LIBRARIES . '/splide/splide.min.js', array(), null, true);

        //daterangepicker
        if (IS_LOCALHOST) {
            wp_enqueue_script('jquery_custom', LIBRARIES . '/jquery.min.js', array(), null, true);
        }
        wp_enqueue_script('momentjs', LIBRARIES . '/daterange/moment.min.js', array('jquery'), null, true);
        wp_enqueue_script('daterangepickerjs', LIBRARIES . '/daterange/daterangepicker.min.js', array('jquery', 'momentjs'), null, true);
        wp_enqueue_script('init', LIBRARIES . '/daterange/init.js', array('jquery', 'momentjs', 'daterangepickerjs'), null, true);
        wp_enqueue_style('daterangepickercss', LIBRARIES . '/daterange/daterangepicker.min.css', array(), null, 'all');
    }
    if (is_page('panel')) {
        wp_enqueue_style('panel', CSS . '/panel/style.css', array(), THEME_VERSION, 'all');
        wp_enqueue_script('tabsjs', JS . '/public/tabs.js', array(), null, true);

        wp_enqueue_script('fullcalendarjs', LIBRARIES . '/fullcalendar/index.global.min.js', array(), null, true);

        wp_enqueue_script('calendar-config', JS . '/shared/calendar-config.js?r=' . time(), [], null, true);
        wp_enqueue_script('calendar', JS . '/public/calendar.js?r=' . time(), array('jquery'), null, true);
        wp_enqueue_script('selectingYear', JS . '/public/selectingYear.js?r=' . time(), array(), null, true);
    }
    if (is_page('dashboard')) {
        wp_enqueue_style('dashcss', CSS . '/dashboard/style.css', array(), THEME_VERSION, 'all');
    }
    if (is_page('login')) {
        wp_enqueue_style('logincss', CSS . '/login/style.css', array(), THEME_VERSION, 'all');
        wp_enqueue_script('loginjs', JS . '/public/login.js', array('jquery'), null, true);
    }
});

add_action('admin_enqueue_scripts', function ($hook) {
    wp_enqueue_script('alerts', LIBRARIES . '/sweetalert/sweetalert.js', array(), null, true);

    if (isset($_GET['page']) && in_array($_GET['page'], ['properties-create', 'properties-admin-edit'])) {
        wp_enqueue_media();
        wp_enqueue_script('property-image-upload', JS . '/private/property-image-upload.js', ['jquery'], THEME_VERSION, true);
    }

    if (isset($_GET['page']) && in_array($_GET['page'], ['properties-admin-edit'])) {
        wp_enqueue_media();
        wp_enqueue_script('property-gallery-upload', JS . '/private/property-gallery-upload.js', ['jquery'], THEME_VERSION, true);

        wp_enqueue_script('property-operation-js', JS . '/private/property-operation.js', ['jquery'], THEME_VERSION, true);
        wp_enqueue_style('property-operation-css', CSS . '/private/property-operation.css', array(), THEME_VERSION, 'all');
        wp_enqueue_style('property-operation-css', CSS . '/private/property.css', array(), THEME_VERSION, 'all');
        wp_enqueue_style('property-operation-css', CSS . '/private/seasons.css', array(), THEME_VERSION, 'all');
    }

    if (isset($_GET['page']) && in_array($_GET['page'], ['seasons', 'calendar-system-edit'])) {
        wp_enqueue_script('sortjs', LIBRARIES . '/sortable/Sortable.min.js', [], THEME_VERSION, true);
        wp_enqueue_script('fullcalendarjs', LIBRARIES . '/fullcalendar/index.global.min.js', [], THEME_VERSION, true);
    }

    if (isset($_GET['page']) && in_array($_GET['page'], ['seasons'])) {
        wp_enqueue_script('seasons', JS . '/private/seasons.js?r=' . time(), ['jquery'], THEME_VERSION, true);
    }

    if (isset($_GET['page']) && in_array($_GET['page'], ['calendar-system-edit'])) {
        wp_enqueue_style('popup', CSS . '/private/popup.css', array(), THEME_VERSION, 'all');
        wp_enqueue_style('calendaredit', CSS . '/private/calendar-edit.css', array(), THEME_VERSION, 'all');

        //daterangepicker
        wp_enqueue_script('jquery_custom', LIBRARIES . '/jquery.min.js', array(), THEME_VERSION, true);
        wp_enqueue_script('momentjs', LIBRARIES . '/daterange/moment.min.js', array('jquery_custom'), THEME_VERSION, true);
        wp_enqueue_script('daterangepickerjs', LIBRARIES . '/daterange/daterangepicker.min.js', array('jquery_custom'), THEME_VERSION, true);
        wp_enqueue_style('daterangepickercss', LIBRARIES . '/daterange/daterangepicker.min.css', array(), THEME_VERSION, 'all');

        wp_enqueue_script('notifications', JS . '/public/notification.js?r=' . time(), [], null, true);

        wp_enqueue_script('calendar-config', JS . '/shared/calendar-config.js?r=' . time(), [], null, true);
        wp_enqueue_script('comments', JS . '/private/comments.js?r=' . time(), ['jquery'], THEME_VERSION, true);
        wp_enqueue_script('calendar', JS . '/private/calendar.js?r=' . time(), ['jquery'], THEME_VERSION, true);
    }
});

add_filter("script_loader_tag", "add_module_to_my_script", 10, 3);
function add_module_to_my_script($tag, $handle, $src)
{
    if ("calendar-config" === $handle || "calendar" === $handle) {
        $tag = '<script type="module" src="' . esc_url($src) . '" id="' . $handle . '"></script>';
    }
    return $tag;
}

add_action('wp_footer', function () {
?>
    <input type="hidden" id="mojo-admin_ajax" value="<?php echo admin_url('admin-ajax.php'); ?>">
    <input type="hidden" id="mojo-uri" value="<?php echo get_site_url(); ?>">
    <input type="hidden" id="mojo-media" value="<?php echo MEDIA; ?>">
<?php
});

// --------------------------------------------------------------------

function create_pages()
{
    $pages = ['Login', 'Panel', 'Dashboard'];

    foreach ($pages as $page) {
        $query = new WP_Query([
            'post_type'   => 'page',
            'title'       => $page,
            'post_status' => 'any',
            'numberposts' => 1,
        ]);

        // Si no existe la página, la creamos
        if (empty($query->posts)) {
            $short = strtolower($page);
            $new_page = array(
                'post_title'    => $page,
                'post_content'  => "[mojo_$short]",
                'post_status'   => 'publish',
                'post_type'     => 'page'
            );

            wp_insert_post($new_page);
        }
    }
}
add_action('init', 'create_pages', 5);


function mojo_start_session()
{
    /*if (!session_id()) {
        session_start();
    }*/

    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        session_start();
    }
}
add_action('init', 'mojo_start_session', 1);


function get_color($season)
{
    if ($season == '14-day') {
        return '#D4E5F7';
    }
    if ($season == 'high') {
        return '#D7E8FA';
    }
    if ($season == 'middle') {
        return '#FFF0D9';
    }
    if ($season == 'low') {
        return '#FFE0E0';
    }
}

function get_seasons($in_admin_view = true)
{
    global $wpdb;
    if ($in_admin_view) {
        $year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : intval(date('Y')) + 0;
    } else {
        $property_id    = filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT) ?: 0;
        $calendar_service = new CalendarService();
        $nearest_year = $calendar_service->getNearestCalendar($property_id);
        $year = isset($_GET['period']) ? absint($_GET['period']) : $nearest_year;
    }
    $table = 'cs_seasons';

    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE year = %s", $year),
        ARRAY_A
    );

    if ($results) {
        foreach ($results as $row) {
            $color = get_color($row['type']);
            echo "{
                    id: '" . $row['id'] . "',
                    start: '" . $row['date'] . "',
                    end: '" . $row['date'] . "',
                    allDay: true,
                    overlap: false,
                    display: 'background',
                    color: '$color',
                    allDay: true
                },";
        }
    }
}

function get_first_and_last_day_of_seasons($in_admin_view = true)
{
    global $wpdb;

    if ($in_admin_view) {
        $year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : intval(date('Y')) + 0;
    } else {
        $property_id    = filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT) ?: 0;
        $calendar_service = new CalendarService();
        $nearest_year = $calendar_service->getNearestCalendar($property_id);
        $year = isset($_GET['period']) ? absint($_GET['period']) : $nearest_year;
    }

    $table = 'cs_seasons';

    $first_last = $wpdb->get_row(
        $wpdb->prepare("
            SELECT 
                MIN(date) AS first_date, 
                MAX(date) AS last_date
            FROM $table 
            WHERE year = %s
        ", $year),
        ARRAY_A
    );

    if ($first_last) {
        echo '<input type="hidden" id="season_first_date" value="' . esc_attr($first_last['first_date']) . '">';
        echo '<input type="hidden" id="season_last_date" value="' . esc_attr($first_last['last_date']) . '">';
    }
}


function get_aside_calendar($format = 1)
{
?>
    <?php if ($format == 1): ?>
        <div class="seasons">
            <h4>SEASONS</h4>
            <div class="seasons_flex">
                <div style="background:<?php echo get_color('low'); ?>">Low<br>Season</div>
                <div style="background:<?php echo get_color('middle'); ?>">Middle<br>Season</div>
                <div style="background:<?php echo get_color('high'); ?>">High<br>Season</div>
            </div>
        </div>
    <?php else: ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2>Seasons</h2>
            </div>
            <div class="inside seasons_flex" style="margin-top:12px">
                <div style="background:<?php echo get_color('low'); ?>">Low<br>Season</div>
                <div style="background:<?php echo get_color('middle'); ?>">Middle<br>Season</div>
                <div style="background:<?php echo get_color('high'); ?>">High<br>Season</div>
            </div>
        </div>
    <?php endif; ?>
<?php
}

require_once __DIR__ . '/src/views/auto/cron.php';
require_once __DIR__ . '/src/views/mail/parts.php';

function send_notification_email($subject, $message, $to, $attachments = [])
{
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: MojoSharing <no-reply@mojosharing.com>'
    ];

    if (!empty($to) && $to != '-') {
        $final_message = mojo_email_header() . render_notification($message) . mojo_email_footer();

        $sent = wp_mail($to, $subject, $final_message, $headers, $attachments);
        error_log("Mail to $to: " . ($sent ? 'SUCCESS' : 'FAILED'));
        return $sent;
    }
}

function render_notification($raw)
{
    $raw = wp_unslash($raw);
    return wpautop(wp_kses_post($raw));
}

require_once __DIR__ . '/src/views/private/owners/owners_admin.php';
require_once __DIR__ . '/src/views/private/properties/properties_admin.php';
require_once __DIR__ . '/src/views/private/propertyoperation/propertyoperation.php';
require_once __DIR__ . '/src/views/private/settings/settings.php';
require_once __DIR__ . '/src/views/public/components.php';
