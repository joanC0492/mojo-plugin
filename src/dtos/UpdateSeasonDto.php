<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateSeasonDto extends BaseDto {

    private string $id;
    protected ?string $date;
    protected ?string $type;
    protected ?int $year;

    public function __construct($id, $date = null, $type = null, $year = null){
        $this->id = $id;
        $this->date = $date;
        $this->type = $type;
        $this->year = $year;
    }

    public function getId(){
        return $this->id;
    }

}