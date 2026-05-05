<?php

require_once __DIR__ . '/../../services/NotificationService.php';
require_once __DIR__ . '/../../services/PropertyService.php';
require_once __DIR__ . '/../../services/CalendarService.php';

function get_mojo_header()
{
    $owner_id = isset($_SESSION['mojo_owner_id']) ? intval($_SESSION['mojo_owner_id']) : null;

    $html = '<header class="mojo_general-head">
        <div class="mojo_general-head_grid">
            <a class="w-100" style="display:block" href="' . esc_url(home_url('dashboard')) . '">
                <img src="' . MEDIA . '/logo.png" width="139" class="w-100">
            </a>
            <div class="nav">
                <a href="' . esc_url(home_url('notifications')) . '" class="mojo_notifications" title="Notifications">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.6252 6.71704C12.9539 5.71983 13.893 5 15.0002 5C16.1075 5 17.0466 5.71983 17.3752 6.71704C19.6491 7.6519 21.2502 9.88898 21.2502 12.5V17.5L23.9332 20.183C24.327 20.5768 24.0481 21.25 23.4912 21.25H6.50907C5.95226 21.25 5.67339 20.5768 6.06712 20.183L8.75024 17.5V12.5C8.75024 9.88898 10.3513 7.6519 12.6252 6.71704Z" fill="white"/>
                        <path d="M12.5 22.5C12.5 23.8807 13.6193 25 15 25C16.3807 25 17.5 23.8807 17.5 22.5H12.5Z" fill="white"/>
                    </svg>
                </a>
                <div class="latest_notifications">
                    <button type="button" class="mojo_notifications mojo_notifications-toggle" title="Notifications">
                        <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.6252 6.71704C12.9539 5.71983 13.893 5 15.0002 5C16.1075 5 17.0466 5.71983 17.3752 6.71704C19.6491 7.6519 21.2502 9.88898 21.2502 12.5V17.5L23.9332 20.183C24.327 20.5768 24.0481 21.25 23.4912 21.25H6.50907C5.95226 21.25 5.67339 20.5768 6.06712 20.183L8.75024 17.5V12.5C8.75024 9.88898 10.3513 7.6519 12.6252 6.71704Z" fill="white"/>
                            <path d="M12.5 22.5C12.5 23.8807 13.6193 25 15 25C16.3807 25 17.5 23.8807 17.5 22.5H12.5Z" fill="white"/>
                        </svg>
                    </button>
                    <div class="latest_notifications-box">
                        <div class="latest_notifications-head">
                            <p>NOTIFICATIONS</p>
                            <button type="button" class="mojo_notifications-toggle">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.734178 0.73411C0.937333 0.531017 1.21283 0.416925 1.50009 0.416925C1.78736 0.416925 2.06286 0.531017 2.26601 0.73411L8.00009 6.46819L13.7342 0.73411C13.8341 0.630641 13.9537 0.54811 14.0858 0.491334C14.218 0.434557 14.3601 0.404672 14.504 0.403422C14.6478 0.402172 14.7905 0.429582 14.9236 0.484053C15.0568 0.538524 15.1777 0.618965 15.2794 0.720682C15.3812 0.822399 15.4616 0.943356 15.5161 1.07649C15.5705 1.20963 15.5979 1.35228 15.5967 1.49613C15.5954 1.63997 15.5656 1.78213 15.5088 1.9143C15.452 2.04647 15.3695 2.16601 15.266 2.26594L9.53193 8.00003L15.266 13.7341C15.4634 13.9384 15.5725 14.2121 15.5701 14.4961C15.5676 14.7802 15.4537 15.0519 15.2528 15.2527C15.052 15.4536 14.7802 15.5675 14.4962 15.57C14.2121 15.5725 13.9385 15.4633 13.7342 15.2659L8.00009 9.53186L2.26601 15.2659C2.06169 15.4633 1.78804 15.5725 1.50399 15.57C1.21995 15.5675 0.948233 15.4536 0.747374 15.2527C0.546515 15.0519 0.432582 14.7802 0.430114 14.4961C0.427645 14.2121 0.53684 13.9384 0.734178 13.7341L6.46826 8.00003L0.734178 2.26594C0.531084 2.06279 0.416992 1.78729 0.416992 1.50003C0.416992 1.21277 0.531084 0.937265 0.734178 0.73411Z" fill="#0D0D0D"/>
                                </svg>
                            </button>
                        </div>';
    if (!is_null($owner_id)) {
        $html .= get_notifications_list($owner_id);
    }
    $html .= '</div>
                </div>
                <button type="button" id="logout-button" class="mojo_simple_button" title="Logout">
                    <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 4.00295V16.0059C0 17.111 0.459804 18.0933 1.1951 18.8605C1.93039 19.5972 2.88039 19.9961 3.98333 19.9961H9.98824V17.5098H3.98333C3.15588 17.5098 2.51275 16.834 2.51275 16.0059V4.00295C2.51275 3.17387 3.15588 2.52947 3.98333 2.52947H9.98824V0.0117874H3.98333C2.88039 0.0117874 1.93039 0.442043 1.1951 1.17878C0.459804 1.94597 0 2.89784 0 4.00295ZM6.58726 7.62574V12.4145C6.58726 12.9676 7.07745 13.4273 7.62941 13.4273H13.1745V17.2033C13.1745 17.5413 13.3588 17.8173 13.6647 17.9705C13.7873 18.001 13.9098 18.001 13.9716 18.001C14.1863 18.001 14.3696 17.9391 14.5235 17.7859L21.7235 10.5717C22.0608 10.2957 22.0304 9.74263 21.7235 9.43615L14.5235 2.25246C14.0941 1.79175 13.1755 2.06778 13.1755 2.8055V6.61198H7.63039C7.07843 6.61198 6.58823 7.07269 6.58823 7.62475L6.58726 7.62574Z" fill="white"/>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </header>';

    echo $html;
}

function get_notifications_list($user_id)
{
    $notification_service = new NotificationService();
    $notifications = $notification_service->getNotifications($user_id);

    if (!is_null($notifications)) {
        $html = '<div class="latest_notifications-body">
            <input type="hidden" value="5" name="offset_notifications" />
            <ul class="notifications" data-id-owner="' . $user_id . '" style="padding:0">';
        foreach ($notifications as $notification) {
            $datetime = $notification->datetime;
            $html .= '<li data-id="' . $notification->id . '">
                        <div>
                            <!--<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_252_5032)">
                                    <path d="M10 0C4.47692 0 0 4.47692 0 10C0 15.5231 4.47692 20 10 20C15.5231 20 20 15.5231 20 10C20 4.47692 15.5231 0 10 0ZM15.5831 7.15923L8.28615 14.4554C8.10308 14.6377 7.80692 14.6377 7.62385 14.4554L7.47692 14.3085L7.47615 14.3092L3.46154 10.2662C3.27846 10.0831 3.27846 9.78615 3.46154 9.60308L4.45692 8.60846C4.64 8.42538 4.93692 8.42538 5.12 8.60846L7.95769 11.4669L13.9238 5.50077C14.1069 5.31769 14.4038 5.31769 14.5869 5.50077L15.5823 6.49615C15.7662 6.67846 15.7662 6.97538 15.5831 7.15923Z" fill="#60C0A8"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_252_5032">
                                        <rect width="20" height="20" fill="white"/>
                                    </clipPath>
                                </defs>
                            </svg>-->
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_252_5052)">
                                    <path d="M4.85237 3.48816C5.22276 3.48816 5.52331 3.18793 5.52331 2.8175V0.670625C5.52331 0.300234 5.22276 0 4.85237 0C4.48198 0 4.1814 0.300234 4.1814 0.670664V2.81754C4.1814 3.18793 4.48198 3.48816 4.85237 3.48816Z" fill="#60C0A8"/>
                                    <path d="M10.2188 3.48816C10.5892 3.48816 10.8897 3.18793 10.8897 2.8175V0.670625C10.8897 0.300234 10.5891 0 10.2188 0C9.84833 0 9.5481 0.300234 9.5481 0.670664V2.81754C9.5481 3.18793 9.84833 3.48816 10.2188 3.48816Z" fill="#60C0A8"/>
                                    <path d="M16.8988 1.75388H16.7928V2.81751C16.7928 3.48306 16.2513 4.02477 15.5852 4.02477C14.9196 4.02477 14.3776 3.48302 14.3776 2.81751V1.75388H11.4264V2.81751C11.4264 3.48306 10.8846 4.02477 10.2188 4.02477C9.55324 4.02477 9.01148 3.48302 9.01148 2.81751V1.75388H6.05992V2.81751C6.05992 3.48306 5.51816 4.02477 4.85234 4.02477C4.1868 4.02477 3.64477 3.48302 3.64477 2.81751V1.75388H3.53937C2.09117 1.75388 0.916992 2.92802 0.916992 4.37626V17.3776C0.916992 18.8258 2.09113 20 3.53937 20H16.8988C18.3467 20 19.5212 18.8259 19.5212 17.3776V4.37626C19.5212 2.92806 18.3467 1.75388 16.8988 1.75388ZM18.4473 17.1786C18.4473 18.144 17.6649 18.9267 16.6995 18.9267H3.73871C2.77324 18.9267 1.99027 18.144 1.99027 17.1786V7.44071H18.4473V17.1786Z" fill="#60C0A8"/>
                                    <path d="M15.5853 3.48816C15.9557 3.48816 16.2562 3.18793 16.2562 2.8175V0.670625C16.2562 0.300234 15.9557 0 15.5853 0C15.2149 0 14.9143 0.300234 14.9143 0.670664V2.81754C14.9143 3.18793 15.2149 3.48816 15.5853 3.48816Z" fill="#60C0A8"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_252_5052">
                                        <rect width="20" height="20" fill="white"/>
                                    </clipPath>
                                </defs>
                            </svg>
                            <p>' . $notification->notification . '</p>
                        </div>
                        <div>
                            <small>' . time_elapsed_string($datetime) . '</small>
                        </div>
                    </li>';
        }
        $html .= '</ul>
        </div>';
        if (count($notifications) > 4) {
            $html .= '<div class="latest_notifications-foot">
                <button type="button">Load More</button>
            </div>';
        }
    } else {
        $html = '<div class="no_notifications">
            <p>There are no notifications yet.</p>
        </div>';
    }

    return $html;
}

function time_elapsed_string($datetime, $full = false)
{
    // Obtener zona horaria desde WordPress
    $timezone = get_option('timezone_string');
    if (!$timezone) {
        // Si no está configurada, usar UTC por defecto
        $timezone = 'UTC';
    }

    $tz = new DateTimeZone($timezone);
    $now = new DateTime('now', $tz);
    $ago = new DateTime($datetime, $tz);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d % 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    foreach ($string as $k => &$v) {
        switch ($k) {
            case 'w':
                $value = $weeks;
                break;
            case 'd':
                $value = $days;
                break;
            default:
                $value = $diff->$k;
        }

        if ($value) {
            $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function get_back_dashboard()
{
    $html = '<a href="' . esc_url(home_url('dashboard')) . '" class="mojo_back">
        <svg width="10" height="17" viewBox="0 0 10 17" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_138_3913)">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.68907 15.1215L8.31055 16.5L0.310547 8.5L8.31055 0.5L9.68907 1.87853L3.06837 8.5L9.68907 15.1215Z" fill="white" />
            </g>
            <defs>
                <clipPath id="clip0_138_3913">
                    <rect width="10" height="16" fill="white" transform="translate(0 0.5)" />
                </clipPath>
            </defs>
        </svg>
        BACK TO PROPERTIES
    </a>';

    echo $html;
}

function get_booking_calendar($id_property = null)
{
    $calendar_service = new CalendarService();
    $current_year = (int) date('Y');
    $selected_year = isset($_GET['period']) ? (int) $_GET['period'] : $current_year;
    $years = is_page('panel')
        ? [$selected_year]
        : [$current_year, $current_year + 1];
    $html = '';

    foreach ($years as $year) {
        $calendar = $calendar_service->getCalendarByProperty($id_property, $year);
        if (!$calendar /*|| (int) $calendar->getToggleDownloadCalendar() !== 1*/) {
            continue;
        }

        if ((isset($_GET['page']) && in_array($_GET['page'], ['calendar-system-edit'])) || is_page('panel')) {
            $html .= '<button data-year="' . $year . '" class="download just-me" type="button" data-toggle="pdf" data-id-property="' . $id_property . '" data-id-calendar="' . $calendar->getId() . '">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/>
                </svg>
            </button>';
        } elseif (is_page('dashboard')) {
            $rent_bookings = $calendar_service->getBookingsByOwnerAndCalendar($calendar->getId());
            if (empty($rent_bookings)) {
                continue;
            }

            $html .= '<button data-year="' . $year . '" class="download general" type="button" data-toggle="pdf" data-id-property="' . $id_property . '" data-id-calendar="' . $calendar->getId() . '">
                <svg width="24" height="24" viewBox="0 0 640 640" fill="#60C0A8" xmlns="http://www.w3.org/2000/svg">
                    <path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/>
                </svg>
                Rental Calendar ' . $year . '
            </button>';
            break;
        } else {
            $rent_bookings = $calendar_service->getBookingsByOwnerAndCalendar($calendar->getId());
            if (empty($rent_bookings)) {
                continue;
            }

            $html .= '<button data-year="' . $year . '" class="download general" type="button" data-toggle="pdf" data-id-property="' . $id_property . '" data-id-calendar="' . $calendar->getId() . '">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/>
                </svg>
                Rental Calendar ' . $year . '
            </button>';
        }
    }

    if ($html === '') {
        $html = '<div>&nbsp;</div>';
    }

    echo $html;
}

function get_list_color_codes_for_owners($qty_shares, $owners_by_position, $show_final_tags = true, $property_id = null)
{
?>
    <?php
    for ($i = 1; $i <= $qty_shares; $i++):
        $owner = $owners_by_position[$i] ?? null;
        $color = COLOR_CODES[intval($i - 1)];
    ?>
        <?php if ($owner): ?>
            <?php if (isset($owner['owner_id'])): ?>
                <li data-color="<?php echo $color; ?>" data-id="<?php echo $owner['owner_id']; ?>" class="member" style="background-color: <?php echo $color ?>;">
                    <p><?php echo $i; ?></p>
                    <p>|</p>
                    <?php if ($owner['is_active'] == 1): ?>
                        <p><?php echo esc_html($owner['name']); ?></p>
                    <?php else: ?>
                        <p>Mojo Sharing</p>
                    <?php endif; ?>
                </li>
            <?php elseif (isset($owner['id'])): ?>
                <li data-color="<?php echo $color; ?>" data-id="<?php echo $owner['id']; ?>" class="member" style="background-color: <?php echo $color ?>;">
                    <p><?php echo $i; ?></p>
                    <p>|</p>
                    <?php if ($owner['is_active'] == 1): ?>
                        <p><?php echo esc_html($owner['name']); ?></p>
                    <?php else: ?>
                        <p>Mojo Sharing</p>
                    <?php endif; ?>
                </li>
            <?php else: ?>
                <li data-color="<?php echo $color; ?>" data-id="0" class="member" style="background-color: <?php echo $color ?>;">
                    <p><?php echo $i; ?></p>
                    <p>|</p>
                    <p>Mojo Sharing</p>
                </li>
            <?php endif; ?>
        <?php else: ?>
            <li data-color="<?php echo $color; ?>" data-id="0" class="member" style="background-color: <?php echo $color ?>;">
                <p><?php echo $i; ?></p>
                <p>|</p>
                <p>Mojo Sharing</p>
            </li>
        <?php endif; ?>
    <?php endfor; ?>

    <?php
    $link = '';
    $property_service = new PropertyService();

    if (!is_null($property_id)) {
        $property = $property_service->getProperty($property_id);
        if ($property) {
            $link = $property->getRentalBookingPage();
        }
    }
    ?>

    <?php if ($show_final_tags): ?>
        <li style="background-color: #CCCCCC;" class="ufr">
            <p>UP FOR RENTAL</p>
        </li>
        <?php if (empty($link)): ?>
            <li style="background-color: white;margin:0;border:1px solid black;" class="crb">
                <p>CONFIRMED RENTAL BOOKING</p>
            </li>
        <?php else: ?>
            <li style="background-color: white;margin:0;border:1px solid black;" class="crb">
                <a href="<?php echo $link; ?>" target="_blank">CONFIRMED RENTAL BOOKING</a>
            </li>
        <?php endif; ?>
    <?php endif; ?>
<?php
}

add_action('wp_ajax_nopriv_mojo_send_quote', 'mojo_send_quote');
add_action('wp_ajax_mojo_send_quote', 'mojo_send_quote');

function mojo_send_quote()
{
    $dates = sanitize_text_field($_POST['dates'] ?? '');
    $property = sanitize_text_field($_POST['property'] ?? '');
    $owner = sanitize_text_field($_POST['owner'] ?? '');

    $subject = "Request For Quote";
    $message = "<p>The user has requested a quote with the following information:</p>" .
        "<p>Owner: $owner</p>" .
        "<p>Property: $property</p>" .
        "<p>Dates: $dates</p>";

    $email = get_request_email();

    $send_email = send_notification_email($subject, $message, $email);

    if ($send_email) {
        wp_send_json_success([
            'message' => 'Quote sent successfully',
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Error sending quote',
        ]);
    }
}

add_action('wp_ajax_nopriv_load_more_notifications', 'load_more_notifications');
add_action('wp_ajax_load_more_notifications', 'load_more_notifications');

function load_more_notifications()
{
    $owner_id = isset($_SESSION['mojo_owner_id']) ? intval($_SESSION['mojo_owner_id']) : null;
    $offset = $_POST['offset'] ?? 5;

    $service = new NotificationService();
    $notifications = $service->getNotifications($owner_id, 5, $offset);

    if (!$notifications) {
        wp_send_json(['success' => false, 'html' => '']);
    }

    ob_start();
    foreach ($notifications as $notification) {
        echo '<li data-id="' . $notification->id . '">
            <div>
                <!--<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_252_5032)">
                        <path d="M10 0C4.47692 0 0 4.47692 0 10C0 15.5231 4.47692 20 10 20C15.5231 20 20 15.5231 20 10C20 4.47692 15.5231 0 10 0ZM15.5831 7.15923L8.28615 14.4554C8.10308 14.6377 7.80692 14.6377 7.62385 14.4554L7.47692 14.3085L7.47615 14.3092L3.46154 10.2662C3.27846 10.0831 3.27846 9.78615 3.46154 9.60308L4.45692 8.60846C4.64 8.42538 4.93692 8.42538 5.12 8.60846L7.95769 11.4669L13.9238 5.50077C14.1069 5.31769 14.4038 5.31769 14.5869 5.50077L15.5823 6.49615C15.7662 6.67846 15.7662 6.97538 15.5831 7.15923Z" fill="#60C0A8"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_252_5032">
                            <rect width="20" height="20" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>-->
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_252_5052)">
                        <path d="M4.85237 3.48816C5.22276 3.48816 5.52331 3.18793 5.52331 2.8175V0.670625C5.52331 0.300234 5.22276 0 4.85237 0C4.48198 0 4.1814 0.300234 4.1814 0.670664V2.81754C4.1814 3.18793 4.48198 3.48816 4.85237 3.48816Z" fill="#60C0A8"/>
                        <path d="M10.2188 3.48816C10.5892 3.48816 10.8897 3.18793 10.8897 2.8175V0.670625C10.8897 0.300234 10.5891 0 10.2188 0C9.84833 0 9.5481 0.300234 9.5481 0.670664V2.81754C9.5481 3.18793 9.84833 3.48816 10.2188 3.48816Z" fill="#60C0A8"/>
                        <path d="M16.8988 1.75388H16.7928V2.81751C16.7928 3.48306 16.2513 4.02477 15.5852 4.02477C14.9196 4.02477 14.3776 3.48302 14.3776 2.81751V1.75388H11.4264V2.81751C11.4264 3.48306 10.8846 4.02477 10.2188 4.02477C9.55324 4.02477 9.01148 3.48302 9.01148 2.81751V1.75388H6.05992V2.81751C6.05992 3.48306 5.51816 4.02477 4.85234 4.02477C4.1868 4.02477 3.64477 3.48302 3.64477 2.81751V1.75388H3.53937C2.09117 1.75388 0.916992 2.92802 0.916992 4.37626V17.3776C0.916992 18.8258 2.09113 20 3.53937 20H16.8988C18.3467 20 19.5212 18.8259 19.5212 17.3776V4.37626C19.5212 2.92806 18.3467 1.75388 16.8988 1.75388ZM18.4473 17.1786C18.4473 18.144 17.6649 18.9267 16.6995 18.9267H3.73871C2.77324 18.9267 1.99027 18.144 1.99027 17.1786V7.44071H18.4473V17.1786Z" fill="#60C0A8"/>
                        <path d="M15.5853 3.48816C15.9557 3.48816 16.2562 3.18793 16.2562 2.8175V0.670625C16.2562 0.300234 15.9557 0 15.5853 0C15.2149 0 14.9143 0.300234 14.9143 0.670664V2.81754C14.9143 3.18793 15.2149 3.48816 15.5853 3.48816Z" fill="#60C0A8"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_252_5052">
                            <rect width="20" height="20" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>
                <p>' . esc_html($notification->notification) . '</p>
            </div>
            <div>
                <small>' . time_elapsed_string($notification->datetime) . '</small>
            </div>
        </li>';
    }
    $html = ob_get_clean();

    wp_send_json([
        'success' => true,
        'html' => $html,
        'count' => count($notifications)
    ]);
}

add_action('wp_ajax_nopriv_mojo_download_booking_calendar', 'mojo_download_booking_calendar');
add_action('wp_ajax_mojo_download_booking_calendar', 'mojo_download_booking_calendar');

function mojo_download_booking_calendar()
{
    $property_id = $_POST['property_id'] ?? 0;
    $calendar_id = $_POST['calendar_id'] ?? 0;
    $year = $_POST['year'] ?? 0;
    $scope = $_POST['scope'] ?? 'all';

    if (!$property_id || !$calendar_id || !$year) {
        wp_send_json_error([
            'message' => 'Missing property_id or calendar_id'
        ]);
    }

    $calendar_service = new CalendarService();
    $property_service = new PropertyService();
    $property = $property_service->getProperty($property_id);
    if (!$property) {
        wp_send_json_error([
            'message' => 'Property not found'
        ]);
    }

    $download_scope = ($scope === 'just_me') ? 'just_me' : 'rent';

    $calendar = $calendar_service->getCalendar($calendar_id);
    if (!$calendar /*|| (int) $calendar->getToggleDownloadCalendar() !== 1*/) {
        wp_send_json_error([
            'message' => 'Download is not enabled for this calendar'
        ]);
    }

    $property_name = $property->getName();

    $pdf_url = $calendar_service->sendPdfs($property_id, $calendar_id, false, $year, null, $download_scope);

    if ($pdf_url) {
        wp_send_json_success([
            'message' => 'PDF generated successfully',
            'url' => $pdf_url,
            'property_name' => $property_name
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Failed to generate PDF'
        ]);
    }
}

require_once __DIR__ . '/login.php';
require_once __DIR__ . '/dashboard.php';
require_once __DIR__ . '/panel.php';
require_once __DIR__ . '/notifications.php';
