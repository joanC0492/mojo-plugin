<?php

class CreatePropertyOperationDto extends BaseDto
{
    protected int $property_id;
    protected string $operation_date;
    protected string $title;
    protected string $description;
    protected string $type;

    public function __construct($property_id, $operation_date, $title, $description, $type)
    {
        $this->property_id    = $property_id;
        $this->operation_date = $operation_date;
        $this->title          = $title;
        $this->description    = $description;
        $this->type           = $type;
    }
}