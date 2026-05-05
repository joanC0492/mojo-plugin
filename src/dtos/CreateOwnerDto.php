<?php

require_once __DIR__.'/../bases/BaseDto.php';

class CreateOwnerDto extends BaseDto
{
    protected string $name;
    protected string $email;
    protected string $password;
    protected string $phone;
    protected string $visible_info;
    protected string $is_active;

    public function __construct($name, $email, $password, $phone, $visible_info, $is_active)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phone = $phone;
        $this->visible_info = $visible_info;
        $this->is_active = $is_active;
    }

}