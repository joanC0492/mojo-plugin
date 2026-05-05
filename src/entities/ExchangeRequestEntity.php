<?php

class ExchangeRequestEntity
{

    private int $id;
    private ?int $id_calendar;
    private ?int $from_owner;
    private ?int $to_owner;
    private ?string $start_from;
    private ?string $end_from;
    private ?string $start_to;
    private ?string $end_to;
    private ?string $status;

    public function __construct($id, $id_calendar, $from_owner, $to_owner, $start_from, $end_from, $start_to, $end_to, $status)
    {
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

    public function getId()
    {
        return $this->id;
    }

    public function getIdCalendar()
    {
        return $this->id_calendar;
    }

    public function getFromOwner()
    {
        return $this->from_owner;
    }

    public function getToOwner()
    {
        return $this->to_owner;
    }

    public function getStartFrom()
    {
        return $this->start_from;
    }

    public function getEndFrom()
    {
        return $this->end_from;
    }

    public function getStartTo()
    {
        return $this->start_to;
    }

    public function getEndTo()
    {
        return $this->end_to;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setIdCalendar($id_calendar)
    {
        $this->id_calendar = $id_calendar;
    }

    public function setFromOwner($from_owner)
    {
        $this->from_owner = $from_owner;
    }

    public function setToOwner($to_owner)
    {
        $this->to_owner = $to_owner;
    }

    public function setStartFrom($start_from)
    {
        $this->start_from = $start_from;
    }

    public function setEndFrom($end_from)
    {
        $this->end_from = $end_from;
    }

    public function setStartTo($start_to)
    {
        $this->start_to = $start_to;
    }

    public function setEndTo($end_to)
    {
        $this->end_to = $end_to;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
}
