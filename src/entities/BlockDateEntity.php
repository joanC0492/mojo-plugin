<?php

class BlockDateEntity {

    private int $id;
    private int $calendar_id;
    private ?string $date;

    public function __construct($id, $calendar_id, $date = null){
        $this->id = $id;
        $this->calendar_id = $calendar_id;
        $this->date = $date;
    }

    public function getId(){
        return $this->id;
    }

    public function getCalendarId(){
        return $this->calendar_id;
    }
    
    public function getDate(){
        return $this->date;
    }

    public function setCalendarId($calendar_id){
        $this->calendar_id = $calendar_id;
    }
    
    public function setDate($date){
        $this->date = $date;
    }
    
}