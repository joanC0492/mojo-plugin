<?php

require_once __DIR__.'/../bases/BaseDto.php';

class CreateNotificationDto extends BaseDto
{
    protected int $owner_id;
    protected ?string $notification;

    public function __construct($owner_id, $notification)
    {
        $this->owner_id = $owner_id;
        $this->notification = $notification;
    }

}