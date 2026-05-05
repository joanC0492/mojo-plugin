<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateBookingDto extends BaseDto {

    private int $id;
    protected ?int $calendar_id;
    protected ?string $start_date;
    protected ?string $end_date;
    protected ?int $owner_id;
    protected ?int $owner_position;
    protected ?int $in_round;
    protected ?string $type;

    public function __construct($id, $calendar_id = null, $start_date = null, $end_date = null, $owner_id = null, $owner_position = null, $in_round = null, $type = null){
        $this->id = $id;
        $this->calendar_id = $calendar_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->owner_id = $owner_id;
        $this->owner_position = $owner_position;
        $this->in_round = $in_round;
        $this->type = $type;
    }

    public function getId(){
        return $this->id;
    }

}