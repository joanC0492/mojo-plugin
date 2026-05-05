<?php

require_once __DIR__.'/../bases/BaseDto.php';

class CreatePropertyDto extends BaseDto
{
    protected string $name;
    protected string $description;
    protected string $thumbnail;
    protected string $code;
    protected int $share_qty;
    protected string $slug;

    public function __construct($name, $description, $thumbnail, $code, $share_qty, $slug)
    {
        $this->name = $name;
        $this->description = $description;
        $this->thumbnail = $thumbnail;
        $this->code = $code;
        $this->share_qty = $share_qty;
        $this->slug = $slug;
    }

}