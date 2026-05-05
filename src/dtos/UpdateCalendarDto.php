<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateCalendarDto extends BaseDto {

    private int $id;
    protected ?int $property_id;
    protected ?int $year;
    protected ?string $owners_priority;
    protected ?string $round;
    protected ?string $turn;
    protected ?string $status;
    protected ?string $colors_order;
    protected ?int $toggle_download_calendar;

    public function __construct($id, $property_id = null, $year = null, $owners_priority = null, $round = null, $turn = null, $status = null, $colors_order = null, $toggle_download_calendar = null){
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

}