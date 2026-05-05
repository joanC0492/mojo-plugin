<?php

use BcMath\Number;

require_once __DIR__ . '/../entities/BookingEntity.php';
require_once __DIR__ . '/../dtos/CreateBookingDto.php';
require_once __DIR__ . '/../dtos/UpdateBookingDto.php';

class BookingRepository
{

    private $wpdb;
    private $table = 'cs_booking';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function findOneContainingRange(int $calendarId, string $start, string $end): ?BookingEntity
    {
        // Buscamos una reserva del calendario cuyo rango
        // start_date <= $start  Y  end_date >= $end
        $sql = $this->wpdb->prepare(
            "
        SELECT *
        FROM {$this->table}
        WHERE calendar_id = %d
          AND start_date <= %s
          AND end_date >= %s
        ORDER BY start_date ASC
        LIMIT 1
        ",
            $calendarId,
            $start,
            $end
        );

        $row = $this->wpdb->get_row($sql);

        if (!$row) {
            return null;
        }

        return new BookingEntity($row->id, $row->calendar_id, $row->start_date, $row->end_date, $row->owner_id, $row->owner_position, $row->in_round, $row->type);
    }

    public function insert(CreateBookingDto $createBookingDto)
    {
        $data = $createBookingDto->getDataValues();
        $dataTypes = $createBookingDto->getDataTypes();

        $inserted = $this->wpdb->insert($this->table, $data, $dataTypes);
        if (!$inserted) {
            return null; // O lanzar una excepción
        }

        $booking_id = $this->wpdb->insert_id;
        return $this->find($booking_id);
    }

    public function insertOrMerge(CreateBookingDto $dto)
    {
        $table = $this->table;

        $start = new DateTime($dto->getStartDate());
        $end = new DateTime($dto->getEndDate());

        $start_minus_1 = (clone $start)->modify('-1 day')->format('Y-m-d');
        $start_exact = $start->format('Y-m-d');

        $end_plus_1 = (clone $end)->modify('+1 day')->format('Y-m-d');
        $end_exact = $end->format('Y-m-d');

        // Buscar reserva que termine justo el día anterior o igual al inicio
        $prev = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table 
         WHERE calendar_id = %d AND owner_id = %d AND owner_position = %d AND in_round = %d 
         AND (end_date = %s OR end_date = %s)",
            $dto->getCalendarId(),
            $dto->getOwnerId(),
            $dto->getOwnerPosition(),
            $dto->getInRound(),
            $start_minus_1,
            $start_exact
        ));

        // Buscar reserva que empiece justo el día siguiente o igual al fin
        $next = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table 
         WHERE calendar_id = %d AND owner_id = %d AND owner_position = %d AND in_round = %d 
         AND (start_date = %s OR start_date = %s)",
            $dto->getCalendarId(),
            $dto->getOwnerId(),
            $dto->getOwnerPosition(),
            $dto->getInRound(),
            $end_plus_1,
            $end_exact
        ));

        // Si hay reservas previas y siguientes, fusionamos todas
        if ($prev && $next) {
            $this->wpdb->delete($table, ['id' => $prev->id]);
            $this->wpdb->delete($table, ['id' => $next->id]);

            $dto->setStartDate($prev->start_date);
            $dto->setEndDate($next->end_date);

            $data = $dto->getDataValues();
            $types = $dto->getDataTypes();

            $inserted = $this->wpdb->insert($table, $data, $types);
            if (!$inserted) return null;

            return $this->find($this->wpdb->insert_id);
        }

        // Si solo hay una previa, extender end_date
        if ($prev) {
            $this->wpdb->update(
                $table,
                ['end_date' => $dto->getEndDate()],
                ['id' => $prev->id]
            );
            return $this->find($prev->id);
        }

        // Si solo hay una siguiente, extender start_date
        if ($next) {
            $this->wpdb->update(
                $table,
                ['start_date' => $dto->getStartDate()],
                ['id' => $next->id]
            );
            return $this->find($next->id);
        }

        // Si no hay ninguna reserva adyacente, insertar normalmente
        $data = $dto->getDataValues();
        $types = $dto->getDataTypes();

        $inserted = $this->wpdb->insert($table, $data, $types);

        if (!$inserted) return null;

        return $this->find($this->wpdb->insert_id);
    }

    public function find(int $id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE id = %d", $id);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return new BookingEntity($row->id, $row->calendar_id, $row->start_date, $row->end_date, $row->owner_id, $row->owner_position, $row->in_round, $row->type);
    }

    public function update(UpdateBookingDto $updateCalendarDto)
    {
        $data = $updateCalendarDto->getDataValues();
        $dataTypes = $updateCalendarDto->getDataTypes();

        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $updateCalendarDto->getId()], // Asegurar la condición WHERE id = ?
            $dataTypes,
            ['%d'] // Tipo de dato del ID
        );

        return $result !== false; // Devuelve true si la actualización fue exitosa
    }

    public function getAll($calendar_id, $turn = '', $round = 1)
    {
        if (empty($turn)) {
            $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE calendar_id = %d", $calendar_id);
        } else {
            $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE calendar_id = %d AND owner_position = %d AND in_round = %d", $calendar_id, $turn, $round);
        }
        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $booking = [];
        foreach ($results as $row) {
            $booking[] = new BookingEntity($row->id, $row->calendar_id, $row->start_date, $row->end_date, $row->owner_id, $row->owner_position, $row->in_round, $row->type);
        }

        return $booking;
    }

    public function getByOwner($calendar_id, $owner_id)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE calendar_id = %d AND owner_id = %d AND type = %s ORDER BY start_date ASC", $calendar_id, $owner_id, 'for personal use');
        $results = $this->wpdb->get_results($query);

        if (empty($results)) {
            return [];
        }

        $booking = [];
        foreach ($results as $row) {
            $booking[] = new BookingEntity($row->id, $row->calendar_id, $row->start_date, $row->end_date, $row->owner_id, $row->owner_position, $row->in_round, $row->type);
        }

        return $booking;
    }

    public function getByOwnerExcludingExchange($calendar_id, $owner_id, $onlyPending = true): array
    {
        $exTable = 'cs_exchange_request';

        // Si solo quieres excluir las solicitudes en estado 'pending'
        $statusCond = $onlyPending ? "AND r.status = 'pending'" : "";

        $sql = "
        SELECT b.*
        FROM {$this->table} b
        WHERE b.calendar_id = %d
          AND b.owner_id    = %d
          AND b.type        = %s
          AND NOT EXISTS (
              SELECT 1
              FROM {$exTable} r
              WHERE r.from_owner_booking = b.id
              {$statusCond}
          )
        ORDER BY b.start_date ASC
    ";

        $prepared = $this->wpdb->prepare($sql, $calendar_id, $owner_id, 'for personal use');
        $rows = $this->wpdb->get_results($prepared);

        if (empty($rows)) {
            return [];
        }

        $booking = [];
        foreach ($rows as $row) {
            $booking[] = new BookingEntity(
                (int)$row->id,
                (int)$row->calendar_id,
                $row->start_date,
                $row->end_date,
                (int)$row->owner_id,
                (int)$row->owner_position,
                (int)$row->in_round,
                $row->type
            );
        }

        return $booking;
    }

    public function delete(int $id)
    {
        $deleted = $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d'] // Tipo de dato del ID
        );

        return $deleted !== false; // Devuelve true si se eliminó correctamente
    }

    public function findAllDates(int $id, int $owner_position, int $owner_id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM $this->table WHERE calendar_id = %d AND owner_position = %d AND owner_id = %d",
            $id,
            $owner_position,
            $owner_id
        );
        $row = $this->wpdb->get_results($query, ARRAY_A);

        if (!$row) {
            return null; // O lanzar una excepción
        }

        return $row;
    }

    public function deleteAllByCalendar(int $calendarId): int
    {
        // borra TODAS las filas de cs_booking para ese calendario
        // devuelve el número de filas borradas (0 si no había)
        return (int) $this->wpdb->delete('cs_booking', ['calendar_id' => $calendarId], ['%d']);
    }

    // ---------------------------------------------------------------------------------------

    public function updateDates(int $id, string $startDate, string $endDate): bool
    {
        $updated = $this->wpdb->update(
            $this->table,
            ['start_date' => $startDate, 'end_date' => $endDate],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
        return $updated !== false;
    }

    public function findSameLengthBookings(
        int $calendarId,
        int $ownerIdToExclude,
        int $nights,
        ?int $excludeBookingId = null
    ): array {
        $exTable = 'cs_exchange_request';

        // Orden de parámetros DEBE coincidir con el orden de los %d/%s en $sql
        $params = [$calendarId, $ownerIdToExclude, 'for personal use', $nights];

        $sql = "
        SELECT b.*
        FROM {$this->table} b
        WHERE b.calendar_id = %d
          AND b.owner_id <> %d
          AND b.type = %s
          AND DATEDIFF(b.end_date, b.start_date) = %d
          -- excluir reservas que ya son 'destino' de un intercambio pendiente
          AND NOT EXISTS (
              SELECT 1
              FROM {$exTable} r
              WHERE r.to_owner_booking = b.id
                AND r.status = 'pending'
          )
    ";

        if ($excludeBookingId) {
            $sql .= " AND b.id <> %d";
            $params[] = $excludeBookingId;
        }

        $sql .= " ORDER BY b.start_date ASC";

        $prepared = $this->wpdb->prepare($sql, $params);
        $rows = $this->wpdb->get_results($prepared);

        $out = [];
        if ($rows) {
            foreach ($rows as $r) {
                $out[] = new BookingEntity(
                    (int)$r->id,
                    (int)$r->calendar_id,
                    $r->start_date,
                    $r->end_date,
                    (int)$r->owner_id,
                    (int)$r->owner_position,
                    (int)$r->in_round,
                    $r->type
                );
            }
        }
        return $out;
    }

    public function findBookingContainingRange(
        int $calendarId,
        string $rangeStart,
        string $rangeEnd,
        ?int $ownerId = null
    ) {
        $params = [$calendarId, $rangeStart, $rangeEnd];

        $sql = "
        SELECT *
        FROM {$this->table}
        WHERE calendar_id = %d
          AND start_date <= %s
          AND end_date   >= %s
    ";

        // si se pasa ownerId, lo filtramos también
        if ($ownerId !== null) {
            $sql .= " AND owner_id = %d";
            $params[] = $ownerId;
        }

        $sql .= " ORDER BY start_date ASC LIMIT 1";

        $prepared = $this->wpdb->prepare($sql, $params);
        $row      = $this->wpdb->get_row($prepared);

        if (!$row) {
            return null;
        }

        return new BookingEntity(
            (int) $row->id,
            (int) $row->calendar_id,
            $row->start_date,
            $row->end_date,
            (int) $row->owner_id,
            (int) $row->owner_position,
            (int) $row->in_round,
            $row->type
        );
    }

    public function hasExchangeRequestDateConflict($id_calendar, $start, $end, bool $onlyPending = true): bool
    {
        $exTable = 'cs_exchange_request';

        // Base SQL sin la condición de estado
        /*$sql = "
            SELECT id
            FROM {$exTable}
            WHERE id_calendar = %d
            AND (
                    (%s BETWEEN start_from AND end_from)
                OR  (%s BETWEEN start_from AND end_from)
                OR  (start_from >= %s AND end_from <= %s)
                OR  (start_from <= %s AND end_from >= %s)
            )
        ";*/

        $sql = "
            SELECT id
            FROM {$exTable}
            WHERE id_calendar = %d
            AND (
                (%s < end_from AND %s > start_from)
                AND NOT (%s = start_from OR %s = end_from)
            )
        ";

        // 🔥 Condición según el parámetro
        if ($onlyPending) {
            $sql .= " AND status = 'pending'";
        } else {
            $sql .= " AND status IN ('pending','approved')";
        }

        $sql .= " LIMIT 1";

        // Preparar con todos los parámetros
        $prepared = $this->wpdb->prepare(
            $sql,
            $id_calendar,
            $start,
            $end,
            $start,
            $end,
            $start,
            $end
        );

        $conflict = $this->wpdb->get_var($prepared);

        return !empty($conflict);
    }



    public function isTheStartOfTheBookingSelected(int $calendar_id, $start_date)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE calendar_id = %d AND start_date = %s", $calendar_id, $start_date);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return false; // O lanzar una excepción
        }

        return true;
    }

    public function isTheEndOfTheBookingSelected(int $calendar_id, $end_date)
    {
        $query = $this->wpdb->prepare("SELECT * FROM $this->table WHERE calendar_id = %d AND end_date = %s", $calendar_id, $end_date);
        $row = $this->wpdb->get_row($query);

        if (!$row) {
            return false; // O lanzar una excepción
        }

        return true;
    }

    public function selectingTwoBookings(int $id_calendar, $start_date, $end_date): bool
    {
        // Normaliza (por si llega con espacios o formato raro)
        $start_date = trim((string)$start_date);
        $end_date   = trim((string)$end_date);

        if (!$start_date || !$end_date) {
            return false;
        }

        // Asegura orden
        if ($start_date > $end_date) {
            [$start_date, $end_date] = [$end_date, $start_date];
        }

        /**
         * Overlap inclusivo:
         *   b.start_date <= selected_end
         *   b.end_date   >= selected_start
         *
         * Pero ignoramos "toques de borde":
         *   selected_start == b.end_date  (pegado por la izquierda)
         *   selected_end   == b.start_date (pegado por la derecha)
         *
         * Si dentro del rango se tocan >=2 owners distintos => true
         */
        $sql = "
        SELECT COUNT(DISTINCT b.owner_id) AS owners_count
        FROM {$this->table} b
        WHERE b.calendar_id = %d
          AND b.start_date <= %s
          AND b.end_date   >= %s
          AND NOT (
                %s = b.end_date
             OR %s = b.start_date
          )
        LIMIT 1
    ";

        $prepared = $this->wpdb->prepare(
            $sql,
            $id_calendar,
            $end_date,    // b.start_date <= selected_end
            $start_date,  // b.end_date   >= selected_start
            $start_date,  // selected_start = b.end_date
            $end_date     // selected_end   = b.start_date
        );

        $owners_count = (int) $this->wpdb->get_var($prepared);

        return $owners_count >= 2;
    }
}
