<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/OwnerEntity.php';
require_once __DIR__ . '/../dtos/CreateOwnerDto.php';
require_once __DIR__ . '/../dtos/UpdateOwnerDto.php';

class OwnerRepository
{

    private $wpdb;
    private $table = 'cs_owners';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function insert(CreateOwnerDto $createOwnerDto)
    {
        $data = $createOwnerDto->getDataValues();
        $dataTypes = $createOwnerDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);

        if (!$inserted) {
            return null;
        }

        $owner_id = $this->wpdb->insert_id;

        return $this->find($owner_id);
    }

    public function emailExists(string $email): bool
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = %s", $email)
        );

        return $count > 0;
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return new OwnerEntity($row->id, $row->name, $row->email, $row->password, $row->phone, $row->visible_info, $row->is_active);
    }

    public function findLikeArray(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query, ARRAY_A);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return $row;
    }

    // Agrega este método:
    public function countAll(?int $isActive = null): int
    {
        if ($isActive === null) {
            $sql = $this->wpdb->prepare("SELECT COUNT(*) FROM $this->table WHERE id != %d", NOT_ASSIGNED_ID);
        } else {
            $sql = $this->wpdb->prepare("SELECT COUNT(*) FROM $this->table WHERE is_active = %d AND id != %d", $isActive, NOT_ASSIGNED_ID);
        }
        return (int) $this->wpdb->get_var($sql);
    }

    public function getAll(?int $isActive = null, int $itemsPerPage = 20, int $pageNumber = 0)
    {
        $offset = $itemsPerPage * $pageNumber;

        if ($isActive === null) {
            // No se filtra por is_active
            $query = $this->wpdb->prepare(
                "SELECT * FROM $this->table WHERE id != %d LIMIT %d OFFSET %d",
                NOT_ASSIGNED_ID,
                $itemsPerPage,
                $offset
            );
        } else {
            // Se filtra por is_active
            $query = $this->wpdb->prepare(
                "SELECT * FROM $this->table WHERE is_active = %d AND id != %d LIMIT %d OFFSET %d",
                $isActive,
                NOT_ASSIGNED_ID,
                $itemsPerPage,
                $offset
            );
        }

        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $owners = [];
        foreach ($results as $row) {
            $owners[] = new OwnerEntity($row->id, $row->name, $row->email, $row->password, $row->phone, $row->visible_info, $row->is_active);
        }

        return $owners;
    }

    public function getPropertiesByOwnerId($ownerId)
    {
        $table = 'cs_owner_property';
        $property_table =  'cs_properties';

        $query = $this->wpdb->prepare("SELECT DISTINCT p.id, p.name FROM $table op INNER JOIN $property_table p ON p.id = op.property_id WHERE op.owner_id = %d", $ownerId);
        return $this->wpdb->get_results($query);
    }

    public function update(UpdateOwnerDto $updateOwnerDto)
    {
        $data = $updateOwnerDto->getDataValues();
        $dataTypes = $updateOwnerDto->getDataTypes();

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $updateOwnerDto->getId()], // Asegurar la condición WHERE id = ?
            $dataTypes,
            ['%d'] // Tipo de dato del ID
        );

        return $result !== false; // Devuelve true si la actualización fue exitosa
    }

    public function updateActiveStatus(int $id, int $status): bool
    {
        return $this->wpdb->update(
            $this->table,
            ['is_active' => $status],
            ['id' => $id],
            ['%d'],
            ['%d']
        ) !== false;
    }

    public function getOwnersByProperty($propertyId)
    {
        $optable = 'cs_owner_property';
        $query = $this->wpdb->prepare("SELECT o.id as owner_id, o.name, o.email, o.phone, o.visible_info, o.is_active, op.owner_position FROM $optable op INNER JOIN $this->table o ON op.owner_id = o.id WHERE op.property_id = %d ORDER BY op.owner_position ASC", $propertyId);
        $results = $this->wpdb->get_results($query, ARRAY_A);
        return $results;
    }

    public function getOwnerByPositionInProperty($propertyId, $ownerPosition)
    {
        $optable = 'cs_owner_property';
        $query = $this->wpdb->prepare("SELECT o.id as owner_id, o.name, o.email, o.phone FROM $optable op INNER JOIN $this->table o ON op.owner_id = o.id WHERE op.property_id = %d AND op.owner_position = %d LIMIT 1", $propertyId, $ownerPosition);
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    public function delete(int $id): bool
    {
        // Solo elimina en cs_owners; la validación se hace antes
        return $this->wpdb->delete($this->table, ['id' => $id], ['%d']) !== false;
    }

}
