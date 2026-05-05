<?php

require_once __DIR__.'/../bases/BaseDto.php';

class CreateCalendarDto extends BaseDto
{
    protected int $property_id;
    protected ?int $year;
    protected ?string $status;

    public function __construct($property_id, $year, $status)
    {
        $this->property_id = $property_id;
        $this->year = $year;
        $this->status = $status;
    }

}