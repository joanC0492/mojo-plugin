<?php

require_once __DIR__ . '/../repositories/PropertyRepository.php';

class PropertyService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PropertyRepository();
    }

    public function createProperty($name, $description, $thumbnail, $code, $share_qty, $slug)
    {
        $dto = new CreatePropertyDto($name, $description, $thumbnail, $code, $share_qty, $slug);
        return $this->repository->insert($dto);
    }

    public function handleCreateProperty(array $data)
    {
        global $wpdb;
        $calendar_service = new CalendarService();

        // Sanitizar datos del formulario
        $name        = sanitize_text_field($data['name']);
        $description = wp_kses_post($data['property_description']);
        $code        = sanitize_text_field($data['code']);
        $share_qty   = intval($data['share_qty']);
        $thumbnail   = esc_url_raw($data['property_image']);
        $slug        = sanitize_title($name);
        $nextYear    = intval(date('Y')) + 0;

        if (empty($thumbnail)) {
            return [
                'success' => false,
                'message' => 'The featured image has not been set yet.'
            ];
        }

        $inserted = $this->createProperty($name, $description, $thumbnail, $code, $share_qty, $slug);

        if ($inserted) {
            $inserted_id = $wpdb->insert_id;
            cs_log('Property inserted successfully.', ['property_id' => $inserted_id]);

            // Actualizar owners
            $selected_owners = [];
            for ($i = 1; $i <= $share_qty; $i++) {
                $selected_owners[$i] = NOT_ASSIGNED_ID;
            }
            $this->updateOwnersForProperty($inserted_id, $selected_owners);
            cs_log('Owners updated for property.', ['property_id' => $inserted_id]);

            // Crea el calendario
            $calendar_service->createCalendar($inserted_id, $nextYear, 'close');
            cs_log('Calendar created for property.', [
                'property_id' => $inserted_id,
                'year'        => $nextYear
            ]);

            // Redirección a la edición
            if ($inserted_id) {
                cs_log('Redirecting to property edit page.', [
                    'url' => admin_url('admin.php?page=properties-admin-edit&id=' . $inserted_id)
                ]);
                return [
                    'success'  => true,
                    'redirect' => admin_url('admin.php?page=properties-admin-edit&id=' . $inserted_id)
                ];
            } else {
                cs_log('Property creation succeeded but no ID returned.');
                return [
                    'success' => true,
                    'message' => 'Property created successfully.'
                ];
            }

        } else {
            cs_log('Error: Failed to insert property into database.', [
                'db_error' => $wpdb->last_error
            ]);
            return [
                'success' => false,
                'message' => 'There was an error while creating the property.'
            ];
        }
    }

    public function updateProperty($id, $name = null, $description = null, $thumbnail = null, $code = null, $share_qty = null, $facebook_group = null, $whatsapp_group = null, $title = null, $resell_shares = null, $property_type = null, $bedroom = null, $bathroom = null, $location = null, $gallery = null, $key_features = null, $is_active = 1, $slug = null, $show_shares = 0, $rental_booking_page = null)
    {
        $dto = new UpdatePropertyDto($id, $name, $description, $thumbnail, $code, $share_qty, $facebook_group, $whatsapp_group, $title, $resell_shares, $property_type, $bedroom, $bathroom, $location, $gallery, $key_features, $is_active, $slug, $show_shares, $rental_booking_page);
        return $this->repository->update($dto);
    }

    public function updateOwnersForProperty($propertyId, $ownersByPosition)
    {
        return $this->repository->syncOwners($propertyId, $ownersByPosition);
    }

    public function handleUpdateProperty(int $id, array $data): array
    {
        try {
            // Campos de texto
            $name        = isset($data['name']) ? sanitize_text_field($data['name']) : '';
            $slug        = isset($data['slug']) ? sanitize_text_field($data['slug']) : '';
            $description = isset($data['property_description']) ? wp_kses_post($data['property_description']) : '';
            $thumbnail   = isset($data['property_image']) ? esc_url_raw($data['property_image']) : '';
            $code        = isset($data['code']) ? sanitize_text_field($data['code']) : '';
            $facebook_group  = isset($data['facebook_group']) ? sanitize_text_field($data['facebook_group']) : '';
            $whatsapp_group  = isset($data['whatsapp_group']) ? sanitize_text_field($data['whatsapp_group']) : '';
            $title       = isset($data['title']) ? sanitize_text_field($data['title']) : '';
            $key_features = isset($data['key_features']) ? sanitize_text_field($data['key_features']) : '';
            $rental_booking_page = isset($data['rental_booking_page']) ? sanitize_text_field($data['rental_booking_page']) : '';

            // Campos numéricos
            $share_qty   = isset($data['share_qty']) ? absint($data['share_qty']) : 0;
            $resell_shares = isset($data['resell_shares']) ? absint($data['resell_shares']) : 0;
            $bedroom     = isset($data['bedroom']) ? absint($data['bedroom']) : 0;
            $bathroom    = isset($data['bathroom']) ? absint($data['bathroom']) : 0;

            // Campos de selección
            $property_type = isset($data['property_type']) ? sanitize_text_field($data['property_type']) : '';
            $location      = isset($data['location']) ? sanitize_text_field($data['location']) : '';

            // Checkbox
            $show_shares = isset($data['show_shares']) ? 1 : 0;

            // Owners
            $selected_owners = [];
            for ($i = 1; $i <= $share_qty; $i++) {
                $selected_owners[$i] = isset($data["owner_$i"]) ? absint($data["owner_$i"]) : 0;
            }

            // Gallery
            $gallery = [];
            if (!empty($data['property_gallery'])) {
                $decoded = json_decode(stripslashes($data['property_gallery']), true);
                if (is_array($decoded)) {
                    $gallery = array_map('esc_url_raw', $decoded);
                }
            }

            $updated = $this->updateProperty(
                $id,
                $name,
                $description,
                $thumbnail,
                $code,
                $share_qty,
                $facebook_group,
                $whatsapp_group,
                $title,
                $resell_shares,
                $property_type,
                $bedroom,
                $bathroom,
                $location,
                $gallery,
                $key_features,
                1,
                $slug,
                $show_shares,
                $rental_booking_page
            );

            // Actualizar relación de owners
            $this->updateOwnersForProperty($id, $selected_owners);

            return [
                'success' => true,
                'message' => 'Property updated successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating property: ' . $e->getMessage()
            ];
        }
    }


    public function getAllProperties($is_active = null, $format = 1)
    {
        $properties = $this->repository->getAll($is_active);

        return array_map(function ($property) {
            $owners = $this->repository->getOwnersByPropertyId($property->getId());

            $ownerLinks = array_map(function ($owner) {
                $post_id = $owner->id;

                $title = esc_html($owner->name);
                $permalink = admin_url("admin.php?page=owners-admin-edit&id=$post_id");

                if ($permalink) {
                    return "<a href='" . esc_url($permalink) . "' target='_blank'>{$title}</a>";
                }

                // Si no es un post real, solo muestra el nombre
                return $title;
            }, $owners);

            $ownerList = implode(', ', $ownerLinks);

            return [
                'id'       => $property->getId(),
                'name'     => $property->getName(),
                'code' => $property->getCode(),
                'shares' => $property->getShare(),
                'owners' => $ownerList,
                'status' => $property->getStatus(),
            ];
        }, $properties);
    }

    public function getPropertiesInRelation($owner_id, $format = 1, $per_page = 5, $page_number = 0)
    {
        if ($format == 1) {
            $properties = $this->repository->getRelatedProperties($owner_id, $format);
        } else {
            $properties = $this->repository->getRelatedProperties($owner_id, $format, $per_page, $page_number);
        }

        return array_map(function ($property) {
            $owners = $this->repository->getOwnersByPropertyId($property->getId(), 2);

            return [
                'id' => $property->getId(),
                'name' => $property->getName(),
                'thumbnail' => $property->getThumbnail(),
                'code' => $property->getCode(),
                'property_type' => $property->getPropertyType(),
                'bedroom' => $property->getBedroom(),
                'location' => $property->getLocation(),
                'shares' => $property->getShare(),
                'resell_shares' => $property->getResellShares(),
                'owners' => $owners,
                'slug' => $property->getSlug(),
            ];
        }, $properties);
    }

    public function getProperty($id)
    {
        // convierte y valida
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }
        return $this->repository->find($id);
    }

    public function getBySlug(string $slug)
    {
        return $this->repository->findBySlug($slug);
    }

    public function getOwnerIdsByProperty($id)
    {
        return $this->repository->getOwnerIdsByProperty($id);
    }

    public function setActiveStatus(int $id, int $status): bool
    {
        return $this->repository->updateActiveStatus($id, $status);
    }

    public function countProperties($is_active = null): int
    {
        return $this->repository->countAll($is_active);
    }

    public function getAllPropertiesPaginated($is_active = null, int $per_page = 20, int $page_number = 0)
    {
        $properties = $this->repository->getAll($is_active, $per_page, $page_number);

        return array_map(function ($property) {
            $owners = $this->repository->getOwnersByPropertyId($property->getId());

            $ownerLinks = array_map(function ($owner) {
                $post_id = $owner->id;
                $title   = esc_html($owner->name);
                $permalink = admin_url("admin.php?page=owners-admin-edit&id=$post_id");

                return $permalink
                    ? "<a href='" . esc_url($permalink) . "' target='_blank'>{$title}</a>"
                    : $title;
            }, $owners);

            return [
                'id'     => $property->getId(),
                'name'   => $property->getName(),
                'code'   => $property->getCode(),
                'shares' => $property->getShare(),
                'owners' => implode(', ', $ownerLinks),
                'status' => $property->getStatus(),
            ];
        }, $properties);
    }

    public function deletePropertyCascade(int $propertyId): bool
    {
        global $wpdb;

        // 1) obtener calendarios asociados a la propiedad
        $calendarIds = $this->repository->getCalendarIdsByProperty($propertyId);

        // Intentar transacción (si InnoDB)
        $wpdb->query('START TRANSACTION');

        // 2) bookings de esos calendarios
        $ok = $this->repository->deleteBookingsByCalendarIds($calendarIds);
        if ($ok === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        // 3) calendarios
        $ok = $this->repository->deleteCalendarsByProperty($propertyId);
        if ($ok === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        // 4) enlaces owner ↔ property
        $ok = $this->repository->deleteOwnerLinksByProperty($propertyId);
        if ($ok === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        // 5) operaciones de la propiedad (NUEVO)
        $ok = $this->repository->deleteOperationsByProperty($propertyId);
        if ($ok === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        // 6) propiedad
        $ok = $this->repository->delete($propertyId);
        if ($ok === false) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        $wpdb->query('COMMIT');
        return true;
    }
    
}
