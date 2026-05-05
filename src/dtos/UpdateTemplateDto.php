<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateTemplateDto extends BaseDto {

    private string $id;
    protected ?string $subject;
    protected ?string $body;
    protected ?string $email_enabled;
    protected ?string $message;
    protected ?string $push_enabled;

    public function __construct($id, $subject = null, $body = null, $email_enabled = 0, $message = null, $push_enabled = 0){
        $this->id = $id;
        $this->subject = $subject;
        $this->body = $body;
        $this->email_enabled = $email_enabled;
        $this->message = $message;
        $this->push_enabled = $push_enabled;
    }

    public function getId(){
        return $this->id;
    }

}