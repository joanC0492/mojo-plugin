<?php

class PropertyEntity
{


    private int $id;
    private ?string $name;
    private ?string $description;
    private ?string $thumbnail;
    private ?string $code;
    private ?string $share_qty;
    private ?string $facebook_group;
    private ?string $whatsapp_group;

    private ?string $title;
    private ?string $resell_shares;
    private ?string $property_type;
    private ?string $bedroom;
    private ?string $bathroom;
    private ?string $location;

    private ?string $gallery;
    private ?string $key_features;
    private ?int $is_active;
    private ?string $slug;
    private ?int $show_shares;
    private ?string $rental_booking_page;

    public function __construct($id, $name = null, $description = null, $thumbnail = null, $code = null, $share_qty = null, $facebook_group = null, $whatsapp_group = null, $title = null, $resell_shares = null, $property_type = null, $bedroom = null, $bathroom = null, $location = null, $gallery = null, $key_features = null, $is_active = 1, $slug = null, $show_shares = 0, $rental_booking_page = null)
    {
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
        $this->gallery = $gallery;
        $this->key_features = $key_features;
        $this->is_active = $is_active;
        $this->slug = $slug;
        $this->show_shares = $show_shares;
        $this->rental_booking_page = $rental_booking_page;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getShare()
    {
        return $this->share_qty;
    }

    public function getFbGroup()
    {
        return $this->facebook_group;
    }

    public function getWspGroup()
    {
        return $this->whatsapp_group;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getResellShares()
    {
        return $this->resell_shares;
    }

    public function getPropertyType()
    {
        return $this->property_type;
    }

    public function getBedroom()
    {
        return $this->bedroom;
    }

    public function getBathroom()
    {
        return $this->bathroom;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getGallery()
    {
        return $this->gallery;
    }

    public function getKeyFeatures()
    {
        return $this->key_features;
    }

    public function getStatus()
    {
        return $this->is_active;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getShowShares()
    {
        return $this->show_shares;
    }

    public function getRentalBookingPage()
    {
        return $this->rental_booking_page;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function setShare($share_qty)
    {
        $this->share_qty = $share_qty;
    }

    public function setFbGroup($facebook_group)
    {
        $this->facebook_group = $facebook_group;
    }

    public function setWspGroup($whatsapp_group)
    {
        $this->whatsapp_group = $whatsapp_group;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setResellShares($resell_shares)
    {
        $this->resell_shares = $resell_shares;
    }

    public function setPropertyType($property_type)
    {
        $this->property_type = $property_type;
    }

    public function setBedrooms($bedroom)
    {
        $this->bedroom = $bedroom;
    }

    public function setBathrooms($bathroom)
    {
        $this->bathroom = $bathroom;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function setGallery($gallery)
    {
        $this->gallery = $gallery;
    }

    public function setKeyFeatures($key_features)
    {
        $this->key_features = $key_features;
    }

    public function setStatus($is_active)
    {
        $this->is_active = $is_active;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function setShowShares($show_shares)
    {
        $this->show_shares = $show_shares;
    }

    public function setRentalBookingPage($rental_booking_page)
    {
        $this->rental_booking_page = $rental_booking_page;
    }
}
