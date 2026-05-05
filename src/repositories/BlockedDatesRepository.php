<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/BlockDateEntity.php';
require_once __DIR__ . '/../dtos/CreateBlockDateDto.php';

class BlockedDatesRepository
{

    private $wpdb;
    private $table = 'cs_blocked_dates';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function insert(CreateBlockDateDto $createBlockDateDto)
    {
        $data = $createBlockDateDto->getDataValues();
        $dataTypes = $createBlockDateDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);
        if (!$inserted) {
            return null; // O lanzar una excepción
        }

        $blockdate_id = $this->wpdb->insert_id;
        return $this->find($blockdate_id);
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return new BlockDateEntity($row->id, $row->calendar_id, $row->date);
    }

    public function getAll($calendar_id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE calendar_id = %d", $calendar_id);
        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $dates = [];
        foreach ($results as $row) {
            $dates[] = new BlockDateEntity($row->id, $row->calendar_id, $row->date);
        }

        return $dates;
    }

    public function delete(int $id)
    {
        $deleted = $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        return $deleted !== false;
    }

}
