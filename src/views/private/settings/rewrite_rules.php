<?php

function custom_properties_rewrite_rules()
{
    add_rewrite_rule(
        '^property/([^/]+)/?$',
        'index.php?cs_property_slug=$matches[1]',
        'top'
    );
}
add_action('init', 'custom_properties_rewrite_rules');


function custom_properties_query_vars($vars)
{
    $vars[] = 'cs_property_slug';
    return $vars;
}
add_filter('query_vars', 'custom_properties_query_vars');


function load_custom_property_template()
{
    $slug = get_query_var('cs_property_slug');

    if ($slug) {

        // Importar el servicio
        require_once __DIR__ . '/../../../services/PropertyService.php';

        // Instanciarlo dentro de la función
        $property_service = new PropertyService();

        $property = $property_service->getBySlug($slug);

        if ($property) {
            global $cpd;
            $cpd = $property;

            include plugin_dir_path(__DIR__) . '/../public/templates/single-property.php';
            exit;
        } else {
            wp_redirect(home_url('/404'));
            exit;
        }
    }
}
add_action('template_redirect', 'load_custom_property_template');
