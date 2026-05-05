<?php

use BcMath\Number;

require_once __DIR__ . '/../dtos/CreateSeasonDto.php';
require_once __DIR__ . '/../dtos/UpdateSeasonDto.php';

class SeasonsRepository
{

    private $wpdb;
    private $table = 'cs_seasons';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function find(string $date, int $year)
    {
        $query = $this->wpdb->prepare("SELECT id FROM $this->table WHERE date = %s AND year = %d", $date, $year);
        $row = $this->wpdb->get_var($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return $row;
    }

    public function insert(CreateSeasonDto $createSeasonDto)
    {
        $data = $createSeasonDto->getDataValues();
        $dataTypes = $createSeasonDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);

        // $season_id = $this->wpdb->insert_id;
        // return $this->wpdb->find($season_id);
        return $inserted !== false;
    }

    public function update(UpdateSeasonDto $UpdateSeasonDto)
    {
        $data = $UpdateSeasonDto->getDataValues();
        $dataTypes = $UpdateSeasonDto->getDataTypes();

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $UpdateSeasonDto->getId()], // Asegurar la condición WHERE id = ?
            $dataTypes,
            ['%d'] // Tipo de dato del ID
        );

        return $result !== false; // Devuelve true si la actualización fue exitosa
    }

    public function delete(int $id)
    {
        $result = $this->wpdb->delete(
            $this->table,
            ['id' => $id], // Condición WHERE
            ['%d']         // Tipo de dato del ID
        );

        return $result !== false; // true si se eliminó correctamente
    }


    public function getAll(int $year)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE year = %d", $year);
        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        return $results;
    }
}
