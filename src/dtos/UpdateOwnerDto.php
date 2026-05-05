<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateOwnerDto extends BaseDto {

    private string $id;
    protected ?string $name;
    protected ?string $email;
    protected ?string $password;
    protected ?string $phone;
    protected ?string $visible_info;
    protected ?string $is_active;

    public function __construct($id, $name = null, $email = null, $password = null, $phone = null, $visible_info = 1, $is_active = 1){
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phone = $phone;
        $this->visible_info = $visible_info;
        $this->is_active = $is_active;
    }

    public function getId(){
        return $this->id;
    }
    
}