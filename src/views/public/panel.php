<?php

require_once __DIR__ . '/../../services/PropertyOperationService.php';
require_once __DIR__ . '/../../services/PropertyService.php';
require_once __DIR__ . '/../../services/OwnerService.php';
require_once __DIR__ . '/../../services/SettingService.php';
require_once __DIR__ . '/../../services/CalendarService.php';
require_once __DIR__ . '/../../services/BookingService.php';
require_once __DIR__ . '/../../services/BlockedDatesService.php';
require_once __DIR__ . '/../../services/ExchangeRequestService.php';

function get_panel()
{

    // Detectar si estoy en petición del editor (REST/AJAX) para NO redirigir
    $is_rest  = defined('REST_REQUEST') && REST_REQUEST;
    $is_ajax  = wp_doing_ajax();
    $is_admin_screen = is_admin(); // en REST/Gutenberg suele ser false
    $is_front_request = ! $is_rest && ! $is_ajax; // solo front real puede redirigir

    $owner_id = isset($_SESSION['mojo_owner_id']) ? intval($_SESSION['mojo_owner_id']) : 0;

    $property_id    = filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT) ?: 0;
    $property_share = filter_input(INPUT_GET, 'property_share', FILTER_VALIDATE_INT) ?: 0;

    if (!$is_front_request) {
        // Si quisieras mostrar algo más útil en el editor, cámbialo aquí.
        return '<div style="padding:8px;background:#fffbe6;border:1px solid #ffe58f;">
            Mojo Panel – preview deshabilitado en el editor. La página requiere parámetros (?property_id=&property_share=) en el frontend.
        </div>';
    }

    if (!current_user_can('administrator')) {
        if ($owner_id <= 0 || $property_id <= 0 || $property_share <= 0) {
            echo "<script>window.location.href = 'login'</script>";
            wp_redirect(home_url('dashboard'));
            exit;
        }
    }

    $serviceP = new PropertyService();
    $serviceO = new OwnerService();
    $servicePO = new PropertyOperationService();
    $serviceS = new SettingService();
    $serviceC = new CalendarService();
    $serviceB = new BookingService();

    $serviceBD = new BlockedDatesService();
    $serviceEX = new ExchangeRequestService();

    $nearest_year = $serviceC->getNearestCalendar($property_id);
    $year = isset($_GET['period']) ? absint($_GET['period']) : $nearest_year;

    if (!is_numeric($year) || preg_match('/[a-zA-Z]/', $year)) {
        echo "<script>window.location.href = 'dashboard'</script>";
        wp_redirect(home_url('dashboard'));
        exit;
    }

    $property = $serviceP->getProperty($property_id);
    $owners = $serviceO->getOwnersByProperty($property_id);
    $operations = $servicePO->getAllProperties($property_id);
    $contacts = $serviceS->getContacts(1);

    $current_owner = $serviceO->getOwner($owner_id);

    if (!current_user_can('administrator')) {
        if (is_null($property) || is_null($owners)) {
            wp_redirect(home_url('dashboard'));
            exit;
        }
    }

    $qty_shares = intval($property->getShare());
    $calendar = $serviceC->getCalendarByProperty($property_id, $year);
    $calendars = $serviceC->getCalendarsByProperty($property_id);

    /*if (!$calendar) {
        echo "<script>window.location.href = 'dashboard'</script>";
        wp_redirect(home_url('dashboard'));
        exit;
    }*/

    if ($calendar) {

        // Reindexar los owners por posición para acceso rápido
        $owners_by_position = [];
        $my_own_reservations = [];

        $calendar_id = $calendar->getId();
        $round = $calendar->getRound();
        $turn = $calendar->getTurn();

        $request_from = $serviceEX->getRequestByOwner($owner_id, $calendar_id);
        $request_to = $serviceEX->getRequestByOwner($owner_id, $calendar_id, 'to');

        $order_owners_4_calendar = [];
        if (!is_null($calendar->getOwnersPriority())) {
            $owners_priority = json_decode(stripslashes($calendar->getOwnersPriority()), true);
            if (!empty($owners_priority)) {
                foreach ($owners_priority as $n => $oid) {
                    $owner = $serviceO->getOwner($oid, false);
                    $owner['owner_position'] = $n;
                    $order_owners_4_calendar[$n] = $owner;
                }
            }
        }

        if (!is_null($turn)) {
            $owner_position = $order_owners_4_calendar[$turn]['owner_position'];
        } else {
            $owner_position = [];
        }

        if (!empty($owners)) {
            foreach ($owners as $owner) {
                $position = intval($owner['owner_position']);
                $owners_by_position[$position] = $owner; // sobrescribe si hay duplicados
            }
        }

        $is_turn_of_the_owner = $serviceC->validateIfIsYourTurn($calendar_id, $owner_id, $owner_position);

        $current_share = get_current_share_of_current_owner($qty_shares, $owners_by_position, $order_owners_4_calendar, $round, $turn);
        $is_share_of_the_owner = $current_share == $property_share;

        $selected_days = $serviceB->getSelectedDates($calendar_id, $owner_position, $round);
        $max_days = $serviceC->getMaxDays4Select($calendar_id);

        $last_reservation = $serviceB->getLastReservation($calendar_id, $owner_position, $round);

        $my_own_reservations = $serviceB->getBookingsByOwner($calendar_id, $owner_id);
    }
?>

    <main class="mojo_plugin mojo_panel-widget">
        <input type="hidden" class="in_panel">
        <?php get_mojo_header(); ?>

        <?php if ($calendar): ?>
            <input type="hidden" name="owner_position" value="<?php echo $owner_position; ?>">
        <?php endif; ?>

        <?php if (!empty($last_reservation)): ?>
            <input type="hidden" name="last_month_to_show" value="<?php echo (int) date('n', strtotime($last_reservation->getEndDate())); ?>">
        <?php endif; ?>

        <section class="mojo_panel-body">
            <?php get_first_and_last_day_of_seasons(false); ?>
            <?php if ($calendar): ?>
                <input type="hidden" name="calendar_id" value="<?php echo $calendar_id; ?>">
                <input type="hidden" name="max_days" value="<?php echo $max_days; ?>">
                <input type="hidden" name="selected_days" value="<?php echo $selected_days; ?>">
                <input type="hidden" name="round" value="<?php echo $round; ?>">
                <input type="hidden" name="purchase_min_nights" value="2"><!-- jcc: placeholder, backend pending -->
            <?php else: ?>
                <input type="hidden" name="round" value="1">
            <?php endif; ?>

            <?php if (isset($property_share) && !empty($property_share)): ?>
                <input type="hidden" name="owner_share" value="<?php echo $property_share; ?>">
            <?php endif; ?>

            <input type="hidden" name="owner_id" value="<?php echo $owner_id; ?>">
            <input type="hidden" name="qty_shares" value="<?php echo $qty_shares; ?>">

            <div class="mojo_container">
                <div class="mojo_panel-box" data-state="1">
                    <div class="mojo_panel-tabs">
                        <button>Calendar</button>
                        <button>Property Operation</button>
                        <?php if ($property && $property->getShowShares()): ?>
                            <button>Share Owners</button>
                        <?php endif; ?>
                        <?php if (!empty($request_from) || !empty($request_to)): ?>
                            <button>Exchange Requests</button>
                        <?php endif; ?>
                    </div>
                    <div class="mojo_panel-container">
                        <div class="mojo_panel-content">
                            <?php require_once __DIR__ . '/templates/calendar.php'; ?>
                            <?php if ($calendar && $calendar->getStatus() == 'open' || $calendar && $calendar->getStatus() == 'pause'): ?>
                                <div class="mojo_panel-schedule w-100">
                                    <?php get_picking_period_schedule($qty_shares, $owners_by_position, $order_owners_4_calendar, $round, $turn, 'panel'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mojo_panel-content mojo_panel-prop-operation">
                            <div class="mojo_panel-prop-head">
                                <h2><?php if (!empty($operations)) {
                                        echo 'ACTIVITIES';
                                    } ?></h2>
                                <div>
                                    <?php
                                    $whatsapp = $property->getWspGroup();
                                    $facebook = $property->getFbGroup();
                                    if (!empty($whatsapp)):
                                    ?>
                                        <a href="<?php echo $whatsapp; ?>" target="_blank" title="Whatsapp">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g clip-path="url(#clip0_232_2423)">
                                                    <path d="M14.275 11.8623C14.0375 11.7498 12.9 11.1873 12.6875 11.0998C12.475 11.0123 12.325 10.9873 12.1625 11.2248C12 11.4623 11.5625 11.9748 11.425 12.1373C11.2875 12.2998 11.1625 12.3123 10.925 12.1373C10.2429 11.8635 9.61287 11.4745 9.06254 10.9873C8.56435 10.5183 8.14259 9.97418 7.81254 9.3748C7.67504 9.1498 7.81254 9.0248 7.91254 8.8998C8.01254 8.7748 8.13754 8.6373 8.26254 8.4998C8.35395 8.38089 8.42958 8.25064 8.48754 8.1123C8.51855 8.04797 8.53465 7.97747 8.53465 7.90605C8.53465 7.83463 8.51855 7.76414 8.48754 7.6998C8.48754 7.5873 7.96254 6.4498 7.76254 5.9873C7.56254 5.5248 7.38754 5.5873 7.25004 5.5873H6.75004C6.51185 5.59659 6.28708 5.69998 6.12504 5.8748C5.86349 6.12439 5.65631 6.42532 5.5165 6.75872C5.37668 7.09212 5.30725 7.45081 5.31254 7.8123C5.37647 8.69978 5.7028 9.54821 6.25004 10.2498C7.25425 11.7416 8.62813 12.9475 10.2375 13.7498C10.7875 13.9873 11.2125 14.1248 11.55 14.2373C12.024 14.3805 12.5249 14.4105 13.0125 14.3248C13.3364 14.2591 13.6433 14.1276 13.9143 13.9383C14.1852 13.7491 14.4143 13.5063 14.5875 13.2248C14.7317 12.8772 14.7792 12.4972 14.725 12.1248C14.6625 12.0373 14.5125 11.9748 14.275 11.8623Z" fill="white" />
                                                    <path d="M16.6125 3.34983C15.7481 2.47718 14.7176 1.78649 13.5819 1.31847C12.4462 0.850459 11.2283 0.614608 10 0.62483C8.37292 0.633345 6.77649 1.06862 5.37023 1.88718C3.96396 2.70574 2.79707 3.87894 1.9861 5.2896C1.17513 6.70026 0.748461 8.29901 0.748716 9.92616C0.748971 11.5533 1.17614 13.1519 1.98755 14.5623L0.737549 19.3748L5.66255 18.1248C7.02409 18.8657 8.54999 19.2526 10.1 19.2498H10C11.8474 19.2619 13.6566 18.7234 15.1967 17.7031C16.7369 16.6829 17.9382 15.227 18.6477 13.5212C19.3571 11.8154 19.5424 9.93696 19.1799 8.12543C18.8174 6.31389 17.9237 4.65136 16.6125 3.34983ZM10 17.6498C8.61324 17.6509 7.25221 17.275 6.06255 16.5623L5.78755 16.3998L2.86255 17.1623L3.63755 14.3123L3.46255 14.0248C2.46424 12.4171 2.09081 10.4989 2.41311 8.6341C2.73541 6.7693 3.731 5.08769 5.21101 3.90831C6.69101 2.72893 8.55244 2.13385 10.4421 2.23598C12.3318 2.3381 14.1183 3.13032 15.4625 4.46233C16.1832 5.1772 16.7544 6.02834 17.1429 6.96617C17.5313 7.904 17.7292 8.90975 17.725 9.92483C17.7217 11.9726 16.9068 13.9356 15.4588 15.3836C14.0108 16.8316 12.0478 17.6465 10 17.6498Z" fill="white" />
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_232_2423">
                                                        <rect width="20" height="20" fill="white" />
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                            WHATSAPP GROUP
                                        </a>
                                    <?php endif;
                                    if (!empty($facebook)): ?>
                                        <a href="<?php echo $facebook; ?>" target="_blank" title="Facebook">
                                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.6392 18V9.9H13.0983L13.5 6.3H10.6392V4.54658C10.6392 3.61958 10.6628 2.7 11.9582 2.7H13.2702V0.126123C13.2702 0.087423 12.1432 0 11.0031 0C8.62199 0 7.13106 1.49148 7.13106 4.23018V6.3H4.5V9.9H7.13106V18H10.6392Z" fill="white" />
                                            </svg>
                                            GO TO PRIVATE GROUP
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php require_once __DIR__ . '/templates/operations-timeline.php'; ?>
                            <?php require_once __DIR__ . '/templates/contacts.php'; ?>
                        </div>
                        <?php require_once __DIR__ . '/templates/property-shares.php'; ?>
                        <?php require_once __DIR__ . '/templates/requests.php'; ?>
                    </div>
                </div>
                <div class="mojo_panel-top">
                    <h1>Currently Managing:
                        <div class="title_card">
                            <?php $slug = $property->getSlug();
                            if (!empty($slug)): ?>
                                <a href="<?php echo esc_url(home_url('/property/' . $slug . '/')); ?>" target="_blank">
                                    <?php echo $property->getName(); ?> (Share <?php echo $property_share; ?>)
                                </a>
                            <?php else: ?>
                                <span>
                                    <?php echo $property->getName(); ?> (Share <?php echo $property_share; ?>)
                                </span>
                            <?php endif; ?>
                            <div class="mojo_property_card v3">
                                <div class="mojo_property_card-thumb">
                                    <?php $thumbnail = $property->getThumbnail();
                                    if (!empty($thumbnail)): ?>
                                        <img src="<?php echo $thumbnail; ?>" alt="<?php echo $property->getName(); ?>">
                                    <?php else: ?>
                                        <img src="<?php echo MEDIA; ?>/1.jpg">
                                    <?php endif; ?>
                                </div>
                                <div class="mojo_property_card-info">
                                    <div>
                                        <div class="mojo_property_card-top">
                                            <p><?php echo $property->getName(); ?></p>
                                            <p><?php echo $property->getCode(); ?></p>
                                        </div>
                                        <div class="mojo_property_card-shares">
                                            <?php
                                            $property_type = $property->getPropertyType();
                                            $bedroom = $property->getBedroom();
                                            if (!empty($property_type) || !empty($bedroom)): ?>
                                                <span>
                                                    <?php if (!empty($property_type)): ?>
                                                        <?php echo $property_type; ?><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($bedroom)): ?>
                                                        <?php echo $bedroom; ?> Bedrooms
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (NOT_APPEAR && $shares_left != 0): ?>
                                        <div class="mojo_property_card-bottom">
                                            <div class="mojo_property_card-message">
                                                <p><?php echo $shares_left; ?> SHARES LEFT</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </h1>
                    <?php get_back_dashboard(); ?>
                </div>
            </div>
        </section>
    </main>


    <?php if (!empty($my_own_reservations)): ?>
        <input type="hidden" class="completed_status">
    <?php endif; ?>

    <script>
        let selectable = false,
            BLOCKED_DATES = [];

        <?php if ($calendar && $calendar->getStatus() == 'open' && $is_turn_of_the_owner && $is_share_of_the_owner && $selected_days >= $max_days): ?>
            selectable = false;
        <?php else: ?>
            selectable = true;
        <?php endif; ?>

        <?php if ($calendar): ?>
            BLOCKED_DATES = <?php echo wp_json_encode($serviceBD->getByCalendar($calendar->getId())); ?>;
        <?php endif; ?>

        let events = [
            <?php if ($calendar) {
                $serviceB->getBookingByYear($calendar->getId());
            } ?>
            <?php get_seasons(false); ?>
        ];
    </script>

    <?php if (NOT_APPEAR): ?>
        <?php if (!empty($request_from)): ?>
            <script>
                window.mjExchangeFromRanges = window.mjExchangeFromRanges || [];

                <?php foreach ($request_from as $request):
                    // FECHAS INCLUSIVAS -> para FullCalendar el end debe ser +1 día
                    $start = esc_js($request['start_from']);

                    $endObj = new DateTime($request['end_from']);
                    $endObj->modify('+1 day');
                    $end  = esc_js($endObj->format('Y-m-d'));
                ?>
                    window.mjExchangeFromRanges.push({
                        start: '<?php echo $start; ?>',
                        end: '<?php echo $end; ?>'
                    });
                <?php endforeach; ?>
            </script>
        <?php endif; ?>
    <?php endif; ?>

<?php
}
add_shortcode('mojo_panel', 'get_panel'); // [mojo_panel]


require_once __DIR__ . '/helpers.php';
