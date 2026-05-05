<div id="postbox-container-1" class="postbox-container">
    <div class="postbox">
        <div class="postbox-header">
            <h2>Actions</h2>
        </div>
        <div class="inside postbox_actions" style="margin-top:12px">

            <?php if (NOT_APPEAR): ?>
                <style>
                    .mojo-toggle-row {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        margin-bottom: 12px
                    }

                    .mojo-toggle-label {
                        font-weight: 600
                    }

                    .mojo-toggle {
                        position: relative;
                        display: inline-block;
                        width: 46px;
                        height: 26px;
                        flex: 0 0 auto
                    }

                    .mojo-toggle input {
                        opacity: 0;
                        width: 0;
                        height: 0
                    }

                    .mojo-toggle-slider {
                        position: absolute;
                        cursor: pointer;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: #c3c4c7;
                        transition: .2s;
                        border-radius: 999px
                    }

                    .mojo-toggle-slider:before {
                        position: absolute;
                        content: "";
                        height: 20px;
                        width: 20px;
                        left: 3px;
                        top: 3px;
                        background: white;
                        transition: .2s;
                        border-radius: 50%
                    }

                    .mojo-toggle input:checked+.mojo-toggle-slider {
                        background: #2271b1
                    }

                    .mojo-toggle input:checked+.mojo-toggle-slider:before {
                        transform: translateX(20px)
                    }

                    .mojo-toggle-hint {
                        font-size: 12px;
                        color: #646970;
                        margin: -6px 0 12px
                    }
                </style>

                <form id="mojoToggleDownloadCalendarForm" method="post" action="" style="margin-bottom:0">
                    <input type="hidden" name="toggle_download_calendar_update" value="1">
                    <input type="hidden" name="toggle_download_calendar" value="0">

                    <div class="mojo-toggle-row">
                        <div class="mojo-toggle-label">Allow calendar download</div>
                        <label class="mojo-toggle" aria-label="Allow calendar download">
                            <input id="mojoToggleDownloadCalendar" type="checkbox" name="toggle_download_calendar" value="1" <?php echo (isset($calendar) && (int) $calendar->getToggleDownloadCalendar() === 1) ? 'checked' : ''; ?>>
                            <span class="mojo-toggle-slider"></span>
                        </label>
                    </div>
                </form>

                <script>
                    (function() {
                        var el = document.getElementById('mojoToggleDownloadCalendar');
                        var form = document.getElementById('mojoToggleDownloadCalendarForm');
                        if (!el || !form) return;
                        el.addEventListener('change', function() {
                            form.submit();
                        });
                    })();
                </script>
            <?php endif; ?>

            <?php if ($calendar_status == 'open'): ?>
                <form method="post" action="">
                    <button class="button button-large button-primary" type="submit" name="pause_calendar">Pause Calendar
                        <?php echo $year; ?></button>
                    <input type="hidden" name="property_id" value="<?php echo $id_property; ?>">
                </form>
            <?php endif; ?>

            <?php if ($calendar_status != 'close'): ?>
                <form method="post" action="">
                    <button class="button button-large button-primary" type="submit" name="reset_calendar">Reset Calendar
                        <?php echo $year; ?></button>
                    <input type="hidden" name="property_id" value="<?php echo $id_property; ?>">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                </form>
            <?php endif; ?>

            <?php if ($calendar_status == 'pause'): ?>
                <form method="post" action="">
                    <button class="button button-large button-primary" type="submit" name="resume_calendar">Resume Calendar
                        <?php echo $year; ?></button>
                    <input type="hidden" name="property_id" value="<?php echo $id_property; ?>">
                </form>
            <?php endif; ?>

            <?php if ($calendar_status != 'close'): ?>
                <button type="button" class="button button-primary button-large open_popup" name="book"
                    style="display: none;">Book A Period</button>
            <?php endif; ?>

            <?php if ($calendar_status == 'close' && !$all_owner_id_is_1): ?>
                <button type="button" class="button button-primary button-large open_popup" data-id="sort_owners">Open
                    Calendar <?php echo $year; ?></button>
            <?php endif; ?>

            <?php if (!$all_owner_id_is_1): ?>
                <form method="post" action="">
                    <button class="button button-large button-primary" type="submit" name="block_date">Block dates</button>
                    <input type="hidden" name="blocked_dates" value="">
                </form>
            <?php endif; ?>

            <button id="comment" class="button button-large button-primary open_popup" type="submit" name="add_comment">Add a comment</button>

            <?php if ($calendar_status == 'close' && $all_owner_id_is_1): ?>
                <p>The property must have at least 1 owner listed in order to open the calendar.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php $blocked_dates = $blockdates_service->getBlockedDatesByCalendarId($id_calendar); ?>
    <?php if (!empty($blocked_dates)): ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2>Blocked Dates</h2>
            </div>
            <div class="inside postbox_actions" style="margin-top:12px">
                <ul style="margin: 0;">
                    <?php foreach ($blocked_dates as $date): ?>
                        <li>
                            <form method="post" action="" class="close_blocked_date">
                                <?php echo $date->getDate(); ?>
                                <input type="hidden" name="id_blocked_date" value="<?php echo $date->getId(); ?>">
                                <button type="submit" name="delete_blocked_date">&#x2715;</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php get_aside_calendar(2); ?>
    <div class="inside_aside postbox">
        <div class="postbox-header">
            <h2>Color Codes for Owners</h2>
        </div>
        <div class="inside" style="margin-top:12px">
            <?php if (!empty($owners_by_position) && $qty_shares != 0): ?>
                <ul class="color_codes-list" style="margin: 0;">
                    <?php get_list_color_codes_for_owners($qty_shares, $owners_by_position); ?>
                </ul>
            <?php else: ?>
                <p>You need to associate owners with the property to open the calendar.</p>
            <?php endif; ?>
        </div>
    </div>
</div>