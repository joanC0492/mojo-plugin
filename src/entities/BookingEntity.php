<?php

class BookingEntity {

    private int $id;
    private int $calendar_id;
    private ?string $start_date;
    private ?string $end_date;
    private ?int $owner_id;
    private ?int $owner_position;
    private ?int $in_round;
    private ?string $type;

    public function __construct($id, $calendar_id, $start_date = null, $end_date = null, $owner_id = null, $owner_position = null, $in_round = null, $type = null){
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

    public function getCalendarId(){
        return $this->calendar_id;
    }
    
    public function getStartDate(){
        return $this->start_date;
    }

    public function getEndDate(){
        return $this->end_date;
    }

    public function getOwnerId(){
        return $this->owner_id;
    }

    public function getOwnerPosition(){
        return $this->owner_position;
    }

    public function getInRound(){
        return $this->in_round;
    }

    public function getType(){
        return $this->type;
    }

    public function setCalendarId($calendar_id){
        $this->calendar_id = $calendar_id;
    }
    
    public function setStartDate($start_date){
        $this->start_date = $start_date;
    }

    public function setEndDate($end_date){
        $this->end_date = $end_date;
    }

    public function setOwnerId($owner_id){
        $this->owner_id = $owner_id;
    }

    public function setOwnerPosition($owner_position){
        $this->owner_position = $owner_position;
    }

    public function setInRound($in_round){
        $this->in_round = $in_round;
    }

    public function setType($type){
        $this->type = $type;
    }
    
}