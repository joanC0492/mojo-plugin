<?php

require_once __DIR__.'/../bases/BaseDto.php';

class CreateSeasonDto extends BaseDto
{
    protected string $date;
    protected string $type;
    protected int $year;

    public function __construct($date, $type, $year)
    {
        $this->date = $date;
        $this->type = $type;
        $this->year = $year;
    }

}