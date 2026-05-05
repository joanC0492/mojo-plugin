<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdatePropertyDto extends BaseDto {

    private int $id;
    protected ?string $name;
    protected ?string $description;
    protected ?string $thumbnail;
    protected ?string $code;
    protected ?string $share_qty;
    protected ?string $facebook_group;
    protected ?string $whatsapp_group;
    protected ?string $title;
    protected ?string $resell_shares;
    protected ?string $property_type;
    protected ?string $bedroom;
    protected ?string $bathroom;
    protected ?string $location;
    protected ?string $gallery;
    protected ?string $key_features;
    protected ?int $is_active;
    protected ?string $slug;
    protected ?int $show_shares;
    protected ?string $rental_booking_page;

    public function __construct($id, $name = null, $description = null, $thumbnail = null, $code = null, $share_qty = null, $facebook_group = null, $whatsapp_group = null, $title = null, $resell_shares = null, $property_type = null, $bedroom = null, $bathroom = null, $location = null, $gallery = null, $key_features = null, $is_active = 1, $slug = null, $show_shares = 0, $rental_booking_page = null){
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->thumbnail = $thumbnail;
        $this->code = $code;
        $this->share_qty = $share_qty;
        $this->facebook_group = $facebook_group;
        $this->whatsapp_group = $whatsapp_group;
        $this->title = $title;
        $this->resell_shares = $resell_shares;
        $this->property_type = $property_type;
        $this->bedroom = $bedroom;
        $this->bathroom = $bathroom;
        $this->location = $location;
        $this->gallery = is_array($gallery) ? json_encode($gallery) : $gallery;
        $this->key_features = $key_features;
        $this->is_active = $is_active;
        $this->slug = $slug;
        $this->show_shares = $show_shares;
        $this->rental_booking_page = $rental_booking_page;
    }

    public function getId(){
        return $this->id;
    }

}