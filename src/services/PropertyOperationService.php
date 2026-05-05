<?php

require_once __DIR__ . '/../repositories/PropertyOperationRepository.php';

class PropertyOperationService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new PropertyOperationRepository();
    }

    public function getAllProperties($id)
    {
        return $this->repository->getAll($id);
    }

    public function insertOperation($property_id, $date, $title, $description, $type)
    {
        $dto = new CreatePropertyOperationDto(
            $property_id,
            $date,
            $title,
            $description,
            $type
        );

        return $this->repository->insert($dto);
    }

    public function deleteOperation(int $id)
    {
        return $this->repository->delete($id);
    }


}

