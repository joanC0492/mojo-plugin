<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once __DIR__ . '/../../../services/PropertyService.php';

class PropertiesCalendarTable extends WP_List_Table
{

    private $items_data;
    private $calendar_service;

    public function __construct()
    {
        $this->calendar_service = new CalendarService();

        parent::__construct([
            'singular' => 'Property',
            'plural'   => 'Properties',
            'ajax'     => false,
        ]);
    }

    public function get_columns()
    {
        return [
            'name'      => 'Name',
            'year'    => 'Year',
            'code'    => 'Code',
            'status'    => 'Picking Status',
        ];
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name] ?? '';
    }

    public function column_name($item)
    {
        $edit_url = admin_url('admin.php?page=calendar-system-edit&id=' . $item['id']);

        $actions = [
            'edit' => sprintf('<a href="%s">Edit Calendar</a>', esc_url($edit_url)),
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
        // Mock data – replace with DB results
        $this->items_data = $this->calendar_service->getAllCalendars();

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->items = $this->items_data;
    }
}
