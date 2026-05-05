<?php

require_once __DIR__ . '/../dtos/UpdateAlertsDto.php';
require_once __DIR__ . '/../repositories/AlertsRepository.php';

class AlertsService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new AlertsRepository();
    }

    public function updateAlerts($id, $alert = null)
    {
        $dto = new UpdateAlertsDto($id, $alert); 
        return $this->repository->update($dto);
    }

    public function getAlerts($id)
    {
        return $this->repository->find($id);
    }

}
