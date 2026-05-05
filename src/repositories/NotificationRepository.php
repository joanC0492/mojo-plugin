<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/NotificationEntity.php';
require_once __DIR__ . '/../dtos/CreateNotificationDto.php';

class NotificationRepository
{

    private $wpdb;
    private $table = 'cs_notifications';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function insert(CreateNotificationDto $createNotificationDto)
    {
        $data = $createNotificationDto->getDataValues();
        $dataTypes = $createNotificationDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);
        if (!$inserted) {
            return null; // O lanzar una excepción
        }

        $calendar_id = $this->wpdb->insert_id;
        return $calendar_id;
    }
    
    public function findByUser(int $owner_id, int $limit = 5, int $offset = 0)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table WHERE owner_id = %d ORDER BY datetime DESC LIMIT %d OFFSET %d",
            $owner_id,
            $limit,
            $offset
        );

        $rows = $this->wpdb->get_results($query);
        return $rows ?: [];
    }

}
