<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateExchangeRequestDto extends BaseDto {

    private int $id;
    protected ?int $id_calendar;
    protected ?int $from_owner;
    protected ?int $to_owner;
    protected ?string $start_from;
    protected ?string $end_from;
    protected ?string $start_to;
    protected ?string $end_to;
    protected ?string $status;

    public function __construct($id, $id_calendar = null, $from_owner = null, $to_owner = null, $start_from = null, $end_from = null, $start_to = null, $end_to = null, $status = null){
        $this->id = $id;
        $this->id_calendar = $id_calendar;
        $this->from_owner = $from_owner;
        $this->to_owner = $to_owner;
        $this->start_from = $start_from;
        $this->end_from = $end_from;
        $this->start_to = $start_to;
        $this->end_to = $end_to;
        $this->status = $status;
    }

    public function getId(){
        return $this->id;
    }

}