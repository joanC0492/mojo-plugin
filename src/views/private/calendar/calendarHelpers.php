<?php
function cs_ranges_overlap(string $aStart, string $aEnd, ?string $bStart, ?string $bEnd): bool
{
    if (!$bStart || !$bEnd) return false;        // por si hay filas sin join
    // Comparación inclusiva: [aStart..aEnd] toca [bStart..bEnd] si:
    return ($aStart <= $bEnd) && ($aEnd >= $bStart);
}

function merge_ranges($bookings)
{
    $merged = [];

    foreach ($bookings as $row) {
        $start = $row->getStartDate(); // 'YYYY-mm-dd'
        $end   = $row->getEndDate();   // 'YYYY-mm-dd'

        if (empty($merged)) {
            $merged[] = [
                'start' => $start,
                'end'   => $end,
            ];
            continue;
        }

        $last_index = count($merged) - 1;
        $last       = $merged[$last_index];

        // Calculamos el día siguiente al final previo
        $day_after_last_end = date('Y-m-d', strtotime($last['end'] . ' +1 day'));

        if ($start <= $day_after_last_end) {
            // tocan o se solapan ⇒ expandimos el final si este bloque termina después
            if ($end > $last['end']) {
                $merged[$last_index]['end'] = $end;
            }
        } else {
            // separado ⇒ nuevo bloque
            $merged[] = [
                'start' => $start,
                'end'   => $end,
            ];
        }
    }

    return $merged;
}

function get_current_share_of_current_owner($qty_shares, $position_of_owners, $order_of_owners, $currentRound, $currentTurn)
{
    $qty_rounds = ($qty_shares == 8) ? 5 : 6;

    // Mapa: owner_id => [lista de share_positions ordenadas]
    $sharePositionsByOwnerId = [];
    foreach ($position_of_owners as $sharePos => $o) {
        $oid = (int)($o['owner_id'] ?? 0);
        if (!isset($sharePositionsByOwnerId[$oid])) {
            $sharePositionsByOwnerId[$oid] = [];
        }
        $sharePositionsByOwnerId[$oid][] = (int)$sharePos;
    }

    // Ordena ascendente las posiciones por dueño
    foreach ($sharePositionsByOwnerId as $oid => $arr) {
        sort($sharePositionsByOwnerId[$oid], SORT_NUMERIC);
    }

    // 🔁 recorrer rondas
    for ($round = 1; $round <= $qty_rounds; $round++) {
        // Reiniciar el puntero de consumo en cada ronda
        $consumedIndexByOwnerId = [];

        $is_even_round = ($round % 2 === 0);
        $positions = $is_even_round ? range($qty_shares, 1) : range(1, $qty_shares);

        foreach ($positions as $priorityPos) {
            $owner = $order_of_owners[$priorityPos] ?? null;

            $is_current = ($round == $currentRound) && ($priorityPos == $currentTurn);

            // Resolver Share real:
            $owner_position_text = 'X';
            if (!empty($owner) && !empty($owner['id'])) {
                $oid = (int)$owner['id'];

                if (!empty($sharePositionsByOwnerId[$oid])) {
                    if (!isset($consumedIndexByOwnerId[$oid])) {
                        $consumedIndexByOwnerId[$oid] = 0;
                    }
                    $idx = $consumedIndexByOwnerId[$oid];

                    if (isset($sharePositionsByOwnerId[$oid][$idx])) {
                        $owner_position_text = $sharePositionsByOwnerId[$oid][$idx];
                        $consumedIndexByOwnerId[$oid]++;
                    } else {
                        $owner_position_text = end($sharePositionsByOwnerId[$oid]);
                    }
                }
            }

            if ($is_current) {
                return $owner_position_text;
            }
        }
    }

    // fallback si no encuentra el current
    return null;
}

function cs_is_valid_ymd(string $s): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
    [$y, $m, $d] = array_map('intval', explode('-', $s));
    return checkdate($m, $d, $y);
}

function cs_normalize_blocked_dates(?string $raw): array
{
    if ($raw === null) return [];
    $raw = trim((string)$raw);

    // JSON o CSV
    if ($raw !== '' && ($raw[0] === '[' || $raw[0] === '{')) {
        $decoded = json_decode(stripslashes($raw), true);
        $arr = is_array($decoded) ? $decoded : [];
    } else {
        $arr = array_filter(array_map('trim', explode(',', $raw)), fn($x) => $x !== '');
    }

    // limpia, valida, dedup y ordena
    $arr = array_filter($arr, 'is_string');
    $arr = array_map(fn($d) => substr($d, 0, 10), $arr);
    $arr = array_values(array_unique(array_filter($arr, 'cs_is_valid_ymd')));
    usort($arr, fn($a, $b) => strcmp($a, $b));
    return $arr;
}

function cs_is_allowed_php(
    $startDate,
    $endDate,
    bool $isLastRound,
    int $nightCount,
    int $gapBefore,
    int $gapAfter,
    bool $areAllSelectedDatesHigh,
    $firstHighDateForThatRange,
    $lastHighDateForThatRange,
    bool $areThereHighAndLowSelectedDates,
    bool $isStartDateHigh,
    bool $isEndDateHigh,
    bool $isEarliestReservation,
    bool $isLatestReservation,
    int $highNightCount,
    bool $isTheSameOwnerExtending,
    int $nightsAvailable,
    int $allowedNights
): array {
    // Normalización y helpers de fecha
    $toDT = function ($d): DateTimeImmutable {
        if (!$d) return new DateTimeImmutable('1970-01-01');
        if ($d instanceof DateTimeInterface) return DateTimeImmutable::createFromInterface($d);
        return new DateTimeImmutable((string)$d);
    };
    $sameDay = function ($a, $b) use ($toDT): bool {
        return $toDT($a)->format('Y-m-d') === $toDT($b)->format('Y-m-d');
    };

    // Aliases (igual que en JS)
    $isFillingTheGap = ($gapBefore === 0 && $gapAfter === 0);
    $booking7        = ($nightCount >= 7);
    $gapRule3        = (($gapBefore >= 3 || $gapBefore === 0) && ($gapAfter >= 3 || $gapAfter === 0));
    $gapRule7        = (($gapBefore >= 7 || $gapBefore === 0) && ($gapAfter >= 7 || $gapAfter === 0));
    $booking3        = ($nightCount >= 3);
    $bookingAllAvail = ($allowedNights == $nightCount);
    $isEarliest      = $isEarliestReservation;
    $isLatest        = $isLatestReservation;
    $zeroNights      = ($nightCount === 0);
    $someHighSomeLow = $areThereHighAndLowSelectedDates;
    $allHigh         = $areAllSelectedDatesHigh;
    $availableGap    = $gapAfter + $gapBefore + $nightCount;
    $gapLess14       = ($availableGap < 14);
    $isGlued         = ($gapAfter === 0 || $gapBefore === 0);

    if ($zeroNights) {
        return ['allowed' => false, 'code' => 'ERROR #00', 'message' => 'Must select at least one night'];
    }

    if ($isLastRound) {
        return ['allowed' => true, 'code' => 'TRUE #00', 'message' => 'Everything is allowed in the last round'];
    }

    if ($isTheSameOwnerExtending) {
        return ['allowed' => true, 'code' => 'TRUE #01', 'message' => 'Same owner extending'];
    }

    if ($allHigh || $someHighSomeLow) {
        $matchSeasonStart = $sameDay($firstHighDateForThatRange, $startDate);
        $matchSeasonEnd   = $sameDay($lastHighDateForThatRange,  $endDate);

        if ($booking7) {
            if ($gapRule7) {
                return ['allowed' => true, 'code' => 'TRUE #03', 'message' => 'All conditions met for 7 nights or more'];
            } else {
                if ($gapLess14) {
                    return ['allowed' => true, 'code' => 'TRUE #06', 'message' => 'All conditions met for 7 nights or more and available gap is 14 or less'];
                } else {
                    return ['allowed' => false, 'code' => 'ERROR #01', 'message' => 'It does not meet the gap rules'];
                }
            }
        } else {
            if ($booking3) {
                if ($isFillingTheGap) {
                    return ['allowed' => true, 'code' => 'TRUE #05', 'message' => 'All conditions met for 3 nights or more and filling the gap'];
                } else {
                    if ($gapRule3) {
                        if ($bookingAllAvail) {
                            return ['allowed' => true, 'code' => 'TRUE #07', 'message' => 'All conditions met for 3 nights or more and gap rules satisfied'];
                        } else {
                            if (($matchSeasonStart || $matchSeasonEnd) && $isGlued) {
                                return ['allowed' => true, 'code' => 'TRUE #08', 'message' => 'All conditions met for 3 nights or more and starts with a high season interval'];
                            } else {
                                return ['allowed' => false, 'code' => 'ERROR #02', 'message' => 'It does not meet the gap rules'];
                            }
                        }
                    } else {
                        return ['allowed' => false, 'code' => 'ERROR #03', 'message' => 'It does not meet the gap rules'];
                    }
                }
            } else {
                return ['allowed' => false, 'code' => 'ERROR #04', 'message' => 'It does not meet the gap rules'];
            }
        }
    } else {
        if ($booking3) {
            if ($gapRule3) {
                return ['allowed' => true, 'code' => 'TRUE #08', 'message' => 'All conditions met for 3 nights or more and gap rules satisfied'];
            } else {
                if ($bookingAllAvail || $isEarliest || $isLatest) {
                    return ['allowed' => true, 'code' => 'TRUE #09', 'message' => 'Max nights available for 3 nights or more'];
                } else {
                    return ['allowed' => false, 'code' => 'ERROR #05', 'message' => 'It does not meet the gap rules'];
                }
            }
        } else {
            return ['allowed' => false, 'code' => 'ERROR #06', 'message' => 'It does not meet the gap rules'];
        }
    }
}

add_action('wp_ajax_nopriv_mojo_is_allowed', 'mojo_is_allowed');
add_action('wp_ajax_mojo_is_allowed', 'mojo_is_allowed');

function mojo_is_allowed()
{
    $bool = fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

    $startDate  = sanitize_text_field($_POST['startDate'] ?? '');
    $endDate    = sanitize_text_field($_POST['endDate'] ?? '');
    $isLastRound = $bool($_POST['isLastRound'] ?? false);
    $nightCount = intval($_POST['nightCount'] ?? 0);
    $gapBefore  = intval($_POST['gapBefore'] ?? 0);
    $gapAfter   = intval($_POST['gapAfter'] ?? 0);

    $areAllSelectedDatesHigh = $bool($_POST['areAllSelectedDatesHigh'] ?? false);
    $firstHighDateForThatRange = sanitize_text_field($_POST['firstHighDateForThatRange'] ?? '');
    $lastHighDateForThatRange  = sanitize_text_field($_POST['lastHighDateForThatRange'] ?? '');
    $areThereHighAndLowSelectedDates = $bool($_POST['areThereHighAndLowSelectedDates'] ?? false);

    $isStartDateHigh = $bool($_POST['isStartDateHigh'] ?? false);
    $isEndDateHigh   = $bool($_POST['isEndDateHigh'] ?? false);
    $isEarliestReservation = $bool($_POST['isEarliestReservation'] ?? false);
    $isLatestReservation   = $bool($_POST['isLatestReservation'] ?? false);
    $highNightCount        = intval($_POST['highNightCount'] ?? 0);
    $isTheSameOwnerExtending = $bool($_POST['isTheSameOwnerExtending'] ?? false);
    $nightsAvailable = intval($_POST['nightsAvailable'] ?? 0);
    $allowedNights   = intval($_POST['allowedNights'] ?? 0);

    // Llama a la validación principal
    $result = cs_is_allowed_php(
        $startDate,
        $endDate,
        $isLastRound,
        $nightCount,
        $gapBefore,
        $gapAfter,
        $areAllSelectedDatesHigh,
        $firstHighDateForThatRange,
        $lastHighDateForThatRange,
        $areThereHighAndLowSelectedDates,
        $isStartDateHigh,
        $isEndDateHigh,
        $isEarliestReservation,
        $isLatestReservation,
        $highNightCount,
        $isTheSameOwnerExtending,
        $nightsAvailable,
        $allowedNights
    );

    if ($result['allowed']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
