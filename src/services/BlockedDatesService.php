<?php

require_once __DIR__ . '/../repositories/BlockedDatesRepository.php';

class BlockedDatesService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new BlockedDatesRepository();
    }

    public function createBlockedDate($calendar_id, $date)
    {
        $dto = new CreateBlockDateDto($calendar_id, $date);
        return $this->repository->insert($dto);
    }

    public function getBlockedDatesByCalendarId($calendar_id)
    {
        $dates = $this->repository->getAll($calendar_id);
        return $dates;
    }

    public function getByCalendar(int $calendar_id): array
    {
        global $wpdb;
        $rows = $wpdb->get_col(
            $wpdb->prepare("SELECT `date` FROM `cs_blocked_dates` WHERE `calendar_id` = %d", $calendar_id)
        );
        // Devuelve strings 'YYYY-MM-DD'
        return array_values(array_unique(array_map('strval', $rows)));
    }
    
}
