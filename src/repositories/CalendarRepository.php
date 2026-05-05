<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/CalendarEntity.php';
require_once __DIR__ . '/../dtos/CreateCalendarDto.php';
require_once __DIR__ . '/../dtos/UpdateCalendarDto.php';

class CalendarRepository
{

    private $wpdb;
    private $table = 'cs_calendar';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function insert(CreateCalendarDto $createCalendarDto)
    {
        $data = $createCalendarDto->getDataValues();
        $dataTypes = $createCalendarDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);
        if (!$inserted) {
            return null; // O lanzar una excepción
        }

        $calendar_id = $this->wpdb->insert_id;
        return $this->find($calendar_id);
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return new CalendarEntity($row->id, $row->property_id, $row->year, $row->owners_priority, $row->round, $row->turn, $row->status, $row->colors_order, $row->toggle_download_calendar);
    }

    public function selectYears(int $id): array
    {
        $query = $this->wpdb->prepare("SELECT DISTINCT year FROM $this->table WHERE property_id = %d ORDER BY year ASC", $id);
        $results = $this->wpdb->get_col($query);

        return array_map('intval', $results);
    }

    public function selectIdCalendar(int $id, int $year)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE property_id = %d AND year = %d", $id, $year);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return new CalendarEntity($row->id, $row->property_id, $row->year, $row->owners_priority, $row->round, $row->turn, $row->status, $row->colors_order, $row->toggle_download_calendar);
    }

    public function getYearOfNearestCalendar(int $propertyId): ?int
    {
        $currentYear = (int) current_time('Y');

        $sql = $this->wpdb->prepare("SELECT year FROM $this->table WHERE property_id = %d ORDER BY ABS(year - %d) ASC, year ASC LIMIT 1", $propertyId, $currentYear);

        $nearestYear = $this->wpdb->get_var($sql);

        return $nearestYear !== null ? (int) $nearestYear : null;
    }

    public function update(UpdateCalendarDto $updateCalendarDto)
    {
        $data = $updateCalendarDto->getDataValues();
        $dataTypes = $updateCalendarDto->getDataTypes();

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $updateCalendarDto->getId()], // Asegurar la condición WHERE id = ?
            $dataTypes,
            ['%d'] // Tipo de dato del ID
        );

        return $result !== false; // Devuelve true si la actualización fue exitosa
    }

    public function reset(int $id, int $year): bool
    {
        $updated = $this->wpdb->update(
            $this->table,
            [
                'year'            => $year,
                'round'           => 1,
                'turn'            => 1,
                'status'          => 'close',
                'owners_priority' => null,
                'colors_order'    => null
            ],
            ['id' => $id],
            ['%d', '%d', '%d', '%s', null, null],
            ['%d']
        );

        return $updated !== false;
    }


    public function getAll()
    {
        // $offset = $itemsPerPage * $pageNumber;

        $query = $this->wpdb->prepare("SELECT * FROM $this->table ORDER BY created_at DESC");

        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $calendars = [];
        foreach ($results as $row) {
            $calendars[] = new CalendarEntity($row->id, $row->property_id, $row->year, $row->owners_priority, $row->round, $row->turn, $row->status, $row->colors_order, $row->toggle_download_calendar);
        }

        return $calendars;
    }

    function deleteBlockedDate(int $id, int $calendarId): bool
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            'cs_blocked_dates',
            [
                'id'          => $id,
                'calendar_id' => (int) $calendarId
            ],
            ['%d', '%d']
        );

        return $deleted !== false;
    }
}
