<?php

require_once __DIR__ . '../../../../services/CalendarService.php';
require_once __DIR__ . '../../../../services/SeasonsService.php';
require_once __DIR__ . '../../../../services/BookingService.php';
require_once __DIR__ . '../../../../services/PropertyService.php';
require_once __DIR__ . '../../../../services/OwnerService.php';
require_once __DIR__ . '../../../../services/TemplateService.php';
require_once __DIR__ . '../../../../services/NotificationService.php';
require_once __DIR__ . '../../../../services/BlockedDatesService.php';
require_once __DIR__ . '../../../../services/ExchangeRequestService.php';
require_once __DIR__ . '../../../../services/CommentService.php';

require_once __DIR__ . '../../../../repositories/BookingRepository.php';

function calendar_system_edit_page_callback()
{
    $calendar_service = new CalendarService();
    $seasons_service = new SeasonsService();
    $booking_service = new BookingService();
    $property_service = new PropertyService();
    $owner_service = new OwnerService();
    $template_service = new TemplateService();
    $notification_service = new NotificationService();
    $blockdates_service = new BlockedDatesService();
    $comment_service = new CommentService();

    $id_calendar = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id_calendar) {
        echo '<br><div class="notice notice-error is-dismissible"><p>Invalid ID.</p></div>';
        return;
    }

    $comments = $comment_service->getCommentsByCalendar($id_calendar);

    // open calendar
    if (isset($_POST['open_calendar'])) {
        $year = sanitize_text_field($_POST['year']);

        cs_log('Attempting to open calendar.', [
            'id_calendar' => $id_calendar,
            'year'        => $year
        ]);

        $qty_seasons = $seasons_service->getSeasonsByYear($year);
        if (!$qty_seasons) {
            cs_log('Calendar opening aborted: no seasons configured for the year.', [
                'year' => $year
            ]);
            echo '<div class="notice notice-error is-dismissible"><p>Before opening the calendar you must have established all the seasons of the year <b>' . $year . '</b>.</p></div>';
        } else {
            $property_id = sanitize_text_field($_POST['property_id']);

            $ordered_owners_raw = $_POST['ordered_owners'];
            $ordered_colors_raw = $_POST['ordered_colors'];

            $ordered_owners = json_decode(stripslashes($ordered_owners_raw), true);
            $ordered_colors = json_decode(stripslashes($ordered_colors_raw), true);

            if (!is_array($ordered_owners)) {
                cs_log('Invalid owners data received when opening calendar.', [
                    'data' => $ordered_owners_raw
                ]);
                echo '<div class="notice notice-error is-dismissible"><p>Invalid data received for owners names.</p></div>';
                return;
            }

            if (!is_array($ordered_colors)) {
                cs_log('Invalid owners colors data received when opening calendar.', [
                    'data' => $ordered_colors_raw
                ]);
                echo '<div class="notice notice-error is-dismissible"><p>Invalid data received for owners colors.</p></div>';
                return;
            }

            $all_are_1 = !empty($ordered_owners) && count(array_unique($ordered_owners)) == NOT_ASSIGNED_ID && reset($ordered_owners) == NOT_ASSIGNED_ID;

            if ($all_are_1) {
                cs_log('Calendar opening aborted: all owners are NOT_ASSIGNED_ID.', [
                    'owners' => $ordered_owners
                ]);
                echo '<div class="notice notice-error is-dismissible"><p>You must have selected at least one owner for the property.</p></div>';
            } else {
                cs_log('Proceeding with calendar opening.', [
                    'property_id'    => $property_id,
                    'ordered_owners' => $ordered_owners
                ]);

                $opening_calendar = $calendar_service->updateCalendar($id_calendar, $property_id, $year, $ordered_owners_raw, 1, 1, 'open', $ordered_colors_raw);

                if ($opening_calendar) {

                    cs_log('Calendar opened successfully.', [
                        'id_calendar' => $id_calendar,
                        'property_id' => $property_id
                    ]);

                    $current_property = $property_service->getProperty($property_id);

                    if (!empty($ordered_owners)) {
                        $unique_owners = array_unique($ordered_owners);
                        $list_owners_to_send_email = array_filter($unique_owners, function ($value) {
                            return $value !== NOT_ASSIGNED_ID;
                        });
                        $list_owners_to_send_email = array_values($list_owners_to_send_email);

                        // When the calendar opens
                        foreach ($list_owners_to_send_email as $id_owner) {
                            $owner = $owner_service->getOwner($id_owner, false);
                            $email = $owner['email'] ?? '';

                            if (!empty($email)) {

                                cs_log('Sending email notification.', [
                                    'owner_id' => $id_owner,
                                    'email'    => $email
                                ]);

                                $placeholders = [
                                    '[NAME]'     => $owner['name'] ?? '',
                                    '[PROPERTY]' => $current_property->getName(),
                                    '[PHONE]'    => $owner['phone'] ?? '',
                                    '[EMAIL]'    => $email
                                ];

                                $templates1 = $template_service->getNotifications(1);

                                // ---------------------------------------------------------------------------------------------------
                                // ---------------------------------------------------------------------------------------------------

                                $body = $templates1->body ?? '';

                                $active_placeholders1 = array_filter($placeholders, function ($key) use ($body) {
                                    return strpos($body, $key) !== false;
                                }, ARRAY_FILTER_USE_KEY);

                                $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $body);

                                // ---------------------------------------------------------------------------------------------------
                                // ---------------------------------------------------------------------------------------------------

                                if ($templates1->push_enabled) {
                                    $message = $templates1->message ?? '';

                                    $active_placeholders2 = array_filter($placeholders, function ($key) use ($message) {
                                        return strpos($message, $key) !== false;
                                    }, ARRAY_FILTER_USE_KEY);

                                    $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $message);

                                    $pushing_notification = $notification_service->createNotification($id_owner, $push);
                                }

                                // ---------------------------------------------------------------------------------------------------
                                // ---------------------------------------------------------------------------------------------------

                                $attempts = 0;
                                $max_attempts = 5;

                                do {
                                    $attempts++;
                                    if ($templates1->email_enabled) {
                                        $sent = send_notification_email($templates1->subject, $message, $email);
                                        if (!$sent) {
                                            error_log("❌ Intento $attempts fallido para $email");
                                            sleep(2); // espera antes de reintentar
                                        }
                                    } else {
                                        $sent = false;
                                    }
                                } while (!$sent && $attempts < $max_attempts);

                                if ($sent) {
                                    error_log("✅ Correo enviado exitosamente a $email en intento $attempts");
                                } else {
                                    error_log("🚨 Fallo permanente al enviar a $email después de $attempts intentos");
                                }

                                sleep(1); // espera después de envío exitoso
                            } else {
                                cs_log('Skipped notification: owner has no email.', [
                                    'owner_id' => $id_owner
                                ]);
                            }
                        }


                        // When turn starts
                        $first_owner = $owner_service->getOwner($list_owners_to_send_email[0], false);
                        if ($first_owner && $first_owner['email']) {

                            $templates2 = $template_service->getNotifications(2);

                            $placeholders = [
                                '[NAME]'     => $first_owner['name'],
                                '[PROPERTY]' => $current_property->getName(),
                                '[PHONE]'    => $first_owner['phone'],
                                '[EMAIL]'    => $first_owner['email']
                            ];

                            // EMAIL notification
                            if ($templates2->email_enabled) {

                                $body = $templates2->body ?? '';

                                $active_placeholders1 = array_filter($placeholders, function ($key) use ($body) {
                                    return strpos($body, $key) !== false;
                                }, ARRAY_FILTER_USE_KEY);

                                $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $body);

                                cs_log('Sending email notification for turn passing.', [
                                    'subject' => $templates2->subject,
                                    'message' => $message,
                                    'to'      => $email
                                ]);

                                send_notification_email($templates2->subject, $message, $email);
                            }

                            // PUSH notification
                            if ($templates2->push_enabled) {

                                $message = $templates2->message ?? '';

                                $active_placeholders2 = array_filter($placeholders, function ($key) use ($message) {
                                    return strpos($message, $key) !== false;
                                }, ARRAY_FILTER_USE_KEY);

                                $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $message);

                                cs_log('Creating push notification for turn passing.', [
                                    'owner_id' => $first_owner['id'],
                                    'push'     => $push
                                ]);

                                $notification_service->createNotification($first_owner['id'], $push);
                            }
                        }
                    }

                    echo '<div class="notice notice-success is-dismissible"><p>Calendar opened successfully.</p></div>';
                } else {
                    cs_log('Failed to open calendar.', [
                        'id_calendar' => $id_calendar,
                        'property_id' => $property_id
                    ]);
                    echo '<div class="notice notice-error is-dismissible"><p>There was an error opening the calendar.</p></div>';
                }
            }
        }
    }

    if (isset($_POST['reset_calendar'])) {

        $year        = (int) sanitize_text_field($_POST['year']);
        $propertyId  = (int) sanitize_text_field($_POST['property_id']);

        $result = $calendar_service->resetCalendarAndBookings(
            (int) $id_calendar,
            $year,
            $propertyId
        );

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>Calendar rebooted successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>There was an error rebooting the calendar.</p></div>';
        }
    }

    if (isset($_POST['pause_calendar'])) {

        $property_id = (int) $_POST['property_id'];

        $paused = $calendar_service->pauseCalendar(
            (int) $id_calendar,
            $property_id
        );

        if ($paused) {
            echo '<div class="notice notice-success is-dismissible">
                    <p>Calendar paused successfully.</p>
                </div>';
        } else {
            echo '<div class="notice notice-error is-dismissible">
                    <p>There was an error stopping the calendar.</p>
                </div>';
        }
    }

    if (isset($_POST['resume_calendar'])) {

        $property_id = (int) $_POST['property_id'];

        $resumed = $calendar_service->resumeCalendar(
            (int) $id_calendar,
            $property_id
        );

        if ($resumed) {
            echo '<div class="notice notice-success is-dismissible">
                    <p>Calendar opened successfully.</p>
                </div>';
        } else {
            echo '<div class="notice notice-error is-dismissible">
                    <p>There was an error opening the calendar.</p>
                </div>';
        }
    }

    if (isset($_POST['toggle_download_calendar_update'])) {
        $raw = isset($_POST['toggle_download_calendar']) ? wp_unslash($_POST['toggle_download_calendar']) : null;
        $value = ($raw === '1' || $raw === 1) ? 1 : 0;

        $updated = $calendar_service->updateCalendar(
            (int) $id_calendar,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $value
        );
        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Download calendar setting updated.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>There was an error updating the setting.</p></div>';
        }
    }

    // pass the turn
    if (isset($_POST['pass_turn'])) {
        $property_id    = sanitize_text_field($_POST['property_id']);
        $owner_position = sanitize_text_field($_POST['owner_position']);

        cs_log('Pass turn request received.', [
            'id_calendar'    => $id_calendar,
            'property_id'    => $property_id,
            'owner_position' => $owner_position
        ]);

        // $update = $calendar_service->passTurn($id_calendar, $owner_position);
        $update = $calendar_service->passTurn($id_calendar);

        if ($update['success']) {
            cs_log('Turn passed successfully.', [
                'message'    => $update['message'],
                'next_owner' => $update['next_owner'] ?? null
            ]);

            $property = $property_service->getProperty($property_id);

            if (isset($update['next_owner']) && !empty($update['next_owner']) && $update['next_owner']['id'] != NOT_ASSIGNED_ID) {
                $email = $update['next_owner']['email'] ?? '';
                $name  = $update['next_owner']['name'] ?? '';
                $phone = $update['next_owner']['phone'] ?? '';

                cs_log('Next owner found for turn passing.', [
                    'id'    => $update['next_owner']['id'],
                    'name'  => $name,
                    'email' => $email,
                    'phone' => $phone
                ]);

                $placeholders = [
                    '[NAME]'     => $name,
                    '[PROPERTY]' => $property->getName(),
                    '[PHONE]'    => $phone,
                    '[EMAIL]'    => $email
                ];

                $templates2 = $template_service->getNotifications(2);

                // EMAIL notification
                if ($templates2->email_enabled) {

                    $body = $templates2->body ?? '';

                    $active_placeholders1 = array_filter($placeholders, function ($key) use ($body) {
                        return strpos($body, $key) !== false;
                    }, ARRAY_FILTER_USE_KEY);

                    $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $body);

                    cs_log('Sending email notification for turn passing.', [
                        'subject' => $templates2->subject,
                        'message' => $message,
                        'to'      => $email
                    ]);

                    send_notification_email($templates2->subject, $message, $email);
                }

                // PUSH notification
                if ($templates2->push_enabled) {

                    $message = $templates2->message ?? '';

                    $active_placeholders2 = array_filter($placeholders, function ($key) use ($message) {
                        return strpos($message, $key) !== false;
                    }, ARRAY_FILTER_USE_KEY);

                    $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $message);

                    cs_log('Creating push notification for turn passing.', [
                        'owner_id' => $update['next_owner']['id'],
                        'push'     => $push
                    ]);

                    $notification_service->createNotification($update['next_owner']['id'], $push);
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . $update['message'] . '</p></div>';
        } else {
            cs_log('Failed to pass turn.', [
                'message' => $update['message']
            ]);

            echo '<div class="notice notice-error is-dismissible"><p>' . $update['message'] . '</p></div>';
        }

        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    localStorage.removeItem("mojo_date1_' . $id_calendar . '");localStorage.removeItem("mojo_date2_' . $id_calendar . '");
                });
            </script>';
    }

    // create period
    if (isset($_POST['create_period'])) {
        $start_date     = sanitize_text_field($_POST['start_date']);
        $end_date       = sanitize_text_field($_POST['end_date']);
        $owner_selected = sanitize_text_field($_POST['owner_selected']);
        $use_selected   = sanitize_text_field($_POST['use_selected']);
        $in_round       = sanitize_text_field($_POST['in_round']);

        if (empty($start_date) || empty($end_date)) {
            cs_log('Create period failed: start date or end date is missing.', [
                'start_date' => $start_date,
                'end_date'   => $end_date
            ]);
            echo '<div class="notice notice-error is-dismissible"><p>There was an error in the reserved dates.</p></div>';
        } elseif (empty($owner_selected)) {
            cs_log('Create period failed: owner not selected.');
            echo '<div class="notice notice-error is-dismissible"><p>There was an error when choosing the owner for the reservation.</p></div>';
        } elseif (empty($use_selected) || empty($in_round)) {
            cs_log('Create period failed: missing "use_selected" or "in_round" values.', [
                'use_selected' => $use_selected,
                'in_round'     => $in_round
            ]);
            echo '<div class="notice notice-error is-dismissible"><p>There was an error booking a period.</p></div>';
        } else {
            $current_selected_owner = explode(" - ", $owner_selected);
            $owner_position         = $current_selected_owner[0];
            $owner_id               = $current_selected_owner[1];

            cs_log('Attempting to create booking.', [
                'id_calendar'    => $id_calendar,
                'start_date'     => $start_date,
                'end_date'       => $end_date,
                'owner_id'       => $owner_id,
                'owner_position' => $owner_position,
                'in_round'       => $in_round,
                'use_selected'   => $use_selected
            ]);

            $creating_booking = $booking_service->createBooking($id_calendar, $start_date, $end_date, $owner_id, $owner_position, $in_round, $use_selected);

            if ($creating_booking) {
                cs_log('Booking created successfully.', [
                    'id_calendar'    => $id_calendar,
                    'start_date'     => $start_date,
                    'end_date'       => $end_date,
                    'owner_id'       => $owner_id,
                    'owner_position' => $owner_position
                ]);
                echo '<div class="notice notice-success is-dismissible"><p>Booking created successfully.</p></div>';
            } else {
                cs_log('Failed to create booking.', [
                    'id_calendar'    => $id_calendar,
                    'start_date'     => $start_date,
                    'end_date'       => $end_date,
                    'owner_id'       => $owner_id,
                    'owner_position' => $owner_position
                ]);
                echo '<div class="notice notice-error is-dismissible"><p>There was an error booking the period in the calendar.</p></div>';
            }

            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    localStorage.removeItem("mojo_date1_' . $id_calendar . '");localStorage.removeItem("mojo_date2_' . $id_calendar . '");
                });
            </script>';
        }
    }

    // update period
    if (isset($_POST['edit_period'])) {
        $start_date     = sanitize_text_field($_POST['start_date_r']);
        $end_date       = sanitize_text_field($_POST['end_date_r']);
        $owner_selected = sanitize_text_field($_POST['owner_selected_r']);
        $use_selected   = sanitize_text_field($_POST['use_selected_r']);
        $in_round       = sanitize_text_field($_POST['in_round']);
        $id_booking     = !empty($_POST['id_booking_r']) ? sanitize_text_field($_POST['id_booking_r']) : '';

        if (empty($start_date) || empty($end_date)) {
            cs_log('Update period failed: start date or end date is missing.', [
                'start_date' => $start_date,
                'end_date'   => $end_date
            ]);
            echo '<div class="notice notice-error is-dismissible"><p>There was an error when separating the reserved date.</p></div>';
        } elseif (empty($owner_selected)) {
            cs_log('Update period failed: owner not selected.');
            echo '<div class="notice notice-error is-dismissible"><p>There was an error when choosing the owner for the reservation.</p></div>';
        } elseif (empty($use_selected) || empty($in_round)) {
            cs_log('Update period failed: missing "use_selected" or "in_round" values.', [
                'use_selected' => $use_selected,
                'in_round'     => $in_round
            ]);
            echo '<div class="notice notice-error is-dismissible"><p>There was an error booking a period.</p></div>';
        } else {
            $current_selected_owner = explode(" - ", $owner_selected);
            $owner_position         = $current_selected_owner[0];
            $owner_id               = $current_selected_owner[1];

            cs_log('Attempting to update booking.', [
                'id_booking'     => $id_booking,
                'id_calendar'    => $id_calendar,
                'start_date'     => $start_date,
                'end_date'       => $end_date,
                'owner_id'       => $owner_id,
                'owner_position' => $owner_position,
                'in_round'       => $in_round,
                'use_selected'   => $use_selected
            ]);

            $updating_booking = $booking_service->updateBooking($id_booking, $id_calendar, $start_date, $end_date, $owner_id, $owner_position, $in_round, $use_selected);

            if ($updating_booking) {
                cs_log('Booking updated successfully.', [
                    'id_booking' => $id_booking
                ]);
                echo '<div class="notice notice-success is-dismissible"><p>Booking updated successfully.</p></div>';
            } else {
                cs_log('Failed to update booking.', [
                    'id_booking' => $id_booking
                ]);
                echo '<div class="notice notice-error is-dismissible"><p>There was an error updating the period in the calendar.</p></div>';
            }
        }
    }

    // delete period
    if (isset($_POST['delete_period'])) {
        $id_booking = !empty($_POST['id_booking_r']) ? sanitize_text_field($_POST['id_booking_r']) : '';

        if (empty($id_booking)) {
            cs_log('Delete period failed: booking ID is missing.');
            echo '<div class="notice notice-error is-dismissible"><p>There was an error deleting a period.</p></div>';
        } else {
            cs_log('Attempting to delete booking.', [
                'id_booking' => $id_booking
            ]);

            $delete_booking = $booking_service->deleteBooking($id_booking);

            if ($delete_booking) {
                cs_log('Booking deleted successfully.', [
                    'id_booking' => $id_booking
                ]);
                echo '<div class="notice notice-success is-dismissible"><p>Booking deleted successfully.</p></div>';
            } else {
                cs_log('Failed to delete booking.', [
                    'id_booking' => $id_booking
                ]);
                echo '<div class="notice notice-error is-dismissible"><p>There was an error deleting the period in the calendar.</p></div>';
            }
        }
    }

    // block the date
    if (isset($_POST['block_date'])) {
        $blocked_raw = isset($_POST['blocked_dates']) ? wp_unslash($_POST['blocked_dates']) : '';
        $dates = cs_normalize_blocked_dates($blocked_raw);

        if (empty($dates)) {
            cs_log('Block dates aborted: empty/invalid.', ['raw' => $blocked_raw]);
            echo '<div class="notice notice-error is-dismissible"><p>No valid dates were received to block.</p></div>';
        } else {
            $ok = 0;
            $fail = [];
            foreach ($dates as $d) {
                // Inserta una por una
                $res = $blockdates_service->createBlockedDate((int)$id_calendar, $d);
                if ($res) {
                    $ok++;
                } else {
                    $fail[] = $d;
                }
            }

            cs_log('Block dates result', [
                'id_calendar' => $id_calendar,
                'received'    => count($dates),
                'inserted'    => $ok,
                'failed'      => $fail
            ]);

            if ($ok && empty($fail)) {
                echo '<div class="notice notice-success is-dismissible"><p>Blocked dates saved successfully (' . $ok . ').</p></div>';
            } elseif ($ok && $fail) {
                echo '<div class="notice notice-warning is-dismissible"><p>Some dates were saved (' . $ok . '), but these failed: <code>' . esc_html(implode(', ', $fail)) . '</code>.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>There was an error saving the blocked dates.</p></div>';
            }

            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    localStorage.removeItem("mojo_date1_' . $id_calendar . '");localStorage.removeItem("mojo_date2_' . $id_calendar . '");
                });
            </script>';
        }
    }

    // delete blocked date
    if (isset($_POST['delete_blocked_date'])) {
        $id_blocked_date = isset($_POST['id_blocked_date']) ? intval($_POST['id_blocked_date']) : 0;

        if ($id_blocked_date <= 0) {
            cs_log('Delete blocked date failed: missing/invalid id.', ['id_blocked_date' => $_POST['id_blocked_date'] ?? null]);
            echo '<div class="notice notice-error is-dismissible"><p>Invalid blocked date ID.</p></div>';
        } else {
            $deleted = $calendar_service->deleteBlockedDate($id_blocked_date, (int)$id_calendar);

            if ($deleted) {
                cs_log('Blocked date deleted successfully.', [
                    'id_blocked_date' => $id_blocked_date,
                    'id_calendar'     => $id_calendar
                ]);
                echo '<div class="notice notice-success is-dismissible"><p>Blocked date deleted.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>There was an error deleting the blocked date.</p></div>';
            }

            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    localStorage.removeItem("mojo_date1_' . $id_calendar . '");localStorage.removeItem("mojo_date2_' . $id_calendar . '");
                });
            </script>';
        }
    }

    if (isset($_POST['add_comment'])) {
        $calendar_id = isset($_POST['calendar_id'])
            ? intval($_POST['calendar_id'])
            : 0;

        $date        = sanitize_text_field($_POST['date'] ?? '');
        $title       = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if ($date) {
            $dt = DateTime::createFromFormat('Y-m-d', $date);
            $date = $dt ? $dt->format('Y-m-d') : '';
        }

        if (empty($calendar_id) || empty($date) || empty($title)) {
            echo '<div class="notice notice-error is-dismissible">
                    <p>Please complete all required fields.</p>
                </div>';
        } else {
            $created = $comment_service->createComment(
                $calendar_id,
                $date,
                $title,
                $description
            );

            if ($created) {
                echo '<div class="notice notice-success is-dismissible">
                        <p>Comment added successfully.</p>
                    </div>';
                echo '<script>window.location.reload();</script>';
            } else {
                echo '<div class="notice notice-error is-dismissible">
                        <p>There was an error saving the comment.</p>
                    </div>';
            }
        }
    }

    if (isset($_POST['update_comment'])) {
        $comment_id  = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        $calendar_id = isset($_POST['calendar_id']) ? intval($_POST['calendar_id']) : 0;
        $title       = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($comment_id) || empty($title)) {
            echo '<div class="notice notice-error is-dismissible">
                    <p>Please complete all required fields.</p>
                </div>';
        } else {
            $updated = $comment_service->updateComment(
                $comment_id,
                $title,
                $description
            );

            if ($updated) {
                echo '<div class="notice notice-success is-dismissible">
                        <p>Comment updated successfully.</p>
                    </div>';
                echo '<script>window.location.reload();</script>';
            } else {
                echo '<div class="notice notice-error is-dismissible">
                        <p>There was an error updating the comment.</p>
                    </div>';
            }
        }
    }

    if (isset($_POST['delete_comment'])) {
        $comment_id  = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;

        if (empty($comment_id)) {
            echo '<div class="notice notice-error is-dismissible">
                    <p>Comment ID missing.</p>
                </div>';
        } else {
            $deleted = $comment_service->deleteComment($comment_id);

            if ($deleted) {
                echo '<div class="notice notice-success is-dismissible">
                        <p>Comment deleted successfully.</p>
                    </div>';
                echo '<script>window.location.reload();</script>';
            } else {
                echo '<div class="notice notice-error is-dismissible">
                        <p>There was an error deleting the comment.</p>
                    </div>';
            }
        }
    }

    $calendar = $calendar_service->getCalendar($id_calendar);
    if (!$calendar) {
        echo '<br><div class="notice notice-error"><p>Calendar not found.</p></div>';
        return;
    }

    $year = $calendar->getYear();

    $id_property = $calendar->getPropertyId();
    $property = $property_service->getProperty($id_property);
    if (!$property) {
        echo '<br><div class="notice notice-error"><p>Property not found.</p></div>';
        return;
    }

    $owners = $owner_service->getOwnersByProperty($id_property);

    // Reindexar los owners por posición para acceso rápido
    $owners_by_position = [];

    if (!empty($owners)) {
        foreach ($owners as $owner) {
            $position = intval($owner['owner_position']);
            $owners_by_position[$position] = $owner; // sobrescribe si hay duplicados
        }
    }

    // Validation to know if everyone has the Not Assigned
    $all_owner_id_is_1 = array_reduce($owners_by_position, function ($carry, $owner) {
        return $carry && ($owner['owner_id'] == NOT_ASSIGNED_ID);
    }, true);

    $qty_shares = intval($property->getShare());
    $status = $calendar->getStatus();
    $calendar_status = !empty($status) ? $status : '';

    $currentRound = !empty($calendar->getRound()) ? intval($calendar->getRound()) : 1;
    $currentTurn = !empty($calendar->getTurn()) ? intval($calendar->getTurn()) : 1;

    $order_owners_4_calendar = [];

    if (!is_null($calendar->getOwnersPriority())) {
        $owners_priority = json_decode(stripslashes($calendar->getOwnersPriority()), true);

        if (!empty($owners_priority)) {
            foreach ($owners_priority as $n => $oid) {
                $owner = $owner_service->getOwner($oid, false);
                $owner['owner_position'] = $n;
                $order_owners_4_calendar[$n] = $owner;
            }
        }
    }

    $max_days = $calendar_service->getMaxDays4Select($id_calendar);
    $selected_days = $booking_service->getSelectedDates($id_calendar, $currentTurn, $currentRound);
?>

    <style>
        .postbox-header-actions button {
            display: block;
            width: 30px;
            height: 30px;
            background: #60C0A8;
            padding: 3px;
            color: white;
            cursor: pointer;
            margin: 0;
            border-radius: 3px;
            overflow: hidden;
            border: none;
        }

        .postbox-header-actions button.disabled {
            opacity: 0.4;
            pointer-events: none;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-user-select: none;
        }

        .postbox-header-actions button svg {
            width: 100%;
            height: 100%;
            object-fit: contain;
            fill: white;
            object-position: center;
        }

        .postbox-header-actions button:not([data-year="<?php echo $year; ?>"]) {
            display: none !important;
        }
    </style>

    <div class="wrap">
        <input type="hidden" class="in_admin">
        <h1 class="wp-heading-inline">Edit Calendar: <u><?php echo esc_attr($property->getName()); ?> - <?php echo $calendar->getYear(); ?></u></h1>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Edit Calendar</h2>
        <br>

        <?php if ($calendar_status == 'close'): ?>
            <?php if (($qty_shares == 5 && $currentRound == 6 && $currentTurn == 1) || ($qty_shares == 8 && $currentRound == 5 && $currentTurn == 8)): ?>
                <style>
                    #owners_schedule td {
                        background: transparent !important;
                    }
                </style>
            <?php endif; ?>
        <?php endif; ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>The picking period of <?php echo esc_attr($property->getName()); ?> is: <b style="color: #2271b1"><?php echo strtoupper($calendar_status); ?></b></h2>
                            <div class="postbox-header-actions" style="padding-inline:12px;">
                                <?php get_booking_calendar($id_property); ?>
                            </div>
                        </div>
                        <div class="inside" style="margin-top:12px">
                            <input type="hidden" name="calendar_id" value="<?php echo $id_calendar; ?>">
                            <input type="hidden" name="max_days" value="<?php echo $max_days; ?>">
                            <input type="hidden" name="selected_days" value="<?php echo $selected_days; ?>">
                            <input type="hidden" name="round" value="<?php echo $currentRound; ?>">
                            <input type="hidden" name="qty_shares" value="<?php echo $qty_shares; ?>">

                            <?php get_first_and_last_day_of_seasons(); ?>

                            <?php if ($calendar_status != 'close'): ?>
                                <h4>SELECTED NIGHTS: <span id="countSelectedCells"><?php echo $selected_days . '/' . $max_days; ?></span></h4>
                            <?php endif; ?>

                            <div class="calendar-edit-box">
                                <div id="calendar" style="width:100%"></div>
                                <div class="comment comment-center" id="comment-center">
                                    <?php include __DIR__ . '/../calendar/components/calendarCommentList.php'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php include __DIR__ . '/../calendar/components/calendarScheduleTable.php'; ?>
                </div>
                <?php include __DIR__ . '/../calendar/components/calendarSidebar.php'; ?>
            </div>
            <br class="clear" />
            <?php if ($calendar_status != 'close'): ?>
                <?php include __DIR__ . '/../calendar/components/calendarBookingPopup.php'; ?>
                <?php include __DIR__ . '/../calendar/components/calendarReassignPopup.php'; ?>
            <?php endif; ?>

            <?php include __DIR__ . '/../calendar/components/calendarPriorityPopup.php'; ?>
            <?php include __DIR__ . '/../calendar/components/calendarCommentPopup.php'; ?>
        </div>
    </div>

    <input type="hidden" id="mojo-admin_ajax" value="<?php echo admin_url('admin-ajax.php'); ?>">
    <input type="hidden" id="mojo-uri" value="<?php echo get_site_url(); ?>">
    <input type="hidden" id="mojo-media" value="<?php echo MEDIA; ?>">

    <script>
        let BLOCKED_DATES = <?php echo wp_json_encode($blockdates_service->getByCalendar($id_calendar)); ?>;

        let events = [
            <?php $booking_service->getBookingByYear($id_calendar); ?>
            <?php get_seasons(); ?>
        ];
    </script>
    <script>
        window.MOJO_COMMENTS = <?php echo wp_json_encode($comments); ?>;
    </script>
<?php
}

require_once __DIR__ . '/../calendar/calendarAjax.php';
require_once __DIR__ . '/../calendar/calendarViewHelpers.php';
require_once __DIR__ . '/../calendar/calendarHelpers.php';
