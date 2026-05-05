<?php

require_once __DIR__ . '/../bases/BaseDto.php';

class CreateBookingDto extends BaseDto
{
    protected int $calendar_id;
    protected ?string $start_date;
    protected ?string $end_date;
    protected ?int $owner_id;
    protected ?int $owner_position;
    protected ?int $in_round;
    protected ?string $type;

    public function __construct($calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $type)
    {
        $this->calendar_id = $calendar_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->owner_id = $owner_id;
        $this->owner_position = $owner_position;
        $this->in_round = $in_round;
        $this->type = $type;
    }

    public function getStartDate()
    {
        return $this->start_date;
    }
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }

    public function getCalendarId()
    {
        return $this->calendar_id;
    }
    public function getOwnerId()
    {
        return $this->owner_id;
    }
    public function getOwnerPosition()
    {
        return $this->owner_position;
    }
    public function getInRound()
    {
        return $this->in_round;
    }
}
