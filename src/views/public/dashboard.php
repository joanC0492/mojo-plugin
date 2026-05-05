<?php

require_once __DIR__ . '/../../services/PropertyService.php';
require_once __DIR__ . '/../../services/CalendarService.php';
require_once __DIR__ . '/../../services/OwnerService.php';

function get_dashboard()
{

    // if (!is_admin() && !current_user_can('administrator') && !isset($_SESSION['mojo_owner_id'])) {
    if (!isset($_SESSION['mojo_owner_id'])) {
        echo "<script>window.location.href = 'login'</script>";
        wp_redirect(home_url('login'));
        exit;
    }

    $owner_id = isset($_SESSION['mojo_owner_id']) ? intval($_SESSION['mojo_owner_id']) : null;

    if (!is_int($owner_id)) {
        return '';
    }

    $owner_name = $_SESSION['mojo_owner_name'] ?? null;

    $service = new PropertyService();
    $calendar_service = new CalendarService();
    $owner_service = new OwnerService();
?>

    <main class="mojo_plugin mojo_dashboard-widget">
        <?php get_mojo_header(); ?>
        <section class="mojo_dashboard-body">
            <div class="mojo_container">
                <input type="hidden" name="owner_id" value="<?php echo $owner_id; ?>">
                <h1>Hello, <?php echo $owner_name; ?></h1>
                <div class="mojo_dashboard-container">
                    <div class="mojo_dashboard-shared" id="shared">
                        <h2>My shared properties</h2>
                        <div class="w-100">
                            <?php
                            $shared_properties = $service->getPropertiesInRelation($owner_id);
                            if (!empty($shared_properties)):
                            ?>
                                <div class="mojo_dashboard-list">
                                    <?php foreach ($shared_properties as $property): ?>
                                        <?php
                                        // We check if the owner is on the list of owners of this property
                                        $is_owner = false;
                                        $respective_shares = [];

                                        $is_your_turn = false;
                                        $is_your_share = false;

                                        $total_shares = intval($property['shares']);
                                        $busy_shares = intval(count($property['owners']));


                                        foreach ($property['owners'] as $owner) {
                                            if ($owner->id == $owner_id) {
                                                $respective_shares[] = $owner;
                                                $is_owner = true;
                                                // break;
                                            }
                                        }
                                        ?>
                                        <?php if ($is_owner && !empty($respective_shares)): ?>
                                            <?php foreach ($respective_shares as $share): ?>
                                                <?php
                                                $share_position = $share->owner_position;
                                                $calendar = $calendar_service->getCalendarByProperty($property['id'], 2026);

                                                if ($calendar && ($calendar->getStatus() == 'open')) {
                                                    $is_your_turn = $calendar_service->validateIfIsYourTurn($calendar->getId(), $owner_id, $share_position);

                                                    $currentRound = $calendar->getRound();
                                                    $currentTurn = $calendar->getTurn();
                                                }


                                                $order_owners_4_calendar = [];
                                                if ($calendar && !is_null($calendar->getOwnersPriority())) {
                                                    $owners_priority = json_decode(stripslashes($calendar->getOwnersPriority()), true);
                                                    if (!empty($owners_priority)) {
                                                        foreach ($owners_priority as $n => $oid) {
                                                            $owner = $owner_service->getOwner($oid, false);
                                                            $owner['owner_position'] = $n;
                                                            $order_owners_4_calendar[$n] = $owner;
                                                        }
                                                    }
                                                }

                                                $owners = $owner_service->getOwnersByProperty($property['id']);

                                                // Reindexar los owners por posición para acceso rápido
                                                $owners_by_position = [];

                                                if (!empty($owners)) {
                                                    foreach ($owners as $owner) {
                                                        $position = intval($owner['owner_position']);
                                                        $owners_by_position[$position] = $owner; // sobrescribe si hay duplicados
                                                    }
                                                }

                                                $current_share = get_current_share_of_current_owner($total_shares, $owners_by_position, $order_owners_4_calendar, $currentRound, $currentTurn);
                                                $is_your_share = $current_share == $share_position;
                                                ?>

                                                <div class="mojo_property_card">
                                                    <div class="mojo_property_card-thumb">
                                                        <?php if (isset($property['thumbnail']) && !empty($property['thumbnail'])): ?>
                                                            <img src="<?php echo $property['thumbnail']; ?>" alt="<?php echo $property['name']; ?>">
                                                        <?php else: ?>
                                                            <img src="<?php echo MEDIA; ?>/1.jpg">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mojo_property_card-info">
                                                        <div>

                                                            <?php if (isset($property['resell_shares']) && !empty($property['resell_shares'])): ?>
                                                                <div class="mojo_property_card-message">
                                                                    <p>
                                                                        <?php echo $property['resell_shares']; ?> RESELL SHARE FOR SALE
                                                                        <?php
                                                                        if (NOT_APPEAR && $busy_shares != $total_shares) {
                                                                            $v = $total_shares - $busy_shares;
                                                                            if ($v == 1) {
                                                                                echo ' / ' . $v . ' SHARE LEFT';
                                                                            } else {
                                                                                echo ' / ' . $v . ' SHARES LEFT';
                                                                            }
                                                                        }
                                                                        ?>
                                                                    </p>
                                                                </div>
                                                            <?php else: ?>
                                                                <?php if (NOT_APPEAR): ?>
                                                                    <?php if ($busy_shares != $total_shares): $b = $total_shares - $busy_shares; ?>
                                                                        <div class="mojo_property_card-message">
                                                                            <?php if ($b == 1): ?>
                                                                                <p><?php echo $total_shares - $busy_shares; ?> SHARE LEFT</p>
                                                                            <?php else: ?>
                                                                                <p><?php echo $total_shares - $busy_shares; ?> SHARES LEFT</p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            <?php endif; ?>

                                                            <?php if ($is_your_turn && $is_your_share): ?>
                                                                <div class="mojo_property_card-message">
                                                                    <p>It's your turn!</p>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="mojo_property_card-top">
                                                                <p><?php echo $property['name']; ?></p>
                                                                <p><?php echo $property['code']; ?></p>
                                                            </div>
                                                            <div class="mojo_property_card-shares">
                                                                <span>Share n° <?php echo $share_position; ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="mojo_property_card-bottom">
                                                            <a href="<?php echo esc_url(home_url('panel')) ?>?property_id=<?php echo $property['id']; ?>&property_share=<?php echo $share_position; ?>" class="manage">MANAGE PROPERTY</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>You do not have shared properties</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    $per_page = 5;
                    $max_num_pages = count($service->getPropertiesInRelation($owner_id, 3));
                    $total_pages = ceil($max_num_pages / $per_page);

                    $pagination = isset($_GET['pagination']) ? intval($_GET['pagination']) : 1;
                    $sharing_properties = $service->getPropertiesInRelation($owner_id, 2, $per_page, $pagination - 1);
                    if (!empty($sharing_properties)):
                    ?>
                        <div class="mojo_dashboard-sharing" id="sharing">
                            <h2>Other Sharing Properties</h2>
                            <div class="w-100">
                                <div class="mojo_dashboard-list">
                                    <?php foreach ($sharing_properties as $property): ?>
                                        <div class="mojo_property_card v2">
                                            <div class="mojo_property_card-thumb">
                                                <?php if (isset($property['thumbnail']) && !empty($property['thumbnail'])): ?>
                                                    <img src="<?php echo $property['thumbnail']; ?>" alt="<?php echo $property['name']; ?>">
                                                <?php else: ?>
                                                    <img src="<?php echo MEDIA; ?>/1.jpg">
                                                <?php endif; ?>
                                            </div>
                                            <div class="mojo_property_card-info">
                                                <div>
                                                    <?php if (isset($property['resell_shares']) && !empty($property['resell_shares'])): ?>
                                                        <div class="mojo_property_card-message">
                                                            <p><?php echo $property['resell_shares']; ?> RESELL SHARE FOR SALE</p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="mojo_property_card-top">
                                                        <p><?php echo $property['name']; ?></p>
                                                        <p><?php echo $property['code']; ?></p>
                                                    </div>
                                                    <div class="mojo_property_card-shares">
                                                        <?php if (!empty($property['property_type']) || !empty($property['bedroom'])): ?>
                                                            <span>
                                                                <?php if (!empty($property['property_type'])): ?>
                                                                    <?php echo $property['property_type']; ?><br>
                                                                <?php endif; ?>
                                                                <?php if (!empty($property['bedroom'])): ?>
                                                                    <?php echo $property['bedroom']; ?> Bedrooms
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if (!empty($property['location'])): ?>
                                                            <span><?php echo $property['location']; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mojo_property_card-bottom between">
                                                    <?php get_booking_calendar($property['id']); ?>

                                                    <?php if (isset($property['slug']) && !empty($property['slug'])): ?>
                                                        <a href="<?php echo esc_url(home_url('/property/' . $property['slug'] . '/')); ?>" class="view">
                                                            VIEW MORE INFO
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($total_pages > 1): ?>
                                    <div class="mojo_dashboard-pagination">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php $url = esc_url(home_url('/dashboard/')) . ($i == 1 ? '' : '?pagination=' . $i); ?>
                                            <a href="<?php echo $url; ?>" class="pagination-item <?php echo $i == $pagination || ($pagination == 0 && $i == 1) ? 'current' : ''; ?>"><?php echo $i; ?></a>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </section>
    </main>

    <?php if (isset($_GET['pagination'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const target = document.getElementById('sharing');
                if (target) {
                    const top = target.getBoundingClientRect().top + window.pageYOffset - 0;
                    window.scrollTo({
                        top,
                        behavior: 'smooth'
                    });
                }
            });
        </script>
    <?php endif; ?>
<?php
}
add_shortcode('mojo_dashboard', 'get_dashboard'); // [mojo_dashboard]