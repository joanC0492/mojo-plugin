<?php

require_once __DIR__ . '/../repositories/OwnerRepository.php';
require_once __DIR__ . '/TemplateService.php';

class OwnerService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new OwnerRepository();
    }

    public function createOwner($name, $email, $password, $phone, $visible_info = 1, $is_active = 1)
    {
        $dto = new CreateOwnerDto($name, $email, $password, $phone, $visible_info, $is_active);
        return $this->repository->insert($dto);
    }

    public function handleCreateOwner(array $data): array
    {
        $name     = sanitize_text_field($data['name'] ?? '');
        $email    = sanitize_text_field($data['email'] ?? '');
        $phone    = sanitize_text_field($data['phone'] ?? '');
        $password = sanitize_text_field($data['password'] ?? '');

        // Verificar email duplicado
        if ($this->repository->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'An Owner with this Email already exists.'
            ];
        }

        $inserted = $this->createOwner($name, $email, $password, $phone);

        if ($inserted) {
            return [
                'success' => true,
                'message' => 'Owner created successfully.'
            ];
        }

        return [
            'success' => false,
            'message' => 'There was an error creating the Owner.'
        ];
    }

    public function updateOwner($id, $name = null, $email = null, $password = null, $phone = null, $visible_info = 1, $is_active = 1)
    {
        $dto = new UpdateOwnerDto($id, $name, $email, $password, $phone, $visible_info, $is_active);
        return $this->repository->update($dto);
    }

    // total para paginación y contadores
    public function countOwners($is_active = null): int
    {
        return $this->repository->countAll($is_active);
    }

    // página paginada (usa tu repo actual que ya acepta itemsPerPage/pageNumber)
    public function getAllOwnersPaginated($is_active = null, int $itemsPerPage = 20, int $pageNumber = 0): array
    {
        $owners = $this->repository->getAll($is_active, $itemsPerPage, $pageNumber);

        return array_map(function ($owner) {
            $properties = $this->repository->getPropertiesByOwnerId($owner->getId());

            $propertyLinks = array_map(function ($property) {
                $post_id   = $property->id;
                $title     = esc_html($property->name);
                $permalink = admin_url("admin.php?page=properties-admin-edit&id=$post_id");
                return $permalink ? "<a href='" . esc_url($permalink) . "' target='_blank'>{$title}</a>" : $title;
            }, $properties);

            return [
                'id'         => $owner->getId(),
                'name'       => $owner->getName(),
                'email'      => $owner->getEmail(),
                'password'   => $owner->getPassword(),
                'phone'      => $owner->getPhone(),
                'properties' => implode(', ', $propertyLinks),
                'status'     => $owner->getStatus(),
            ];
        }, $owners);
    }

    public function getAllOwners($is_active = null, $pagination = 20)
    {
        $owners = $this->repository->getAll($is_active, $pagination);

        return array_map(function ($owner) {
            $properties = $this->repository->getPropertiesByOwnerId($owner->getId());

            $propertyLinks = array_map(function ($property) {
                $post_id = $property->id;
                $title = esc_html($property->name);

                $permalink = admin_url("admin.php?page=properties-admin-edit&id=$post_id");

                if ($permalink) {
                    return "<a href='" . esc_url($permalink) . "' target='_blank'>{$title}</a>";
                }

                // Si no es un post real, solo muestra el nombre
                return $title;
            }, $properties);

            $propertyList = implode(', ', $propertyLinks);

            return [
                'id'       => $owner->getId(),
                'name'     => $owner->getName(),
                'email'    => $owner->getEmail(),
                'password' => $owner->getPassword(),
                'phone'    => $owner->getPhone(),
                'properties' => $propertyList,
                'status' => $owner->getStatus(),
            ];
        }, $owners);

        // error_log(print_r($owners_array, true));
    }

    public function getOwner($id, $format = true)
    {
        if ($format) {
            return $this->repository->find($id);
        } else {
            return $this->repository->findLikeArray($id);
        }
    }

    public function setActiveStatus(int $id, int $status): bool
    {
        return $this->repository->updateActiveStatus($id, $status);
    }

    public function getOwnersByProperty($id)
    {
        return $this->repository->getOwnersByProperty($id);
    }

    public function sendEmailForOwner($id)
    {
        $template_service = new TemplateService();
        $templates6 = $template_service->getNotifications(6);

        $owner = $this->repository->find($id);
        if ($owner) {

            $email = $owner->getEmail();

            $placeholders = [
                '[NAME]'     => $owner->getName(),
                '[EMAIL]'    => $email,
                '[PASSWORD]' => $owner->getPassword()
            ];

            if ($templates6->email_enabled) {

                $body = $templates6->body ?? '';

                $active_placeholders1 = array_filter($placeholders, function ($key) use ($body) {
                    return strpos($body, $key) !== false;
                }, ARRAY_FILTER_USE_KEY);

                $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $body);

                try {
                    $sent = send_notification_email($templates6->subject, $message, $email);
                    cs_log('📧 Sending welcome email', [
                        'to' => $email,
                        'sent' => (bool)$sent
                    ]);
                } catch (Throwable $e) {
                    cs_log('❌ Error sending welcome email', [
                        'to' => $email,
                        'error' => $e->getMessage()
                    ]);
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // dentro de class OwnerService

    public function isLinkedToProperty(int $id): bool
    {
        // Usa el repo existente que ya trae propiedades por owner_id
        $props = $this->repository->getPropertiesByOwnerId($id);
        return !empty($props);
    }

    public function deleteOwner(int $id): bool
    {
        return $this->repository->delete($id);
    }
}