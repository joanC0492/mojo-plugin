<?php

require_once __DIR__ . '../../../../services/SeasonsService.php';

function seasons_system_page()
{
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y')) + 0;
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Seasons</h1>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Seasons</h2>
        <br>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>
                                <select name="year" id="year" style="width:100px">
                                    <?php for ($i = 0; $i < 4; $i++):
                                        $val = date('Y') + $i;
                                    ?>
                                        <option value="<?php echo $val; ?>" <?php echo !is_null($year) && $val == $year ? 'selected' : ''; ?>>
                                            <?php echo $val; ?>
                                        </option>';
                                    <?php endfor; ?>
                                </select>
                            </h2>
                        </div>
                        <div class="inside">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
                <!-- /post-body-content -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>Actions</h2>
                        </div>
                        <div class="inside postbox_actions" style="margin-top:12px">
                            <button type="button" style="color:black;background:<?php echo get_color('high'); ?>;border-color:<?php echo get_color('high'); ?>;" name="high" class="season_button button button-large">High</button>
                            <button type="button" style="color:black;background:<?php echo get_color('middle'); ?>;border-color:<?php echo get_color('middle'); ?>;" name="middle" class="season_button button button-large">Middle</button>
                            <button type="button" style="color:black;background:<?php echo get_color('low'); ?>;border-color:<?php echo get_color('low'); ?>;" name="low" class="season_button button button-large">Low</button>
                            <!-- <button type="button" style="color:black;background:<?php echo get_color('14-day'); ?>;border-color:<?php echo get_color('14-day'); ?>;" name="14-day" class="season_button button button-large">14-Day Period</button> -->
                            <hr>
                            <button type="button" style="background:#ddd;border-color:#ddd;color:black;" class="season_clear button button-large">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /post-body -->
            <br class="clear" />
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearSelect = document.getElementById('year');
            yearSelect.addEventListener('change', function() {
                const selectedYear = this.value,
                    url = new URL(window.location.href);
                url.searchParams.set('year', selectedYear);
                window.location.href = url.toString();
            });
        });

        let events;
        events = [
            <?php get_seasons(); ?>
        ];
    </script>
<?php
}

function save_or_update_season()
{
    $season_service = new SeasonsService();

    $date = sanitize_text_field($_POST['date']);
    $type = sanitize_text_field($_POST['type']);
    $year = sanitize_text_field($_POST['year']);

    if (!$date || !$type || !$year) {
        wp_send_json_error('Incomplete data');
    }

    $existing = $season_service->getSeason($date, $year);

    if ($existing) {
        $updating = $season_service->updateSeason($existing, $date, $type, $year);
    } else {
        $creating = $season_service->createSeason($date, $type, $year);
    }

    if ($updating || $creating) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Error inserting');
    }
}
add_action('wp_ajax_save_or_update_season', 'save_or_update_season');

function remove_season()
{
    $season_service = new SeasonsService();

    $date = sanitize_text_field($_POST['date']);
    $year = sanitize_text_field($_POST['year']);

    if (!$date || !$year) {
        wp_send_json_error('Incomplete data');
    }

    $existing = $season_service->getSeason($date, $year);

    if ($existing) {
        $removing = $season_service->removeSeason($existing);
        if ($removing) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Error: Record not deleted');
        }
    } else {
        wp_send_json_error('Error: No record exists');
    }
}
add_action('wp_ajax_remove_season', 'remove_season');
