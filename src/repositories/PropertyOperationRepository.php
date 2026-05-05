<?php

use BcMath\Number;

require_once __DIR__ . '/../dtos/CreatePropertyOperationDto.php';

class PropertyOperationRepository
{

    private $wpdb;
    private $table = 'cs_property_operation';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function getAll(int $id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table WHERE property_id = %d ORDER BY operation_date",
            $id
        );

        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        return $results;
    }

    public function delete($id)
    {
        $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function insert(CreatePropertyOperationDto $dto)
    {
        $data  = $dto->getDataValues();
        $types = $dto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $types);

        if (!$inserted) {
            return null;
        }

        $id = (int) $this->wpdb->insert_id;

        return $this->find($id);
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table WHERE id = %d",
            $id
        );

        return $this->wpdb->get_row($query);
    }
}
    