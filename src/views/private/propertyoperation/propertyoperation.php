<?php

require_once __DIR__ . '../../../../services/PropertyOperationService.php';

function save_property_operation()
{
    $service = new PropertyOperationService();

    $date        = sanitize_text_field($_POST['date']);
    $title       = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $type        = sanitize_text_field($_POST['type']);
    $property_id = intval($_POST['property_id']);

    if (!$date || !$title || !$property_id || !$type) {
        wp_send_json_error('Incomplete data');
    }

    $inserted = $service->insertOperation(
        $property_id,
        $date,
        $title,
        $description,
        $type
    );

    if ($inserted) {
        wp_send_json_success([
            'id' => $inserted->id,
            'operation' => $inserted
        ]);
    } else {
        wp_send_json_error('Error inserting');
    }
}

add_action('wp_ajax_save_property_operation', 'save_property_operation');

function delete_property_operation()
{
    $service = new PropertyOperationService();

    $operation_id = intval($_POST['operation_id']);
    if (!$operation_id) {
        wp_send_json_error('Invalid ID');
    }

    $service->deleteOperation($operation_id);

    wp_send_json_success();
}

add_action('wp_ajax_delete_property_operation', 'delete_property_operation');