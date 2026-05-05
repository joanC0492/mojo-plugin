<?php

require_once __DIR__ . '/../repositories/NotificationRepository.php';

class NotificationService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new NotificationRepository();
    }

    public function createNotification($owner_id, $notification)
    {
        if(intval($owner_id) != NOT_ASSIGNED_ID){
            $dto = new CreateNotificationDto($owner_id, $notification);
            return $this->repository->insert($dto);
        }else{
            return true;
        }
    }

    public function getNotifications($id, $limit = 5, $offset = 0)
    {
        return $this->repository->findByUser($id, $limit, $offset);
    }
    
}
