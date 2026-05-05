<?php

require_once __DIR__ . '/../dtos/UpdateTemplateDto.php';
require_once __DIR__ . '/../repositories/TemplateRepository.php';

class TemplateService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new TemplateRepository();
    }

    public function updateNotification($id, $subject = null, $body = null, $email_enabled = 0, $message = null, $push_enabled = 0)
    {
        $dto = new UpdateTemplateDto($id, $subject, $body, $email_enabled, $message, $push_enabled);
        return $this->repository->update($dto);
    }

    public function getNotifications($id)
    {
        return $this->repository->find($id);
    }

}
