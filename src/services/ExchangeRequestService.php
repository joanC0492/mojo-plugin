<?php

require_once __DIR__ . '/../repositories/OwnerRepository.php';
require_once __DIR__ . '/../repositories/ExchangeRequestRepository.php';
require_once __DIR__ . '/../repositories/BookingRepository.php';

class ExchangeRequestService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ExchangeRequestRepository();
    }

    public function selectRequest($id)
    {
        return $this->repository->find($id);
    }

    public function createExchangeRequest($id_calendar, $from_owner, $to_owner, $start_from, $end_from, $start_to, $end_to, $status = 'pending')
    {
        $dto = new CreateExchangeRequestDto($id_calendar, $from_owner, $to_owner, $start_from, $end_from, $start_to, $end_to, $status);
        return $this->repository->insert($dto);
    }

    public function updateExchangeRequest($id, $id_calendar = null, $from_owner = null, $to_owner = null, $start_from = null, $end_from = null, $start_to = null, $end_to = null, $status = null)
    {
        $dto = new UpdateExchangeRequestDto($id, $id_calendar, $from_owner, $to_owner, $start_from, $end_from, $start_to, $end_to, $status);
        return $this->repository->update($dto);
    }

    public function getRequestByOwner($owner_id, $id_calendar, $origin = 'from')
    {
        $requests = $this->repository->getAllByOwner($owner_id, $id_calendar, $origin);

        if (empty($requests)) {
            return [];
        }

        return array_map(function ($request) {
            $owner_repository = new OwnerRepository();
            $from_owner = $owner_repository->find($request->getFromOwner());
            $to_owner = $owner_repository->find($request->getToOwner());

            return [
                'id' => $request->getId(),
                'from_owner' => $from_owner,
                'to_owner' => $to_owner,
                'start_from' => $request->getStartFrom(),
                'end_from' => $request->getEndFrom(),
                'start_to' => $request->getStartTo(),
                'end_to' => $request->getEndTo(),
                'status' => $request->getStatus()
            ];
        }, $requests);
    }

    public function isDateBlockedByExchange(
        int $calendarId,
        string $startDate,
        string $endDate
    ): bool {
        $exchangeRows = $this->repository->getPendingByCalendar($calendarId);

        if (empty($exchangeRows)) {
            return false;
        }

        foreach ($exchangeRows as $exchange) {
            if (
                cs_ranges_overlap($startDate, $endDate, $exchange['f_start'], $exchange['f_end']) ||
                cs_ranges_overlap($startDate, $endDate, $exchange['t_start'], $exchange['t_end'])
            ) {
                return true;
            }
        }

        return false;
    }
}
