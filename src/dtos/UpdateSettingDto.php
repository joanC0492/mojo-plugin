<?php

require_once __DIR__.'/../bases/BaseDto.php';

class UpdateSettingDto extends BaseDto {

    private string $id;
    protected ?string $admin_name;
    protected ?string $admin_email;
    protected ?string $admin_phone;
    protected ?string $sale_name;
    protected ?string $sale_email;
    protected ?string $sale_phone;
    protected ?string $request_email;
    protected ?string $rent_email;
    protected ?string $exchange_email;
    protected ?string $contact_us_email;

    protected ?string $facebook;
    protected ?string $instagram;
    protected ?string $linkedin;
    protected ?string $youtube;

    protected ?string $phone_footer;
    protected ?string $mail_footer;
    protected ?string $direction_footer;

    protected ?string $bgcolor_pdf;

    public function __construct($id, $admin_name = null, $admin_email = null, $admin_phone = null, $sale_name = null, $sale_email = null, $sale_phone = null, $request_email = null, $rent_email = null, $exchange_email = null, $contact_us_email = null, $facebook = null, $instagram = null, $linkedin = null, $youtube = null, $phone_footer = null, $mail_footer = null, $direction_footer = null, $bgcolor_pdf = SELECTED_COLOR_IN_CALENDAR_DEFAULT){
        $this->id = $id;
        $this->admin_name = $admin_name;
        $this->admin_email = $admin_email;
        $this->admin_phone = $admin_phone;
        $this->sale_name = $sale_name;
        $this->sale_email = $sale_email;
        $this->sale_phone = $sale_phone;
        $this->request_email = $request_email;
        $this->rent_email = $rent_email;
        $this->exchange_email = $exchange_email;
        $this->contact_us_email = $contact_us_email;
        
        $this->facebook = $facebook;
        $this->instagram = $instagram;
        $this->linkedin = $linkedin;
        $this->youtube = $youtube;

        $this->phone_footer = $phone_footer;
        $this->mail_footer = $mail_footer;
        $this->direction_footer = $direction_footer;

        $this->bgcolor_pdf = $bgcolor_pdf;
    }

    public function getId(){
        return $this->id;
    }

}