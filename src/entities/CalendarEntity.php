<?php

class CalendarEntity {

    private int $id;
    private int $property_id;
    private ?int $year;
    private ?string $owners_priority;
    private ?string $round;
    private ?string $turn;
    private ?string $status;
    private ?string $colors_order;
    private ?int $toggle_download_calendar;

    public function __construct($id, $property_id, $year = null, $owners_priority = null, $round = null, $turn = null, $status = null, $colors_order = null, $toggle_download_calendar = null){
        $this->id = $id;
        $this->property_id = $property_id;
        $this->year = $year;
        $this->owners_priority = $owners_priority;
        $this->round = $round;
        $this->turn = $turn;
        $this->status = $status;
        $this->colors_order = $colors_order;
        $this->toggle_download_calendar = $toggle_download_calendar;
    }

    public function getId(){
        return $this->id;
    }

    public function getPropertyId(){
        return $this->property_id;
    }
    
    public function getYear(){
        return $this->year;
    }

    public function getOwnersPriority(){
        return $this->owners_priority;
    }

    public function getRound(){
        return $this->round;
    }

    public function getTurn(){
        return $this->turn;
    }

    public function getStatus(){
        return $this->status;
    }

    public function getColorsOrder(){
        return $this->colors_order;
    }

    public function getToggleDownloadCalendar(){
        return $this->toggle_download_calendar;
    }

    public function setPropertyId($property_id){
        $this->property_id = $property_id;
    }
    
    public function setYear($year){
        $this->year = $year;
    }

    public function setOwnersPriority($owners_priority){
        $this->owners_priority = $owners_priority;
    }

    public function setRound($round){
        $this->round = $round;
    }

    public function setTurn($turn){
        $this->turn = $turn;
    }

    public function setStatus($status){
        $this->status = $status;
    }

    public function setColorsOrder($colors_order){
        $this->colors_order = $colors_order;
    }
    
    public function setToggleDownloadCalendar($toggle_download_calendar){
        $this->toggle_download_calendar = $toggle_download_calendar;
    }

}