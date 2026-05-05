<?php

use BcMath\Number;

class AlertsRepository
{

    private $wpdb;
    private $table = 'cs_alerts';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return $row;
    }

    public function update(UpdateAlertsDto $updateAlertsDto)
    {
        $data = $updateAlertsDto->getDataValues();
        $dataTypes = $updateAlertsDto->getDataTypes();

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $updateAlertsDto->getId()], // Asegurar la condición WHERE id = ?
            $dataTypes,
            ['%d'] // Tipo de dato del ID
        );

        return $result !== false; // Devuelve true si la actualización fue exitosa
    }

}
