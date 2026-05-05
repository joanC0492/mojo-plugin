<?php

require_once __DIR__ . '../../../services/CalendarService.php';
require_once __DIR__ . '../../../services/PropertyService.php';
require_once __DIR__ . '../../../services/BookingService.php';
require_once __DIR__ . '../../../services/NotificationService.php';
require_once __DIR__ . '../../../services/PropertyService.php';
require_once __DIR__ . '../../../services/OwnerService.php';
require_once __DIR__ . '../../../services/TemplateService.php';

function update_turns_and_rounds()
{
    cs_log('Cron started: update_turns_and_rounds()');

    global $wpdb;

    $calendar_table = 'cs_calendar';

    $calendar_service     = new CalendarService();
    $booking_service      = new BookingService();
    $notification_service = new NotificationService();
    $property_service     = new PropertyService();
    $template_service     = new TemplateService();
    $owner_service        = new OwnerService();

    // Load templates
    $templates3 = $template_service->getNotifications(3);

    cs_log('Templates loaded', [
        'has_message'             => isset($templates3->message),
        'push_enabled'         => isset($templates3->push_enabled) ? (int)$templates3->push_enabled : null,
        'has_body'     => isset($templates3->body),
        'email_enabled' => isset($templates3->email_enabled) ? (int)$templates3->email_enabled : null,
    ]);

    // Get calendars with status "open"
    $sql = "SELECT * FROM {$calendar_table} WHERE status = %s";
    $calendar_rows = $wpdb->get_results($wpdb->prepare($sql, 'open'));

    if (empty($calendar_rows)) {
        cs_log('No calendars found with status "open". Cron finished.');
        return;
    }

    cs_log('Calendars retrieved', ['count' => count($calendar_rows)]);

    foreach ($calendar_rows as $calendar) {
        try {
            $calContext = [
                'calendar_id'         => $calendar->id ?? null,
                'property_id'         => $calendar->property_id ?? null,
                'updated_at'          => $calendar->updated_at ?? null,
                'turn'                => $calendar->turn ?? null,
                'owners_priority_raw' => $calendar->owners_priority ?? null,
            ];
            cs_log('Processing calendar', $calContext);

            $updated_at = !empty($calendar->updated_at) ? strtotime($calendar->updated_at) : 0;
            $now = time();

            // Parse owners_priority
            $owners_priority_json = is_string($calendar->owners_priority) ? stripslashes($calendar->owners_priority) : '';
            $owners_order = json_decode($owners_priority_json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($owners_order)) {
                cs_log('Error parsing owners_priority', [
                    'json_error'            => json_last_error_msg(),
                    'owners_priority_json'  => $owners_priority_json,
                ]);
                continue;
            }

            $turn = isset($calendar->turn) ? (int)$calendar->turn : 0;
            if (!isset($owners_order[$turn])) {
                cs_log('Current turn not found in owners_order', [
                    'turn'         => $turn,
                    'owners_order' => $owners_order,
                ]);
                continue;
            }

            $id_owner = (int)$owners_order[$turn];

            // Owner
            $owner = $owner_service->getOwner($id_owner, false);
            if (empty($owner) || !is_array($owner)) {
                cs_log('Owner not found or invalid', ['id_owner' => $id_owner]);
                continue;
            }

            $email = $owner['email'] ?? '';
            $name  = $owner['name'] ?? '';
            $phone = $owner['phone'] ?? '';

            // Property
            $id_property = (int)($calendar->property_id ?? 0);
            $property = $property_service->getProperty($id_property);
            if (!$property) {
                cs_log('Property not found', ['property_id' => $id_property]);
                continue;
            }

            $placeholders = [
                '[NAME]'     => $name,
                '[PROPERTY]' => $property->getName(),
                '[PHONE]'    => $phone,
                '[EMAIL]'    => $email,
            ];
            cs_log('Placeholders prepared', $placeholders);

            // Check if at least 2 days have passed (172800 seconds)
            $diff = $now - $updated_at;
            cs_log('Time since updated_at', ['diff_seconds' => $diff]);

            if ($updated_at > 0 && $diff >= 172800) {
                cs_log('>= 2 days passed. Attempting to pass turn...', ['calendar_id' => $calendar->id]);

                $passingTurn = $calendar_service->passTurn($calendar->id);
                cs_log('passTurn result', [
                    'calendar_id' => $calendar->id,
                    'passed'      => (int)$passingTurn
                ]);

                if ($passingTurn) {
                    // PUSH notification
                    if (!empty($templates3->push_enabled) && !empty($templates3->message)) {
                        $active_placeholders2 = array_filter($placeholders, function ($key) use ($templates3) {
                            return strpos($templates3->message, $key) !== false;
                        }, ARRAY_FILTER_USE_KEY);

                        $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $templates3->message);
                        cs_log('Creating PUSH notification', ['owner_id' => $id_owner, 'push' => $push]);

                        try {
                            $notification_service->createNotification($id_owner, $push);
                            cs_log('PUSH created successfully', ['owner_id' => $id_owner]);
                        } catch (Throwable $e) {
                            cs_log('Error creating PUSH', ['error' => $e->getMessage()]);
                        }
                    } else {
                        cs_log('PUSH disabled or template empty');
                    }

                    // EMAIL notification
                    if (!empty($templates3->email_enabled) && !empty($templates3->body) && !empty($email) && $email !== '-') {
                        $active_placeholders1 = array_filter($placeholders, function ($key) use ($templates3) {
                            return strpos($templates3->body, $key) !== false;
                        }, ARRAY_FILTER_USE_KEY);

                        $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $templates3->body);
                        cs_log('Sending missed turn EMAIL', ['to' => $email]);

                        try {
                            $sent = send_notification_email($templates3->subject, $message, $email);
                            cs_log('EMAIL send result', ['to' => $email, 'sent' => (int)$sent]);
                        } catch (Throwable $e) {
                            cs_log('Error sending EMAIL', ['to' => $email, 'error' => $e->getMessage()]);
                        }
                    } else {
                        cs_log('EMAIL disabled, no template or invalid email', [
                            'email_enabled' => isset($templates->email_enabled) ? (int)$templates->email_enabled : null,
                            'has_body'     => isset($templates->body),
                            'email'                  => $email,
                        ]);
                    }
                }
            } else {
                cs_log('Not enough time passed to change turn', [
                    'calendar_id'  => $calendar->id,
                    'updated_at'   => $calendar->updated_at,
                    'now'          => date('Y-m-d H:i:s', $now),
                    'diff_seconds' => $diff
                ]);
            }
        } catch (Throwable $e) {
            cs_log('Error processing calendar', [
                'calendar_id' => $calendar->id ?? null,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString()
            ]);
        }
    }

    cs_log('Cron finished: update_turns_and_rounds()');
}

function setup_cron_event()
{
    // Ensure a single scheduled event
    if (!wp_next_scheduled('cs_update_calendar_turns_event')) {
        $scheduled = wp_schedule_event(time(), 'hourly', 'cs_update_calendar_turns_event');
        cs_log('Scheduling hourly cron event', ['scheduled' => (bool)$scheduled]);
    } else {
        // cs_log('Cron event already scheduled');
    }
}

add_action('init', 'setup_cron_event');
add_action('cs_update_calendar_turns_event', 'update_turns_and_rounds');
