<?php

require_once __DIR__ . '/../repositories/OwnerRepository.php';
require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../repositories/CalendarRepository.php';

class BookingService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new BookingRepository();
    }

    public function createBooking($calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $type)
    {
        $dto = new CreateBookingDto($calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $type);
        return $this->repository->insertOrMerge($dto);
    }

    public function createRentedBooking($calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $type)
    {
        $dto = new CreateBookingDto($calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $type);
        return $this->repository->insert($dto);
    }

    public function updateBooking($id, $calendar_id = null, $start_date = null, $end_date = null, $owner_id = null, $owner_position = null, $in_round = null, $type = null)
    {
        $dto = new UpdateBookingDto($id, $calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $type);
        return $this->repository->update($dto);
    }

    public function getBookingByYear($calendar_id, $uniqueColor = '', $format = 1)
    {
        $calendar_repository = new CalendarRepository();
        $calendar = $calendar_repository->find($calendar_id);

        $colors_only = COLOR_CODES;

        if (!is_null($calendar->getColorsOrder())) {
            $colors_order = json_decode(stripslashes($calendar->getColorsOrder()), true);
            $colors_only = array_values($colors_order);
        }

        // Trae todos los períodos de la BD
        $booking = $this->repository->getAll($calendar_id);

        if ($format == 1) {
            if (!empty($booking)) {

                $owner_repository = new OwnerRepository();

                /**
                 * 1) ORDENAR por start_date
                 */
                usort($booking, function ($a, $b) {
                    return strcmp($a->getStartDate(), $b->getStartDate());
                });

                /**
                 * 2) FUSIONAR SOLO SI hay overlap o mismo día (NO día siguiente)
                 */
                $merged = [];
                foreach ($booking as $p) {
                    $key = $p->getOwnerId() . '|' . $p->getOwnerPosition() . '|' . $p->getType();

                    if (!isset($merged[$key])) {
                        // Primer bloque para este key
                        $merged[$key] = [[
                            'id'             => $p->getId(),
                            'owner_id'       => $p->getOwnerId(),
                            'owner_position' => $p->getOwnerPosition(),
                            'type'           => $p->getType(),
                            'in_round'       => $p->getInRound(),
                            'start'          => $p->getStartDate(),
                            'end'            => $p->getEndDate(),
                        ]];
                        continue;
                    }

                    // Tomar la última ventana de este key
                    $lastIndex = count($merged[$key]) - 1;
                    $last      = $merged[$key][$lastIndex];

                    $endPrev   = new DateTime($last['end']);
                    $startCurr = new DateTime($p->getStartDate());

                    // SOLO fusionar si startCurr <= endPrev
                    if ($startCurr <= $endPrev) {

                        $endCurr = new DateTime($p->getEndDate());
                        if ($endCurr > $endPrev) {
                            $merged[$key][$lastIndex]['end'] = $endCurr->format('Y-m-d');
                        }
                    } else {
                        // No es contiguo ni overlap → abrir nuevo bloque
                        $merged[$key][] = [
                            'id'             => $p->getId(),
                            'owner_id'       => $p->getOwnerId(),
                            'owner_position' => $p->getOwnerPosition(),
                            'type'           => $p->getType(),
                            'in_round'       => $p->getInRound(),
                            'start'          => $p->getStartDate(),
                            'end'            => $p->getEndDate(),
                        ];
                    }
                }

                /**
                 * 3) Emitir eventos usando bloques fusionados
                 */
                foreach ($merged as $blocks) {
                    foreach ($blocks as $period) {

                        $owner = $owner_repository->find($period['owner_id']);

                        // FullCalendar usa end exclusivo → +1 día
                        $date = new DateTime($period['end']);
                        $date->modify('+1 day');
                        $new_end_date = $date->format('Y-m-d');

                        if (empty($uniqueColor)) {
                            $color = $colors_only[$period['owner_position'] - 1] ?? '#999999';
                            if ($period['type'] == 'for rent') {
                                $color = '#CCCCCC';
                            }

                            echo "{
                            id_period: '" . $period['id'] . "',
                            id_owner: '" . $owner->getId() . "',
                            owner_position: '" . $period['owner_position'] . "',
                            use: '" . $period['type'] . "',
                            in_round: '" . $period['in_round'] . "',
                            title: '" . $owner->getName() . "',
                            start: '" . $period['start'] . "',
                            end: '" . $new_end_date . "',
                            overlap: false,
                            backgroundColor: '" . $color . "',
                            borderColor: '" . $color . "',
                        },";
                        } else {
                            $color = $uniqueColor;
                            if ($period['type'] == 'for rent') {
                                $color = '#CCCCCC';
                            }

                            echo "{
                            id_period: '" . $period['id'] . "',
                            id_owner: '" . $owner->getId() . "',
                            owner_position: '" . $period['owner_position'] . "',
                            use: '" . $period['type'] . "',
                            in_round: '" . $period['in_round'] . "',
                            title: '" . $owner->getName() . "',
                            start: '" . $period['start'] . "',
                            end: '" . $new_end_date . "',
                            overlap: false,
                            backgroundColor: '" . $color . "',
                            borderColor: '" . $color . "',
                            clickable: false,
                        },";
                        }
                    }
                }
            }
        } else {
            return $booking;
        }
    }

    public function getSelectedDates($calendar_id, $turn, $round = 1, $format = 'int')
    {
        $booking = $this->repository->getAll($calendar_id, $turn, $round);

        if ($format != 'int') {
            return $booking;
        }

        $totalNights = 0;

        if (!empty($booking)) {
            foreach ($booking as $bookingEntity) {
                $startDate = new DateTime($bookingEntity->getStartDate());
                $endDate = new DateTime($bookingEntity->getEndDate());
                $interval = $startDate->diff($endDate);
                $nights = $interval->days;
                $totalNights += $nights;
            }
        }

        return $totalNights;
    }

    public function getLastReservation($calendar_id, $turn, $round = 1)
    {
        $booking = $this->repository->getAll($calendar_id, $turn, $round);

        if (!empty($booking)) {
            return $booking[array_key_last($booking)];
        } else {
            return [];
        }
    }

    public function getBookingsByOwner($calendar_id, $owner_id, $excludeExchange = true, $onlyPending = true)
    {
        if ($excludeExchange) {
            $booking = $this->repository->getByOwnerExcludingExchange($calendar_id, $owner_id, $onlyPending);
        } else {
            $booking = $this->repository->getByOwner($calendar_id, $owner_id);
        }

        return !empty($booking) ? $booking : [];
    }

    public function selectBooking($id)
    {
        return $this->repository->find($id);
    }

    public function deleteBooking($id)
    {
        return $this->repository->delete($id);
    }

    public function validateIfActualTurnIsComplete($id_calendar, $turn, $owner_id)
    {
        $bookings = $this->repository->findAllDates($id_calendar, $turn, $owner_id);

        if (empty($bookings)) {
            return false;
        }

        $totalDays = 0;

        foreach ($bookings as $booking) {
            $start = new DateTime($booking['start_date']);
            $end = new DateTime($booking['end_date']);
            $diff = $start->diff($end)->days + 1;
            $totalDays += $diff;
        }

        return $totalDays > 9;
    }

    public function deleteAllByCalendar(int $calendarId): int
    {
        return $this->repository->deleteAllByCalendar($calendarId);
    }

    // -----------------------------------------------------------------------------------------

    // -----------------------------------------------------------------------------------------

    public function swapDatesByBookingIds(int $requestId, int $calendarId): array
    {
        global $wpdb;

        $exchangeTable = 'cs_exchange_request';

        // 1) Leer la solicitud de intercambio
        $req = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$exchangeTable} WHERE id = %d LIMIT 1",
                $requestId
            )
        );

        if (!$req) {
            return ['ok' => false, 'msg' => 'Exchange request not found.'];
        }

        // Campos de cs_exchange_request
        $fromOwner = (int) $req->from_owner;
        $toOwner   = (int) $req->to_owner;

        $startFrom = $req->start_from; // YYYY-mm-dd
        $endFrom   = $req->end_from;
        $startTo   = $req->start_to;
        $endTo     = $req->end_to;

        // (opcional) misma cantidad de noches en ambos rangos
        $nightsFrom = (new DateTime($startFrom))->diff(new DateTime($endFrom))->days;
        $nightsTo   = (new DateTime($startTo))->diff(new DateTime($endTo))->days;
        /*if ($nightsFrom !== $nightsTo) {
            return ['ok' => false, 'msg' => 'The ranges must have the same number of nights.'];
        }*/

        // 2) Buscar los bookings que contienen completamente esos rangos
        $bFrom = $this->repository->findBookingContainingRange(
            $calendarId,
            $startFrom,
            $endFrom,
            $fromOwner
        );

        $bTo = $this->repository->findBookingContainingRange(
            $calendarId,
            $startTo,
            $endTo,
            $toOwner
        );

        if (!$bFrom || !$bTo) {
            return ['ok' => false, 'msg' => 'Could not find the reservations to exchange.'];
        }

        // Por seguridad: owners deben ser distintos
        if ((int) $bFrom->getOwnerId() === (int) $bTo->getOwnerId()) {
            return ['ok' => false, 'msg' => 'Both reservations belong to the same owner.'];
        }

        // 3) Datos base de cada booking
        $fromPos   = (int) $bFrom->getOwnerPosition();
        $toPos     = (int) $bTo->getOwnerPosition();
        $fromRound = (int) $bFrom->getInRound();
        $toRound   = (int) $bTo->getInRound();

        $fromType  = $bFrom->getType();   // normalmente 'for personal use'
        $toType    = $bTo->getType();

        $fullFromStart = $bFrom->getStartDate();
        $fullFromEnd   = $bFrom->getEndDate();
        $fullToStart   = $bTo->getStartDate();
        $fullToEnd     = $bTo->getEndDate();

        // Helpers +/- 1 día
        $minusOne = function (string $ymd): string {
            // return (new DateTime($ymd))->modify('-1 day')->format('Y-m-d');
            return (new DateTime($ymd))->format('Y-m-d');
        };
        $plusOne = function (string $ymd): string {
            // return (new DateTime($ymd))->modify('+1 day')->format('Y-m-d');
            return (new DateTime($ymd))->format('Y-m-d');
        };

        try {
            $wpdb->query('START TRANSACTION');

            // 4) Eliminar los bookings originales completos
            $del1 = $this->repository->delete($bFrom->getId());
            $del2 = $this->repository->delete($bTo->getId());

            if ($del1 === false || $del2 === false) {
                throw new RuntimeException('Delete failed while preparing exchange.');
            }

            /**
             * 5) Reconstruir booking del FROM_OWNER
             *    - Las partes fuera del rango intercambiado conservan fromOwner.
             *    - El rango del TO pasa a fromOwner.
             */

            // a) Tramo antes del rango intercambiado (mismo owner original)
            if ($fullFromStart < $startFrom) {
                $beforeEnd = $minusOne($startFrom);
                if ($fullFromStart <= $beforeEnd) {
                    $this->createRentedBooking(
                        $calendarId,
                        $fullFromStart,
                        $beforeEnd,
                        $fromOwner,
                        $fromPos,
                        null,
                        $fromType
                    );
                }
            }

            // b) Tramo después del rango intercambiado (mismo owner original)
            if ($fullFromEnd > $endFrom) {
                $afterStart = $plusOne($endFrom);
                if ($afterStart <= $fullFromEnd) {
                    $this->createRentedBooking(
                        $calendarId,
                        $afterStart,
                        $fullFromEnd,
                        $fromOwner,
                        $fromPos,
                        null,
                        $fromType
                    );
                }
            }

            // c) Rango del TO que pasa a ser del FROM
            $this->createRentedBooking(
                $calendarId,
                $startTo,
                $endTo,
                $fromOwner,
                $fromPos,
                $fromRound,
                $fromType
            );

            /**
             * 6) Reconstruir booking del TO_OWNER
             *    - Las partes fuera del rango intercambiado conservan toOwner.
             *    - El rango del FROM pasa a toOwner.
             */

            // a) Tramo antes del rango intercambiado (mismo owner original)
            if ($fullToStart < $startTo) {
                $beforeEndTo = $minusOne($startTo);
                if ($fullToStart <= $beforeEndTo) {
                    $this->createRentedBooking(
                        $calendarId,
                        $fullToStart,
                        $beforeEndTo,
                        $toOwner,
                        $toPos,
                        null,
                        $toType
                    );
                }
            }

            // b) Tramo después del rango intercambiado (mismo owner original)
            if ($fullToEnd > $endTo) {
                $afterStartTo = $plusOne($endTo);
                if ($afterStartTo <= $fullToEnd) {
                    $this->createRentedBooking(
                        $calendarId,
                        $afterStartTo,
                        $fullToEnd,
                        $toOwner,
                        $toPos,
                        null,
                        $toType
                    );
                }
            }

            // c) Rango del FROM que pasa a ser del TO
            $this->createRentedBooking(
                $calendarId,
                $startFrom,
                $endFrom,
                $toOwner,
                $toPos,
                $toRound,
                $toType
            );

            $wpdb->query('COMMIT');
            return ['ok' => true];
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            error_log('swapDatesByBookingIds error: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Could not exchange reservations.'];
        }
    }

    // -----------------------------------------------------------------------------------------

    public function selectBookingIdByDatesWithIn($calendar_id, $end_from, $end_to)
    {
        $booking = $this->repository->findOneContainingRange($calendar_id, $end_from, $end_to);

        if (!$booking) {
            return null;
        }

        return (int) $booking->getOwnerId();
    }

    public function IsThereConflictWithSomeExchangeRequest($id_calendar, $start, $end, bool $onlyPending = true): bool
    {
        return $this->repository->hasExchangeRequestDateConflict($id_calendar, $start, $end, $onlyPending);
    }

    public function IsSelectingTwoBookings($id_calendar, $start, $end): bool
    {
        return $this->repository->selectingTwoBookings($id_calendar, $start, $end);
    }

    public function IsDateTheStartOrEndOfTheBooking($id_calendar, $start, $end)
    {
        $start_ampliation = $this->repository->isTheStartOfTheBookingSelected($id_calendar, $start);
        $end_ampliation = $this->repository->isTheEndOfTheBookingSelected($id_calendar, $end);
        
        return $start_ampliation || $end_ampliation;
    }
}
