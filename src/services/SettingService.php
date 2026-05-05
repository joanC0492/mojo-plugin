<?php

require_once __DIR__ . '/../dtos/UpdateSettingDto.php';
require_once __DIR__ . '/../repositories/SettingRepository.php';

class SettingService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new SettingRepository();
    }

    public function updateSetting($id, $admin_name = null, $admin_email = null, $admin_phone = null, $sale_name = null, $sale_email = null, $sale_phone = null, $request_email = null, $rent_email = null, $exchange_email = null, $contact_us_email = null, $facebook = null, $instagram = null, $linkedin = null, $youtube = null, $phone_footer = null, $mail_footer = null, $direction_footer = null, $bgcolor_pdf = SELECTED_COLOR_IN_CALENDAR_DEFAULT)
    {
        $dto = new UpdateSettingDto($id, $admin_name, $admin_email, $admin_phone, $sale_name, $sale_email, $sale_phone, $request_email, $rent_email, $exchange_email, $contact_us_email, $facebook, $instagram, $linkedin, $youtube, $phone_footer, $mail_footer, $direction_footer, $bgcolor_pdf);
        return $this->repository->update($dto);
    }

    public function getContacts($id)
    {
        return $this->repository->find($id);
    }

}
