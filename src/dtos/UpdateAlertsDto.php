<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateAlertsDto extends BaseDto {

    private string $id;
    protected ?string $alert;

    public function __construct($id, $alert = null){
        $this->id = $id;
        $this->alert = $alert;
    }

    public function getId(){
        return $this->id;
    }

}