<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/PropertyEntity.php';
require_once __DIR__ . '/../dtos/CreatePropertyDto.php';
require_once __DIR__ . '/../dtos/UpdatePropertyDto.php';

class PropertyRepository
{

    private $wpdb;
    private $table = 'cs_properties';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function insert(CreatePropertyDto $createPropertyDto)
    {
        $data = $createPropertyDto->getDataValues();
        $dataTypes = $createPropertyDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);
        if (!$inserted) {
            return null; // O lanzar una excepción
        }

        $property_id = (int) $this->wpdb->insert_id;
        return $this->find($property_id);
    }

    public function find(int $id)
    {
        if ($id <= 0) {
            return null;
        }

        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null;
        }

        return new PropertyEntity($row->id, $row->name, $row->description, $row->thumbnail, $row->code, $row->share_qty, $row->facebook_group, $row->whatsapp_group, $row->title, $row->resell_shares, $row->property_type, $row->bedroom, $row->bathroom, $row->location, $row->gallery, $row->key_features, $row->is_active, $row->slug, $row->show_shares, $row->rental_booking_page);
    }

    public function findBySlug(string $slug): ?PropertyEntity
    {
        if (empty($slug)) {
            return null;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE slug = %s LIMIT 1",
            $slug
        );

        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null;
        }

        return new PropertyEntity(
            $row->id,
            $row->name,
            $row->description,
            $row->thumbnail,
            $row->code,
            $row->share_qty,
            $row->facebook_group,
            $row->whatsapp_group,
            $row->title,
            $row->resell_shares,
            $row->property_type,
            $row->bedroom,
            $row->bathroom,
            $row->location,
            $row->gallery,
            $row->key_features,
            $row->is_active,
            $row->slug,
            $row->show_shares,
            $row->rental_booking_page
        );
    }


    public function countAll(?int $isActive = null): int
    {
        if ($isActive === null) {
            $sql = "SELECT COUNT(*) FROM {$this->table}";
            return (int) $this->wpdb->get_var($sql);
        } else {
            $sql = $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE is_active = %d", $isActive);
            return (int) $this->wpdb->get_var($sql);
        }
    }

    public function getAll(?int $isActive = null, int $itemsPerPage = 20, int $pageNumber = 0)
    {
        $offset = $itemsPerPage * $pageNumber;

        if ($isActive === null) {
            // No se filtra por is_active
            $query = $this->wpdb->prepare(
                "SELECT * FROM $this->table LIMIT %d OFFSET %d",
                $itemsPerPage,
                $offset
            );
        } else {
            // Se filtra por is_active
            $query = $this->wpdb->prepare(
                "SELECT * FROM $this->table WHERE is_active = %d LIMIT %d OFFSET %d",
                $isActive,
                $itemsPerPage,
                $offset
            );
        }

        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $properties = [];
        foreach ($results as $row) {
            $properties[] = new PropertyEntity($row->id, $row->name, $row->description, $row->thumbnail, $row->code, $row->share_qty, $row->facebook_group, $row->whatsapp_group, $row->title, $row->resell_shares, $row->property_type, $row->bedroom, $row->bathroom, $row->location, $row->gallery, $row->key_features, $row->is_active, $row->slug, $row->show_shares, $row->rental_booking_page);
        }

        return $properties;
    }

    public function getRelatedProperties(int $ownerId, int $format, int $itemsPerPage = 20, int $pageNumber = 0)
    {
        $offset = $itemsPerPage * $pageNumber;

        // Consulta SQL con JOIN y filtro is_active = 1
        if ($format == 1) {
            $query = $this->wpdb->prepare(
                "
            SELECT DISTINCT p.*
            FROM {$this->table} p
            INNER JOIN cs_owner_property op ON p.id = op.property_id
            WHERE op.owner_id = %d AND p.is_active = 1 
            ORDER BY CAST(SUBSTRING(p.code, 3) AS UNSIGNED)  
            LIMIT %d OFFSET %d
            ",
                $ownerId,
                $itemsPerPage,
                $offset
            );
        } elseif ($format == 2) {
            $query = $this->wpdb->prepare(
                "
                SELECT p.*
                FROM {$this->table} p
                LEFT JOIN cs_owner_property op ON p.id = op.property_id AND op.owner_id = %d
                WHERE op.owner_id IS NULL AND p.is_active = 1 
                ORDER BY CAST(SUBSTRING(p.code, 3) AS UNSIGNED)  
                LIMIT %d OFFSET %d
                ",
                $ownerId,
                $itemsPerPage,
                $offset
            );
        } else {
            $query = $this->wpdb->prepare(
                "
                SELECT p.*
                FROM {$this->table} p
                LEFT JOIN cs_owner_property op ON p.id = op.property_id AND op.owner_id = %d
                WHERE op.owner_id IS NULL AND p.is_active = 1
                ORDER BY CAST(SUBSTRING(p.code, 3) AS UNSIGNED)
                ",
                $ownerId
            );
        }

        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $properties = [];
        foreach ($results as $row) {
            $properties[] = new PropertyEntity($row->id, $row->name, $row->description, $row->thumbnail, $row->code, $row->share_qty, $row->facebook_group, $row->whatsapp_group, $row->title, $row->resell_shares, $row->property_type, $row->bedroom, $row->bathroom, $row->location, $row->gallery, $row->key_features, $row->is_active, $row->slug, $row->show_shares, $row->rental_booking_page);
        }

        return $properties;
    }

    public function update(UpdatePropertyDto $updatePropertyDto)
    {
        $data = $updatePropertyDto->getDataValues();

        // Mapa de formatos por columna
        $formatMap = [
            'name'           => '%s',
            'description'    => '%s',
            'thumbnail'      => '%s',
            'code'           => '%s',
            'share_qty'      => '%d',
            'facebook_group' => '%s',
            'whatsapp_group' => '%s',
            'title'          => '%s',
            'resell_shares'  => '%d',
            'property_type'  => '%s',
            'bedroom'        => '%d',
            'bathroom'       => '%d',
            'location'       => '%s',
            'gallery'        => '%s',
            'key_features'   => '%s',
            'is_active'      => '%d',
            'slug'           => '%s',
            'show_shares'    => '%d',
            'rental_booking_page' => '%s',
        ];

        //$dataTypes = $updatePropertyDto->getDataTypes();
        $dataTypes = array_map(
            fn($k) => $formatMap[$k] ?? '%s',
            array_keys($data)
        );

        $id = $updatePropertyDto->getId();

        // Log para depuración
        /*error_log('=== UPDATE PROPERTY DEBUG ===');
        error_log('Tabla: ' . $this->table);
        error_log('ID: ' . $id);
        error_log('Datos: ' . print_r($data, true));
        error_log('Tipos de datos: ' . print_r($dataTypes, true));*/

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id], // Asegurar la condición WHERE id = ?
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

    public function syncOwners($propertyId, array $ownersByPosition)
    {
        $wp_table = 'cs_owner_property';
        $this->wpdb->delete($wp_table, ['property_id' => $propertyId]);

        if (!empty($ownersByPosition)) {
            foreach ($ownersByPosition as $position => $ownerId) {
                if (!empty($ownerId)) {
                    $this->wpdb->insert($wp_table, [
                        'property_id'     => $propertyId,
                        'owner_id'        => $ownerId,
                        'owner_position'  => $position,
                    ]);
                }
            }
        }
    }

    public function getOwnerIdsByProperty($propertyId)
    {
        $query = $this->wpdb->prepare("SELECT * FROM cs_owner_property WHERE property_id = %d", $propertyId);
        // $results = $this->wpdb->get_col($query);
        $results = $this->wpdb->get_results($query);
        return $results;
    }

    public function getOwnersByPropertyId($propertyId, $formatSql = 1)
    {
        $table = 'cs_owner_property';
        $owner_table =  'cs_owners';

        if ($formatSql == 1) {
            $query = $this->wpdb->prepare("SELECT DISTINCT o.id, o.name FROM $table op INNER JOIN $owner_table o ON o.id = op.owner_id WHERE op.property_id = %d AND o.id != %d", $propertyId, NOT_ASSIGNED_ID);
        } else {
            $query = $this->wpdb->prepare("SELECT o.id, o.name, op.owner_position FROM $table op INNER JOIN $owner_table o ON o.id = op.owner_id WHERE op.property_id = %d AND o.id != %d", $propertyId, NOT_ASSIGNED_ID);
        }

        return $this->wpdb->get_results($query);
    }

    // ----------------------------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------------------------

    // Borra operaciones (cs_property_operation) por property_id
    public function deleteOperationsByProperty(int $propertyId): bool
    {
        return $this->wpdb->delete('cs_property_operation', ['property_id' => $propertyId], ['%d']) !== false;
    }

    // Obtiene los IDs de calendario de una propiedad
    public function getCalendarIdsByProperty(int $propertyId): array
    {
        $sql = $this->wpdb->prepare("SELECT id FROM cs_calendar WHERE property_id = %d", $propertyId);
        $ids = $this->wpdb->get_col($sql);
        return array_map('intval', $ids ?: []);
    }

    // Borra bookings por una lista de calendar_id
    public function deleteBookingsByCalendarIds(array $calendarIds): bool
    {
        if (empty($calendarIds)) return true;
        $in  = implode(',', array_fill(0, count($calendarIds), '%d'));
        $sql = $this->wpdb->prepare("DELETE FROM cs_booking WHERE calendar_id IN ($in)", $calendarIds);
        return $this->wpdb->query($sql) !== false;
    }

    // Borra el/los calendarios de la propiedad
    public function deleteCalendarsByProperty(int $propertyId): bool
    {
        return $this->wpdb->delete('cs_calendar', ['property_id' => $propertyId], ['%d']) !== false;
    }

    // Borra enlaces owner↔property
    public function deleteOwnerLinksByProperty(int $propertyId): bool
    {
        return $this->wpdb->delete('cs_owner_property', ['property_id' => $propertyId], ['%d']) !== false;
    }

    // Borra la propiedad
    public function delete(int $id): bool
    {
        return $this->wpdb->delete($this->table, ['id' => $id], ['%d']) !== false;
    }
}
