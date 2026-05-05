<?php

require_once __DIR__ . '/../repositories/PropertyRepository.php';
require_once __DIR__ . '/../repositories/CalendarRepository.php';
require_once __DIR__ . '/../repositories/OwnerRepository.php';

require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/TemplateService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/SettingService.php';

require_once LIBS . '/autoload.inc.php';

use Dompdf\FontMetrics;
use Dompdf\Options;
use Dompdf\Dompdf;
use Dompdf\FontCache;

class CalendarService
{
    private $repository;
    private $bookingService;

    public function __construct()
    {
        $this->repository = new CalendarRepository();
        $this->bookingService = new BookingService();
    }

    public function createCalendar($property_id, $year, $status)
    {
        $dto = new CreateCalendarDto($property_id, $year, $status);
        return $this->repository->insert($dto);
    }

    public function updateCalendar($id, $property_id = null, $year = null, $owners_priority = null, $round = null, $turn = null, $status = null, $colors_order = null, $toggle_download_calendar = null)
    {
        $dto = new UpdateCalendarDto($id, $property_id, $year, $owners_priority, $round, $turn, $status, $colors_order, $toggle_download_calendar);
        return $this->repository->update($dto);
    }

    public function resetCalendar(int $id, int $year): bool
    {
        return $this->repository->reset($id, $year);
    }

    public function pauseCalendar(int $calendarId, int $propertyId): bool {

        cs_log('Attempting to pause calendar.', [
            'id_calendar' => $calendarId,
            'property_id' => $propertyId
        ]);

        $updated = $this->updateCalendar(
            $calendarId,
            $propertyId,
            null,
            null,
            null,
            null,
            'pause',
            null,
            null
        );

        if ($updated) {
            cs_log('Calendar paused successfully.', [
                'id_calendar' => $calendarId,
                'property_id' => $propertyId
            ]);
        }

        return (bool) $updated;
    }

    public function resumeCalendar(int $calendarId, int $propertyId): bool {
        cs_log('Attempting to resume (open) calendar.', [
            'id_calendar' => $calendarId,
            'property_id' => $propertyId
        ]);

        $updated = $this->updateCalendar($calendarId, $propertyId, null, null, null, null,'open', null, null);

        if ($updated) {
            cs_log('Calendar resumed (opened) successfully.', [
                'id_calendar' => $calendarId,
                'property_id' => $propertyId
            ]);
        }

        return (bool) $updated;
    }

    public function getCalendar($id)
    {
        return $this->repository->find($id);
    }

    public function getCalendarByProperty($id, $year)
    {
        return $this->repository->selectIdCalendar($id, $year);
    }

    public function getNearestCalendar($id)
    {
        return $this->repository->getYearOfNearestCalendar($id);
    }

    public function getCalendarsByProperty($id)
    {
        return $this->repository->selectYears($id);
    }

    public function getAllCalendars()
    {
        $calendars = $this->repository->getAll();

        return array_map(function ($calendar) {
            $property_repository = new PropertyRepository();
            $property = $property_repository->find($calendar->getPropertyId());

            return [
                'id'       => $calendar->getId(),
                'name'     => $property->getName(),
                'year' => $calendar->getYear(),
                'code' => $property->getCode(),
                'status' => ucfirst($calendar->getStatus())
            ];
        }, $calendars);
    }

    public function getPropertyByCalendar($id)
    {
        $calendar = $this->repository->find($id);

        $property_repository = new PropertyRepository();
        return $property_repository->find($calendar->getPropertyId());
    }

    public function validateIfIsYourTurn($id_calendar, $id_owner, $owner_share)
    {
        $calendar = $this->repository->find($id_calendar);

        if ($calendar) {
            $turn = $calendar->getTurn(); //turno actual

            if (!is_null($calendar->getOwnersPriority())) {
                $owners_order = json_decode(stripslashes($calendar->getOwnersPriority()), true); //orden de turnos

                // return isset($owners_order[$turn]) && $owners_order[$turn] == $id_owner && $owners_order[$owner_share] == $id_owner;
                return isset($owners_order[$turn]) && $owners_order[$turn] == $id_owner;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getMaxDays4Select($id_calendar)
    {
        $calendar = $this->repository->find($id_calendar);
        if (!$calendar) {
            return null;
        }

        $property_id = $calendar->getPropertyId();
        $round = $calendar->getRound();

        $property_repository = new PropertyRepository();
        $property = $property_repository->find($property_id);
        if (!$property) {
            return null;
        }

        $qty_shares = intval($property->getShare());
        $max_days = 0;

        if ($qty_shares == 8) { // for 8 shares
            if ($round == 5) {
                $max_days = 5;
            } else {
                $max_days = 10;
            }
        }
        if ($qty_shares == 5) { // for 5 shares
            if ($round == 6) {
                $max_days = 2;
            } else {
                $max_days = 14;
            }
        }

        return $max_days;
    }

    public function passTurn($id_calendar, $owner_position = null)
    {
        $calendar = $this->repository->find($id_calendar);
        if (!$calendar) {
            return [
                'success' => false,
                'message' => 'Calendar not found.'
            ];
        }

        if (is_null($owner_position)) {
            $owner_position = $calendar->getTurn();
        }

        $property_repository = new PropertyRepository();
        $booking_service = new BookingService();
        $owner_repository = new OwnerRepository();

        $property_id = $calendar->getPropertyId();
        $property = $property_repository->find($property_id);
        if (!$property) {
            return [
                'success' => false,
                'message' => 'Property not found.'
            ];
        }

        $qty_shares = intval($property->getShare());
        $turn = intval($calendar->getTurn());
        $round = intval($calendar->getRound());

        $reserved_days = $booking_service->getSelectedDates($id_calendar, $owner_position, $round);
        $max_days = $this->getMaxDays4Select($id_calendar);

        $is_last_round = $round == 6 || ($qty_shares == 8 && $round == 5);

        if ($reserved_days >= $max_days) {

            if ($is_last_round && $turn == 1) {
                $next_turn = $this->updateCalendar($id_calendar, $property_id, null, null, 0, 0, 'completed', null, null);

                if ($next_turn) {
                    $this->sendPdfs($property_id, $id_calendar);
                }

                return [
                    'success' => (bool) $next_turn,
                    'message' => $next_turn ? 'Turn updated successfully.' : 'Error assigning turn.'
                ];
            }

            if ($is_last_round && $turn == 8) {
                $next_turn = $this->updateCalendar($id_calendar, $property_id, null, null, 0, 0, 'completed', null, null);

                if ($next_turn) {
                    $this->sendPdfs($property_id, $id_calendar);
                }

                return [
                    'success' => (bool) $next_turn,
                    'message' => $next_turn ? 'Turn updated successfully.' : 'Error assigning turn.'
                ];
            }


            // --------------------------------------------------------------------------------
            $next_owner_position = 0;
            if ($round % 2 === 0) {
                if ($turn !== 1) {
                    $next_owner_position = intval($owner_position) - 1;
                } else {
                    $next_owner_position = 1;
                }
            } else {
                if ($turn < $qty_shares) {
                    $next_owner_position = intval($owner_position) + 1;
                } else {
                    $next_owner_position = intval($owner_position);
                }
            }
            $owners_order = json_decode(stripslashes($calendar->getOwnersPriority()), true);
            $next_owner_id = $owners_order[$next_owner_position];
            $next_owner_info = $owner_repository->findLikeArray($next_owner_id);
            // --------------------------------------------------------------------------------


            if ($round % 2 === 0) {
                if ($turn !== 1) {
                    $next_turn = $this->updateCalendar($id_calendar, $property_id, null, null, null, $turn - 1, null, null);
                    return [
                        'success' => (bool) $next_turn,
                        'next_owner' => $next_owner_info,
                        'message' => $next_turn ? 'Turn updated successfully.' : 'Error assigning turn.'
                    ];
                } else {
                    $next_round = $this->updateCalendar($id_calendar, $property_id, null, null, $round + 1, 1, null, null);
                    return [
                        'success' => (bool) $next_round,
                        'next_owner' => $next_owner_info,
                        'message' => $next_round ? 'Round updated successfully.' : 'Error assigning round.'
                    ];
                }
            } else {
                if ($turn < $qty_shares) {
                    $next_turn = $this->updateCalendar($id_calendar, $property_id, null, null, null, $turn + 1, null, null);
                    return [
                        'success' => (bool) $next_turn,
                        'next_owner' => $next_owner_info,
                        'message' => $next_turn ? 'Turn updated successfully.' : 'Error assigning turn.'
                    ];
                } else {
                    $next_round = $this->updateCalendar($id_calendar, $property_id, null, null, $round + 1, $qty_shares, null, null);
                    return [
                        'success' => (bool) $next_round,
                        'next_owner' => $next_owner_info,
                        'message' => $next_round ? 'Round updated successfully.' : 'Error assigning round.'
                    ];
                }
            }
        } else {

            $puttingInPauseCalendar = $this->updateCalendar($id_calendar, $property_id, null, null, null, null, 'pause', null);
            if ($puttingInPauseCalendar) {
                return [
                    'success' => true,
                    'message' => 'Calendar paused because the maximum number of days reserved was not reached.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'The calendar could not be paused.'
                ];
            }
        }
    }

    public function sendPdfs($property_id, $id_calendar, $just_send = true, $year = null, $owner_id = null, $download_scope = null)
    {
        $owner_repository = new OwnerRepository();
        $property_repository = new PropertyRepository();

        $booking_service = new BookingService();
        $template_service = new TemplateService();
        $notification_service = new NotificationService();

        $contacts = (new SettingService())->getContacts(1);

        $templates4 = $template_service->getNotifications(4);

        $property = $property_repository->find($property_id);
        $owners = $owner_repository->getOwnersByProperty($property_id);

        if (empty($owners)) {
            return false;
        }

        // 🔹 Eliminar duplicados por owner_id
        $unique_owners = [];
        foreach ($owners as $owner) {
            $oId = $owner['owner_id'];
            if (!isset($unique_owners[$oId])) {
                $unique_owners[$oId] = $owner;
            }
        }

        $property_name = $property->getName();
        $closed_date = date('Y-m-d');
        $year = $year ?? date('Y') + 1;
        $MEDIA = MEDIA;

        $plugin_root = dirname(__DIR__, 2);
        $font_rel    = 'assets/fonts/';

        // Pasa variables al template
        $FONT_DIR_REL = $font_rel; // lo usarás en el @font-face

        if ($just_send) {

            $color_selected = $contacts->bgcolor_pdf;
            $calendar_scope = 'just_me';

            foreach ($unique_owners as $owner) {
                if (empty($owner['email'])) {
                    continue;
                }

                $owner_id = $owner['owner_id'];

                $bookings = $this->getBookingsByOwnerAndCalendar($id_calendar, $owner_id);

                ob_start();
                include MAIL . '/calendar-closed.php';
                $html = ob_get_clean();

                $options = new Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);

                $options->setChroot($plugin_root);                 // raíz segura para rutas relativas
                $options->set('defaultFont', 'Poppins');

                $upload = wp_upload_dir();
                $dompdf_cache = trailingslashit($upload['basedir']) . 'dompdf-cache-poppins';
                if (!is_dir($dompdf_cache)) {
                    @wp_mkdir_p($dompdf_cache);
                }
                $options->set('fontCache', $dompdf_cache);
                $options->set('fontDir',   $dompdf_cache);

                $dompdf = new Dompdf($options);
                $fm = $dompdf->getFontMetrics();
                $fm->getFont('Poppins', 'normal', $plugin_root . '/assets/fonts/Poppins-Regular.ttf');
                $fm->getFont('Poppins', 'bold',   $plugin_root . '/assets/fonts/Poppins-Bold.ttf');

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A3', 'portrait');
                $dompdf->render();
                $output = $dompdf->output();

                $pdf_path = wp_upload_dir()['basedir'] . '/calendar-closed-' . uniqid() . '.pdf';
                file_put_contents($pdf_path, $output);

                $placeholders = [
                    '[NAME]'     => $owner['name'],
                    '[PROPERTY]' => $property_name,
                    '[PHONE]'    => $owner['phone'],
                    '[EMAIL]'    => $owner['email']
                ];

                if ($templates4->email_enabled) {

                    $body = $templates4->body ?? '';

                    $active_placeholders1 = array_filter($placeholders, function ($key) use ($body) {
                        return strpos($body, $key) !== false;
                    }, ARRAY_FILTER_USE_KEY);

                    $message = str_replace(array_keys($active_placeholders1), array_values($active_placeholders1), $body);

                    send_notification_email($templates4->subject, $message, $owner['email'], [$pdf_path]);

                    @unlink($pdf_path);
                }

                if ($templates4->push_enabled) {

                    $message = $templates4->message ?? '';

                    $active_placeholders2 = array_filter($placeholders, function ($key) use ($message) {
                        return strpos($message, $key) !== false;
                    }, ARRAY_FILTER_USE_KEY);

                    $push = str_replace(array_keys($active_placeholders2), array_values($active_placeholders2), $message);

                    $notification_service->createNotification($owner_id, $push);
                }
            }
        } else {

            $calendar = $this->getCalendar($id_calendar);
            if (!$calendar) {
                return false;
            }
            $colors_order = json_decode(stripslashes((string) ($calendar->getColorsOrder() ?? '')), true);
            $colors_order = is_array($colors_order) ? $colors_order : [];
            if (!empty($colors_order)) {
                uksort($colors_order, static function ($a, $b) {
                    return (int) $a <=> (int) $b;
                });
            }
            $colors_only = array_values($colors_order);

            if ($download_scope === 'just_me') {
                $calendar_scope = 'just_me';
                $bookings = $this->getAllBookingsForCalendar($id_calendar);
            } else {
                $calendar_scope = 'rent';
                $bookings = $this->getBookingsByOwnerAndCalendar($id_calendar, null);
            }

            $legend_owners = [];
            if ($download_scope === 'just_me') {
                $owners_priority = json_decode(stripslashes((string) ($calendar->getOwnersPriority() ?? '')), true);

                if (is_array($owners_priority) && !empty($owners_priority)) {
                    foreach ($owners_priority as $pos => $owner_id_row) {
                        $pos = (int) $pos;
                        $owner_id_row = (int) $owner_id_row;
                        if ($pos < 1 || $owner_id_row <= 0) {
                            continue;
                        }
                        $owner_entity = $owner_repository->find($owner_id_row);
                        $idx = $pos - 1;
                        $legend_owners[] = [
                            'name'  => $owner_entity ? $owner_entity->getName() : ('Owner #' . $owner_id_row),
                            'color' => $colors_only[$idx] ?? '#D0D0D0',
                        ];
                    }
                } else {
                    foreach ($owners as $row) {
                        $pos = isset($row['owner_position']) ? (int) $row['owner_position'] : 0;
                        if ($pos < 1) {
                            continue;
                        }
                        $idx = $pos - 1;
                        $legend_owners[] = [
                            'name'  => $row['name'] ?? '',
                            'color' => $colors_only[$idx] ?? '#D0D0D0',
                        ];
                    }
                }
            }

            ob_start();
            include MAIL . '/calendar-closed.php';
            $html = ob_get_clean();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $options->setChroot($plugin_root);                 // raíz segura para rutas relativas
            $options->set('defaultFont', 'Poppins');

            $upload = wp_upload_dir();
            $dompdf_cache = trailingslashit($upload['basedir']) . 'dompdf-cache-poppins';
            if (!is_dir($dompdf_cache)) {
                @wp_mkdir_p($dompdf_cache);
            }
            $options->set('fontCache', $dompdf_cache);
            $options->set('fontDir',   $dompdf_cache);

            $dompdf = new Dompdf($options);
            $fm = $dompdf->getFontMetrics();
            $fm->getFont('Poppins', 'normal', $plugin_root . '/assets/fonts/Poppins-Regular.ttf');
            $fm->getFont('Poppins', 'bold',   $plugin_root . '/assets/fonts/Poppins-Bold.ttf');

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A3', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            $pdf_path = wp_upload_dir()['basedir'] . '/calendar-closed-' . uniqid() . '.pdf';
            file_put_contents($pdf_path, $output);

            // Forzar descarga del PDF generado
            if (file_exists($pdf_path) && is_readable($pdf_path)) {

                $upload = wp_upload_dir();
                $pdf_url = trailingslashit($upload['baseurl']) . basename($pdf_path);

                return $pdf_url;
            } else {
                return false;
            }
        }
    }

    public function getAllBookingsForCalendar(int $calendar_id): array
    {
        global $wpdb;
        $table = 'cs_booking';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE calendar_id = %d ORDER BY start_date ASC",
                $calendar_id
            ),
            ARRAY_A
        );
    }

    public function getBookingsByOwnerAndCalendar($calendar_id, $owner_id = null)
    {
        global $wpdb;
        $table = 'cs_booking';

        if ($owner_id === null) {
            // Por defecto: solo reservas de tipo "for rent" para ese calendario (todos los owners)
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE calendar_id = %d AND type LIKE %s",
                    $calendar_id,
                    '%for rent%'
                ),
                ARRAY_A
            );
        } else {
            // Filtra por owner_id y calendar_id (todos los type)
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE owner_id = %d AND calendar_id = %d ORDER BY start_date ASC",
                    $owner_id,
                    $calendar_id
                ),
                ARRAY_A
            );
        }
    }

    public function deleteBlockedDate(int $blockedDateId, int $calendarId): bool
    {
        return $this->repository->deleteBlockedDate($blockedDateId, $calendarId);
    }

    public function openCalendar(int $calendarId, int $propertyId, string $year, array $orderedOwners, array $orderedColors): array {

        $seasonsService = new SeasonsService();
        $propertyService = new PropertyService();
        $ownerService = new OwnerService();
        $templateService = new TemplateService();
        $notificationService = new NotificationService();

        // 1. Validar seasons
        $qtySeasons = $seasonsService->getSeasonsByYear($year);
        if (!$qtySeasons) {
            return [
                'success' => false,
                'message' => "Before opening the calendar you must have established all the seasons of the year $year."
            ];
        }

        // 2. Validar owners
        $allAreNotAssigned =
            !empty($orderedOwners)
            && count(array_unique($orderedOwners)) === 1
            && reset($orderedOwners) === NOT_ASSIGNED_ID;

        if ($allAreNotAssigned) {
            return [
                'success' => false,
                'message' => 'You must have selected at least one owner for the property.'
            ];
        }

        // 3. Abrir calendario
        $updated = $this->updateCalendar(
            $calendarId,
            $propertyId,
            $year,
            wp_json_encode($orderedOwners),
            1,
            1,
            'open',
            wp_json_encode($orderedColors),
        );

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'There was an error opening the calendar.'
            ];
        }

        // 4. Notificaciones
        $property = $propertyService->getProperty($propertyId);

        $uniqueOwners = array_values(
            array_filter(array_unique($orderedOwners), fn($id) => $id !== NOT_ASSIGNED_ID)
        );

        $template = $templateService->getNotifications(1);

        foreach ($uniqueOwners as $ownerId) {
            $owner = $ownerService->getOwner($ownerId, false);
            if (empty($owner['email'])) {
                continue;
            }

            $placeholders = [
                '[NAME]'     => $owner['name'] ?? '',
                '[PROPERTY]' => $property->getName(),
                '[PHONE]'    => $owner['phone'] ?? '',
                '[EMAIL]'    => $owner['email']
            ];

            if ($template->email_enabled) {
                $message = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $template->body
                );

                send_notification_email(
                    $template->subject,
                    $message,
                    $owner['email']
                );
            }

            if ($template->push_enabled) {
                $push = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $template->message
                );

                $notificationService->createNotification($ownerId, $push);
            }
        }

        return [
            'success' => true,
            'message' => 'Calendar opened successfully.'
        ];
    }

    public function resetCalendarAndBookings(int $calendarId, int $year, int $propertyId): array {

        cs_log('Attempting to reset calendar.', [
            'id_calendar' => $calendarId,
            'property_id' => $propertyId
        ]);

        // borrar reservas
        $deletedRows = $this->bookingService->deleteAllByCalendar($calendarId);

        cs_log('Deleted bookings before reset.', [
            'id_calendar'  => $calendarId,
            'rows_deleted' => $deletedRows
        ]);

        // reutiliza el reset existente
        $updated = $this->resetCalendar($calendarId, $year);

        if (!$updated) {
            return ['success' => false];
        }

        cs_log('Calendar rebooted successfully.', [
            'id_calendar'  => $calendarId,
            'property_id'  => $propertyId,
            'rows_deleted' => $deletedRows
        ]);

        return [
            'success'      => true,
            'rows_deleted' => $deletedRows
        ];
    }

}
