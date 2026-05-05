<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/ExchangeRequestEntity.php';
require_once __DIR__ . '/../dtos/CreateExchangeRequestDto.php';
require_once __DIR__ . '/../dtos/UpdateExchangeRequestDto.php';

class ExchangeRequestRepository
{

    private $wpdb;
    private $table = 'cs_exchange_request';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function insert(CreateExchangeRequestDto $createExchangeRequestDto)
    {
        $data = $createExchangeRequestDto->getDataValues();
        $dataTypes = $createExchangeRequestDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);
        if (!$inserted) {
            return null; // O lanzar una excepción
        }

        $request_id = $this->wpdb->insert_id;
        return $this->find($request_id);
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return new ExchangeRequestEntity($row->id, $row->id_calendar, $row->from_owner, $row->to_owner, $row->start_from, $row->end_from, $row->start_to, $row->end_to, $row->status);
    }

    public function update(UpdateExchangeRequestDto $updateExchangeRequestDto)
    {
        $data = $updateExchangeRequestDto->getDataValues();
        $dataTypes = $updateExchangeRequestDto->getDataTypes();

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $updateExchangeRequestDto->getId()], // Asegurar la condición WHERE id = ?
            $dataTypes,
            ['%d'] // Tipo de dato del ID
        );

        return $result !== false; // Devuelve true si la actualización fue exitosa
    }

    public function getAllByOwner($owner_id, $calendar_id, $origin)
    {
        if ($origin == 'from') {
            $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE from_owner = %d AND id_calendar = %d AND status != %s", $owner_id, $calendar_id, 'approved');
        } else {
            $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE to_owner = %d AND id_calendar = %d AND (status != %s AND status != %s)", $owner_id, $calendar_id, 'canceled', 'approved');
        }

        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $exchange_request = [];
        foreach ($results as $row) {
            $exchange_request[] = new ExchangeRequestEntity($row->id, $row->id_calendar, $row->from_owner, $row->to_owner, $row->start_from, $row->end_from, $row->start_to, $row->end_to, $row->status);
        }

        return $exchange_request;
    }

    public function getPendingByCalendar(int $calendarId): array
    {
        $sql = "
            SELECT 
                r.id,
                r.status,
                b1.start_date AS f_start, 
                b1.end_date AS f_end, 
                b1.calendar_id AS f_cal,
                b2.start_date AS t_start, 
                b2.end_date AS t_end, 
                b2.calendar_id AS t_cal
            FROM cs_exchange_request r
            LEFT JOIN cs_booking b1 ON b1.id = r.from_owner_booking
            LEFT JOIN cs_booking b2 ON b2.id = r.to_owner_booking
            WHERE r.status = 'pending'
              AND (b1.calendar_id = %d OR b2.calendar_id = %d)
        ";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $calendarId, $calendarId),
            ARRAY_A
        ) ?: [];
    }
}
