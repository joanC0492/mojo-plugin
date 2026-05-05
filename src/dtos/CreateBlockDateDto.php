<?php

require_once __DIR__ . '/../bases/BaseDto.php';

class CreateBlockDateDto extends BaseDto
{
    protected int $calendar_id;
    protected ?string $date;

    public function __construct($calendar_id, $date)
    {
        $this->calendar_id = $calendar_id;
        $this->date = $date;
    }

    public function getDate()
    {
        return $this->date;
    }
    
    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getCalendarId()
    {
        return $this->calendar_id;
    }
}
