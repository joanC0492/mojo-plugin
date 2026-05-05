<?php
add_action('wp_ajax_nopriv_mojo_panel_exchange_dates', 'mojo_panel_exchange_dates');
add_action('wp_ajax_mojo_panel_exchange_dates', 'mojo_panel_exchange_dates');

function mojo_panel_exchange_dates()
{
    $id_period_1 = intval($_POST['id_period_1'] ?? 0);
    $id_period_2 = intval($_POST['id_period_2'] ?? 0);

    if (!$id_period_1 || !$id_period_2) {
        wp_send_json_error(['message' => 'Missing reservation identifiers.']);
    }

    $booking_service = new BookingService();

    try {
        $response = $booking_service->swapDatesByBookingIds($id_period_1, $id_period_2);

        if (!empty($response['ok'])) {
            wp_send_json_success(['message' => 'Reservations exchanged']);
        } else {
            wp_send_json_error(['message' => $response['msg'] ?? 'Could not exchange reservations.']);
        }
    } catch (Throwable $e) {
        error_log('Error en mojo_panel_exchange_dates: ' . $e->getMessage());
        wp_send_json_error(['message' => 'A problem occurred while trying to exchange reservations.']);
    }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_change_request_status', 'mojo_panel_change_request_status');
add_action('wp_ajax_mojo_panel_change_request_status', 'mojo_panel_change_request_status');

function mojo_panel_change_request_status()
{
    $id_request  = intval($_POST['id_request'] ?? 0);
    $id_calendar = intval($_POST['id_calendar'] ?? 0);

    $status      = $_POST['status'] ?? '';
    $requestor_dates      = $_POST['requestor_dates'] ?? '';
    $recipient_dates      = $_POST['recipient_dates'] ?? '';
    $ownerFrom      = $_POST['from_owner'] ?? '';
    $ownerTo      = $_POST['to_owner'] ?? '';

    if (!$id_calendar || !$id_request || !$status) {
        wp_send_json_error(['message' => 'Missing reservation identifiers.']);
    }

    $booking_service = new BookingService();
    $calendar_service = new CalendarService();
    $exchangeRequestService  = new ExchangeRequestService();
    $notification_service = new NotificationService();
    $owner_service = new OwnerService();
    $template_service = new TemplateService();

    try {
        $templates7 = $template_service->getNotifications(7);
        $current_status = $exchangeRequestService->selectRequest($id_request);

        if (!$current_status) {
            wp_send_json_error(['message' => 'The request to update was not found.']);
        }

        if ($current_status->getStatus() !== 'pending') {
            wp_send_json_error(['message' => 'The request has already been previously updated.']);
        }

        // SOLO si se aprueba, intentamos intercambiar días
        if ($status == 'approved') {
            $swaping = $booking_service->swapDatesByBookingIds($id_request, $id_calendar);

            $propertyInfo = $calendar_service->getPropertyByCalendar($id_calendar);
            $ownerFromInfo = $owner_service->getOwner($ownerFrom);
            $ownerToInfo = $owner_service->getOwner($ownerTo);

            $placeholders = [
                '[REQUESTOR]'     => $ownerFromInfo->getName(),
                '[RECIPIENT]' => $ownerToInfo->getName(),
                '[REQUESTOR_DATES]'  => $requestor_dates,
                '[RECIPIENT_DATES]'  => $recipient_dates,
                '[PROPERTY]'    => $propertyInfo->getName()
            ];

            if ($templates7->email_enabled) {
                $body = $templates7->body ?? '';

                $active_placeholders = array_filter($placeholders, function ($key) use ($body) {
                    return strpos($body, $key) !== false;
                }, ARRAY_FILTER_USE_KEY);

                $message = str_replace(array_keys($active_placeholders), array_values($active_placeholders), $body);
                $email   = get_exchange_email();

                cs_log('Sending email notification for exchange dates.', [
                    'subject' => $templates7->subject,
                    'message' => $message,
                    'to'      => $email
                ]);

                send_notification_email($templates7->subject, $message, $email);
            }

            // ---------------------------------

            if ($templates7->push_enabled) {

                $active_placeholders = array_filter($placeholders, function ($key) use ($templates7) {
                    return strpos($templates7->message, $key) !== false;
                }, ARRAY_FILTER_USE_KEY);

                $push = str_replace(array_keys($active_placeholders), array_values($active_placeholders), $templates7->message);

                $notification_service->createNotification($ownerFrom, $push);
            }

            if (!$swaping['ok']) {
                wp_send_json_error(['message' => $swaping['msg'] ?? 'Could not exchange reservations.']);
            }
        }

        // Si llegamos aquí, o bien se aprobó con éxito el intercambio,
        // o se cambió a "declined" / otro estado sin problema
        $response = $exchangeRequestService->updateExchangeRequest($id_request, null, null, null, null, null, null, null, $status);

        if ($response) {
            wp_send_json_success(['message' => 'Request Updated']);
        } else {
            wp_send_json_error(['message' => 'Could not update the request.']);
        }
    } catch (Throwable $e) {
        error_log('Error en mojo_panel_change_request_status: ' . $e->getMessage());
        wp_send_json_error(['message' => 'A problem occurred while trying to update the request.']);
    }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_save_exchange_request_pre_validation', 'mojo_panel_save_exchange_request_pre_validation');
add_action('wp_ajax_mojo_panel_save_exchange_request_pre_validation', 'mojo_panel_save_exchange_request_pre_validation');

function mojo_panel_save_exchange_request_pre_validation()
{
    $ownerFrom = intval($_POST['ownerFrom'] ?? 0);
    $id_calendar = intval($_POST['calendarId'] ?? 0);
    $start_from = $_POST['startFrom'] ?? '';
    $end_from = $_POST['endFrom'] ?? '';
    $qtyDaysValidation = filter_var($_POST['qtyDaysValidation'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$start_from || !$end_from || !$ownerFrom || !$id_calendar) {
        wp_send_json_error(['message' => 'Missing reservation information.']);
    }

    $exchangeRequestService = new ExchangeRequestService();
    $booking_service = new BookingService();

    try {

        $is_selecting_two_bookings = $booking_service->IsSelectingTwoBookings($id_calendar, $start_from, $end_from);
        if ($is_selecting_two_bookings) {
            wp_send_json_error(['message' => 'Make sure you do not select 2 ranges from different owners.']);
        }

        $is_ampliation = true;
        /*$is_ampliation = $booking_service->IsDateTheStartOrEndOfTheBooking($id_calendar, $start_from, $end_from);
        if (!$is_ampliation && $qtyDaysValidation) {
            wp_send_json_error(['message' => 'A minimum of 3 consecutive nights must be selected.']);
        }*/

        $hasConflict = $booking_service->IsThereConflictWithSomeExchangeRequest($id_calendar, $start_from, $end_from, true);
        if ($hasConflict) {
            wp_send_json_error(['message' => 'There is already an exchange request involving some of those dates.']);
        }

        wp_send_json_success(['message' => 'Reservations exchanged', 'is_ampliation' => $is_ampliation]);
    } catch (Throwable $e) {
        error_log('Error en mojo_panel_exchange_dates: ' . $e->getMessage());
        wp_send_json_error(['message' => 'A problem occurred while trying to exchange reservations.']);
    }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

function format_date($date)
{
    return $date ? (new DateTime($date))->format('d/m/Y') : '';
}

add_action('wp_ajax_nopriv_mojo_panel_save_exchange_request', 'mojo_panel_save_exchange_request');
add_action('wp_ajax_mojo_panel_save_exchange_request', 'mojo_panel_save_exchange_request');

function mojo_panel_save_exchange_request()
{
    $ownerFrom = intval($_POST['ownerFrom'] ?? 0);

    $id_calendar = intval($_POST['calendarId'] ?? 0);

    $start_from = $_POST['startFrom'] ?? '';
    $end_from = $_POST['endFrom'] ?? '';

    $start_to = $_POST['startTo'] ?? '';
    $end_to = $_POST['endTo'] ?? '';

    $isPreviousBookingAnAmpliation = filter_var($_POST['previous_ampliation'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $qtyDaysValidation = filter_var($_POST['qtyDaysValidation'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$start_from || !$start_to || !$end_from || !$end_to || !$ownerFrom || !$id_calendar) {
        wp_send_json_error(['message' => 'Missing reservation information.']);
    }

    $exchangeRequestService = new ExchangeRequestService();
    $booking_service = new BookingService();
    $calendar_service = new CalendarService();
    $template_service = new TemplateService();
    $notification_service = new NotificationService();
    $owner_service = new OwnerService();

    try {

        $templates7 = $template_service->getNotifications(7);

        $is_selecting_two_bookings = $booking_service->IsSelectingTwoBookings($id_calendar, $start_to, $end_to);
        if ($is_selecting_two_bookings) {
            wp_send_json_error(['message' => 'Make sure you do not select 2 ranges from different owners.']);
        }

        /*$is_ampliation = $booking_service->IsDateTheStartOrEndOfTheBooking($id_calendar, $start_to, $end_to);
        if ($isPreviousBookingAnAmpliation) {
            if (!$is_ampliation) {
                wp_send_json_error(['message' => 'You need to choose another reservation that you can extend.']);
            }
        } else {
            if ($qtyDaysValidation) {
                wp_send_json_error(['message' => 'A minimum of 3 consecutive nights must be selected.']);
            }
        }*/

        $ownerTo = $booking_service->selectBookingIdByDatesWithIn($id_calendar, $start_to, $end_to);
        if (!$ownerTo) {
            wp_send_json_error(['message' => 'Reservation not found.', 'ownerTo' => $ownerTo]);
        }

        $response = $exchangeRequestService->createExchangeRequest($id_calendar, $ownerFrom, $ownerTo, $start_from, $end_from, $start_to, $end_to, 'pending');
        if ($response) {

            if (NOT_APPEAR) {

                $propertyInfo = $calendar_service->getPropertyByCalendar($id_calendar);
                $ownerFromInfo = $owner_service->getOwner($ownerFrom);
                $ownerToInfo = $owner_service->getOwner($ownerTo);

                $placeholders = [
                    '[REQUESTOR]'     => $ownerFromInfo->getName(),
                    '[RECIPIENT]' => $ownerToInfo->getName(),
                    '[REQUESTOR_DATES]'  => format_date($start_from) . ' - ' . format_date($end_from),
                    '[RECIPIENT_DATES]'  => format_date($start_to) . ' - ' . format_date($end_to),
                    '[PROPERTY]'    => $propertyInfo->getName()
                ];

                if ($templates7->email_enabled) {
                    $body = $templates7->body ?? '';

                    $active_placeholders = array_filter($placeholders, function ($key) use ($body) {
                        return strpos($body, $key) !== false;
                    }, ARRAY_FILTER_USE_KEY);

                    $message = str_replace(array_keys($active_placeholders), array_values($active_placeholders), $body);
                    $email   = get_exchange_email();

                    cs_log('Sending email notification for exchange dates.', [
                        'subject' => $templates7->subject,
                        'message' => $message,
                        'to'      => $email
                    ]);

                    send_notification_email($templates7->subject, $message, $email);
                }

                // ---------------------------------

                if ($templates7->push_enabled) {

                    $active_placeholders = array_filter($placeholders, function ($key) use ($templates7) {
                        return strpos($templates7->message, $key) !== false;
                    }, ARRAY_FILTER_USE_KEY);

                    $push = str_replace(array_keys($active_placeholders), array_values($active_placeholders), $templates7->message);

                    $notification_service->createNotification($ownerFrom, $push);
                }
            }

            wp_send_json_success(['message' => 'Reservations exchanged']);
        } else {
            wp_send_json_error(['message' => $response['msg'] ?? 'Could not exchange reservations.']);
        }
    } catch (Throwable $e) {
        error_log('Error en mojo_panel_exchange_dates: ' . $e->getMessage());
        wp_send_json_error(['message' => 'A problem occurred while trying to exchange reservations.']);
    }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_book_period', 'mojo_panel_book_period');
add_action('wp_ajax_mojo_panel_book_period', 'mojo_panel_book_period');

function mojo_panel_book_period()
{
    $calendar_id = $_POST['calendar_id'] ?? '';
    $start_date = $_POST['start'] ?? '';
    $end_date = $_POST['end'] ?? '';
    $owner_id = $_POST['owner_id'] ?? '';
    $in_round = $_POST['round'] ?? 1;
    $owner_position = $_POST['owner_position'] ?? '';
    $use = 'for personal use';

    if (empty($calendar_id) || empty($start_date) || empty($end_date) || empty($owner_id) || empty($owner_position) || empty($in_round)) {
        wp_send_json_error(['message' => 'A problem occurred while trying to book the selected dates']);
    }

    $booking_service = new BookingService();
    $calendar_service = new CalendarService();
    $template_service = new TemplateService();
    $notification_service = new NotificationService();

    try {
        $templates2 = $template_service->getNotifications(2);

        $property = $calendar_service->getPropertyByCalendar($calendar_id);

        $creating_booking = $booking_service->createBooking($calendar_id, $start_date, $end_date, $owner_id, $owner_position, $in_round, $use);

        if ($creating_booking) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'A problem occurred while trying to book the selected dates']);
        }
    } catch (Throwable $e) {
        error_log('Error en mojo_panel_book_period: ' . $e->getMessage());
        wp_send_json_error(['message' => 'A problem occurred while trying to book the selected dates.']);
    }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_confirm_reservation', 'mojo_panel_confirm_reservation');
add_action('wp_ajax_mojo_panel_confirm_reservation', 'mojo_panel_confirm_reservation');

function mojo_panel_confirm_reservation()
{
    $calendar_id = $_POST['calendar_id'] ?? '';
    $owner_id = $_POST['owner_id'] ?? '';
    $in_round = $_POST['round'] ?? 1;
    $owner_position = $_POST['owner_position'] ?? '';
    $use = 'for personal use';

    if (empty($calendar_id) || empty($owner_id) || empty($owner_position) || empty($in_round)) {
        wp_send_json_error(['message' => 'A problem occurred while trying to book the selected dates']);
    }

    $booking_service = new BookingService();
    $calendar_service = new CalendarService();
    $template_service = new TemplateService();
    $notification_service = new NotificationService();

    try {
        $templates2 = $template_service->getNotifications(2);

        $property = $calendar_service->getPropertyByCalendar($calendar_id);

        $selected_days = $booking_service->getSelectedDates($calendar_id, $owner_position, $in_round);
        $max_days = $calendar_service->getMaxDays4Select($calendar_id);

        if ($selected_days < $max_days) {
            wp_send_json_success();
        } else {
            // $go_to_next_turn = $calendar_service->passTurn($calendar_id, $owner_position);
            $go_to_next_turn = $calendar_service->passTurn($calendar_id);
            if ($go_to_next_turn['success']) {

                if (isset($go_to_next_turn['next_owner']) && !empty($go_to_next_turn['next_owner']) && $go_to_next_turn['next_owner']['id'] != NOT_ASSIGNED_ID) {

                    $email = $go_to_next_turn['next_owner']['email'] ?? '';
                    $name = $go_to_next_turn['next_owner']['name'] ?? '';
                    $phone = $go_to_next_turn['next_owner']['phone'] ?? '';

                    $placeholders = [
                        '[NAME]'     => $name,
                        '[PROPERTY]' => $property->getName(),
                        '[PHONE]'    => $phone,
                        '[EMAIL]'    => $email
                    ];

                    if ($templates2->email_enabled) {

                        $active_placeholders1 = array_filter($placeholders, function ($key) use ($templates2) {
                            return strpos($templates2->body, $key) !== false;
                        }, ARRAY_FILTER_USE_KEY);

                        $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $templates2->body);

                        send_notification_email($templates2->subject, $message, $email);
                    }

                    // ---------------------------------

                    if ($templates2->push_enabled) {

                        $active_placeholders2 = array_filter($placeholders, function ($key) use ($templates2) {
                            return strpos($templates2->message, $key) !== false;
                        }, ARRAY_FILTER_USE_KEY);

                        $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $templates2->message);

                        $notification_service->createNotification($go_to_next_turn['next_owner']['id'], $push);
                    }

                    wp_send_json_success();
                } else {
                    wp_send_json_success();
                }
            } else {
                wp_send_json_error(['message' => 'A problem occurred while trying to pass to the next turn']);
            }
        }
    } catch (Throwable $e) {
        error_log('Error en mojo_panel_confirm_reservation: ' . $e->getMessage());
        wp_send_json_error(['message' => 'A problem occurred while trying to book the selected dates.']);
    }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_delete_booked_date', 'mojo_panel_delete_booked_date');
add_action('wp_ajax_mojo_panel_delete_booked_date', 'mojo_panel_delete_booked_date');

function mojo_panel_delete_booked_date()
{
    $booked_date_id = $_POST['booked_date_id'] ?? '';

    if (empty($booked_date_id)) {
        wp_send_json_error(['message' => 'A problem occurred while trying to delete the reserved date']);
    }

    $booking_service = new BookingService();

    $delete_booked_date = $booking_service->deleteBooking($booked_date_id);

    if ($delete_booked_date) {
        wp_send_json_success();
    } else {
        error_log('A problem occurred while trying to delete the reserved date with ID: ' . $booked_date_id);
        wp_send_json_error(['message' => 'A problem occurred while trying to delete the reserved date']);
    }
}


add_action('wp_ajax_nopriv_mojo_panel_rent_period', 'mojo_panel_rent_period');
add_action('wp_ajax_mojo_panel_rent_period', 'mojo_panel_rent_period');

function mojo_panel_rent_period()
{
    $calendar_id    = $_POST['calendar_id'] ?? '';
    $start_date     = $_POST['start'] ?? '';
    $end_date       = $_POST['end'] ?? '';
    $owner_id       = $_POST['owner_id'] ?? '';
    $in_round       = $_POST['round'] ?? 1;
    $owner_position = $_POST['owner_position'] ?? '';

    if (empty($calendar_id) || empty($start_date) || empty($end_date) || empty($owner_id) || empty($owner_position)) {
        error_log("❌ A problem occurred while trying to rent the selected dates");
        wp_send_json_error(['message' => 'A problem occurred while trying to rent the selected dates']);
    }

    $calendar_service = new CalendarService();
    $calendar = $calendar_service->getCalendar($calendar_id);
    if (!$calendar) {
        wp_send_json_error(['message' => 'The selected calendar could not be found.']);
    }

    $property_service = new PropertyService();
    $id_property = $calendar->getPropertyId();
    $property = $property_service->getProperty($id_property);
    if (!$property) {
        wp_send_json_error(['message' => 'The property could not be found.']);
    }

    $owner_service = new OwnerService();
    $owner = $owner_service->getOwner($owner_id, false);
    if (!$owner) {
        wp_send_json_error(['message' => 'The owner could not be found.']);
    }

    $formatted_start = date('d/m/Y', strtotime($start_date));
    $formatted_end   = date('d/m/Y', strtotime($end_date));

    // ------------------------------------------------------------------------
    // EXCHANGE REQUESTS GUARD: impedir rent si toca con solicitudes de intercambio
    // ------------------------------------------------------------------------

    $exchangeRequestService  = new ExchangeRequestService();

    // Solo considerar solicitudes 'pending'. Si quieres TODAS, elimina la condición de estado.
    if ($exchangeRequestService->isDateBlockedByExchange(
        (int) $calendar_id,
        $start_date,
        $end_date
    )) {
        wp_send_json_error([
            'message' => 'You cannot rent days that are on an exchange request.'
        ]);
    }

    // ------------------------------------------------------------------------
    // Notifications
    // ------------------------------------------------------------------------

    $template_service     = new TemplateService();
    $notification_service = new NotificationService();

    $templates5 = $template_service->getNotifications(5);

    $placeholders = [
        '[OWNER_NAME]'     => esc_html($owner['name']),
        '[OWNER_POSITION]' => esc_html($owner_position),
        '[FROM_DATE]'     => esc_html($formatted_start),
        '[TO_DATE]'      => esc_html($formatted_end),
        '[PROPERTY]'       => esc_html($property->getName()),
    ];

    // Email
    if ($templates5->email_enabled) {
        $body = $templates5->body ?? '';

        $active_placeholders1 = array_filter($placeholders, function ($key) use ($body) {
            return strpos($body, $key) !== false;
        }, ARRAY_FILTER_USE_KEY);

        $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $body);
        $email   = get_rent_email();

        cs_log('Sending email notification for rent a period.', [
            'subject' => $templates5->subject,
            'message' => $message,
            'to'      => $email
        ]);

        send_notification_email($templates5->subject, $message, $email);
    }

    // Push
    if ($templates5->push_enabled) {
        $msgTemplate = $templates5->message ?? '';

        $active_placeholders2 = array_filter($placeholders, function ($key) use ($msgTemplate) {
            return strpos($msgTemplate, $key) !== false;
        }, ARRAY_FILTER_USE_KEY);

        $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $msgTemplate);

        cs_log('Creating push notification for turn passing.', [
            'owner_id' => $owner_id,
            'push'     => $push
        ]);

        $pushing_notification = $notification_service->createNotification($owner_id, $push);
    }

    // ------------------------------------------------------------------------
    // BOOKINGS LOGIC
    // ------------------------------------------------------------------------

    $booking_service = new BookingService();
    $rows = $booking_service->getBookingByYear($calendar_id, '', 2);

    // bookings del owner, tipo "for personal use"
    $owner_personal_bookings = array_filter($rows, function ($row) use ($owner_id) {
        return $row->getType() === 'for personal use'
            && $row->getOwnerId() == $owner_id;
    });

    // orden por fecha inicio asc
    usort($owner_personal_bookings, function ($a, $b) {
        return strcmp($a->getStartDate(), $b->getStartDate());
    });

    // Paso 1: validar que la selección está cubierta por cadena continua
    $merged_ranges = merge_ranges($owner_personal_bookings);

    $covered = false;
    foreach ($merged_ranges as $range) {
        if ($start_date >= $range['start'] && $end_date <= $range['end']) {
            $covered = true;
            break;
        }
    }

    if (!$covered) {
        wp_send_json_error(['message' => 'No valid booking found to update']);
    }

    if (!empty($owner_personal_bookings)) {

        foreach ($owner_personal_bookings as $row) {
            $existing_start = $row->getStartDate();
            $existing_end   = $row->getEndDate();
            $booking_id     = $row->getId();
            $row_owner_pos  = $row->getOwnerPosition(); // <- posición ORIGINAL del booking

            // margen para considerar bookings contiguos
            $day_after_existing_start = date('Y-m-d', strtotime($existing_start . ' -1 day'));
            $day_before_existing_end  = date('Y-m-d', strtotime($existing_end . ' +1 day'));

            // si este booking no toca para nada el rango seleccionado (ni por borde), saltamos
            if ($day_before_existing_end < $start_date || $day_after_existing_start > $end_date) {
                continue;
            }

            // ✅ ya no filtramos por owner_position, solo por owner_id
            if ($row->getOwnerId() == $owner_id) {

                // 1. borrar el booking original completo
                $booking_service->deleteBooking($booking_id);

                // 2. recrear tramo "antes", si existe parte antes del inicio rentado
                if ($existing_start < $start_date) {
                    $booking_service->createRentedBooking(
                        $calendar_id,
                        $existing_start,
                        $start_date,
                        $owner_id,
                        $row_owner_pos,      // <- usamos la posición ORIGINAL de ese booking
                        $in_round,
                        'for personal use'
                    );
                }

                // 3. recrear tramo "después", si existe parte después del final rentado
                if ($existing_end > $end_date) {
                    $booking_service->createRentedBooking(
                        $calendar_id,
                        $end_date,
                        $existing_end,
                        $owner_id,
                        $row_owner_pos,      // <- usamos la posición ORIGINAL de ese booking
                        $in_round,
                        'for personal use'
                    );
                }
            }
        }

        // 4. crear el bloque 'for rent' una sola vez con owner_position = null
        $booking_service->createRentedBooking(
            $calendar_id,
            $start_date,
            $end_date,
            $owner_id,
            null, // siempre null para alquiler
            $in_round,
            'for rent'
        );

        wp_send_json_success(['message' => 'Booking updated successfully']);
    }

    wp_send_json_error(['message' => 'No valid booking found to update']);
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_request_period', 'mojo_panel_request_period');
add_action('wp_ajax_mojo_panel_request_period', 'mojo_panel_request_period');

function mojo_panel_request_period()
{
    $property_service = new PropertyService();
    $calendar_service = new CalendarService();
    $owner_service = new OwnerService();

    // Primero: obtener datos del POST
    $calendar_id    = $_POST['calendar_id'] ?? '';
    $owner_position = $_POST['owner_position'] ?? '';
    $start_date     = $_POST['start'] ?? '';
    $end_date       = $_POST['end'] ?? '';
    $in_round       = $_POST['round'] ?? 1;
    $owner_id       = $_POST['owner_id'] ?? '';

    // Luego: validar
    if (
        empty($calendar_id) ||
        empty($start_date) ||
        empty($end_date) ||
        empty($owner_id) ||
        empty($owner_position) ||
        empty($in_round)
    ) {
        error_log("❌ A problem occurred while trying to request the selected dates");
        wp_send_json_error(['message' => 'A problem occurred while trying to request the selected dates']);
    }

    // Continuar
    $calendar = $calendar_service->getCalendar($calendar_id);
    if (!$calendar) {
        wp_send_json_error(['message' => 'The selected calendar could not be found.']);
    }

    $id_property = $calendar->getPropertyId();
    $property = $property_service->getProperty($id_property);
    if (!$property) {
        wp_send_json_error(['message' => 'The property could not be found.']);
    }

    $owner = $owner_service->getOwner($owner_id, false);
    if (!$owner) {
        wp_send_json_error(['message' => 'The owner could not be found.']);
    }

    $formatted_start = date('d/m/Y', strtotime($start_date));
    $formatted_end = date('d/m/Y', strtotime($end_date));

    $message = '
        <p>Hi,</p>
        <p>A new <strong>Rent Request</strong> has been submitted with the following details:</p>
        <p>Owner <b>' . esc_html($owner['name']) . ' (Share #' . esc_html($owner_position) . ')</b> made a request for the <br>
        dates from <b>' . esc_html($formatted_start) . '</b> to <b>' . esc_html($formatted_end) . '</b>, on the property <strong>' . esc_html($property->getName()) . '</strong></p>
        <p>Please verify this request from the admin panel.</p>
        <p>Best regards,<br>Your Mojo Sharing System</p>
    ';

    send_notification_email('Mojo Sharing - A New Rent Request', $message, get_rent_email());
    error_log("✅ Request for Personal Use");
    wp_send_json_success(['message' => 'Request submitted successfully.']);
}

// ------------------------------------------------------------------------
// ------------------------------------------------------------------------

add_action('wp_ajax_nopriv_mojo_panel_remove_period', 'mojo_panel_remove_period');
add_action('wp_ajax_mojo_panel_remove_period', 'mojo_panel_remove_period');

function mojo_panel_remove_period()
{
    $property_service = new PropertyService();
    $calendar_service = new CalendarService();
    $owner_service = new OwnerService();
    $booking_service = new BookingService();

    $id_period    = $_POST['id_period'] ?? '';

    if (empty($id_period)) {
        error_log("❌ A problem occurred while trying to remove the selected dates.");
        wp_send_json_error(['message' => 'A problem occurred while trying to request the selected dates.']);
    }

    $booking = $booking_service->selectBooking($id_period);
    if (!$booking) {
        error_log("❌ A problem occurred while trying to find the booking.");
        wp_send_json_error(['message' => 'The owner could not be found.']);
    }

    $deleting = $booking_service->deleteBooking($id_period);

    if ($deleting) {
        error_log("✅ Reservation removed");
        wp_send_json_success(['message' => 'Request submitted successfully.']);
    } else {
        error_log("❌ A problem occurred while trying to remove the reservation.");
        wp_send_json_error(['message' => 'A problem occurred while trying to remove the reservation.']);
    }
}