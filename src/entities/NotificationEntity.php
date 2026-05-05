<?php

class NotificationEntity {

    private int $id;
    private int $owner_id;
    private ?string $notification;

    public function __construct($id, $owner_id, $notification = null){
        $this->id = $id;
        $this->owner_id = $owner_id;
        $this->notification = $notification;
    }

    public function getId(){
        return $this->id;
    }

    public function getOwnerId(){
        return $this->owner_id;
    }
    
    public function getNotification(){
        return $this->notification;
    }
    
}