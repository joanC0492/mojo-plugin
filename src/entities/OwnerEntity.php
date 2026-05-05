<?php

class OwnerEntity {

    private int $id;
    private string $name;
    private string $email;
    private string $password;
    private ?string $phone;
    private ?string $visible_info;
    private ?int $is_active;

    public function __construct($id, $name, $email, $password, $phone = null, $visible_info = 1, $is_active = 1){
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

    public function getName(){
        return $this->name;
    }
    
    public function getEmail(){
        return $this->email;
    }

    public function getPassword(){
        return $this->password;
    }

    public function getPhone(){
        return $this->phone;
    }

    public function getVisibleInfo(){
        return $this->visible_info;
    }

    public function getStatus(){
        return $this->is_active;
    }

    public function setName($name){
        $this->name = $name;
    }
    
    public function setEmail($email){
        $this->email = $email;
    }

    public function setPassword($password){
        $this->password = $password;
    }

    public function setPhone($phone){
        $this->phone = $phone;
    }

    public function setVisibleInfo($visible_info){
        $this->visible_info = $visible_info;
    }

    public function setStatus($is_active){
        $this->is_active = $is_active;
    }
    
}