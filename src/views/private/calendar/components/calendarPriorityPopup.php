<?php if ($calendar_status == 'close'): ?>
<div class="popup" id="sort_owners">
    <div class="popup_bg close_popup"></div>
    <div class="popup_content postbox inside_aside">
        <div class="postbox-header">
            <h2 style="margin-inline: auto">Pick the initial priority</h2>
        </div>
        <div class="inside" style="margin-top: 12px">
            <?php if (!empty($owners_by_position) && $qty_shares != 0): ?>
            <ul id="sort" style="margin: 0">
                <?php get_list_color_codes_for_owners($qty_shares, $owners_by_position, false); ?>
            </ul>
            <br><br>
            <form method="post" action="" style="text-align: center">
                <?php
                    $ordered_owner_ids = [];
                    for ($i = 1; $i <= $qty_shares; $i++) {
                        if (!empty($owners_by_position[$i]['owner_id'])) {
                            $ordered_owner_ids[$i] = (int)$owners_by_position[$i]['owner_id'];
                        } else {
                            $ordered_owner_ids[$i] = 0;
                        }
                    }
                ?>

                <?php if (!empty($ordered_owner_ids)): ?>
                <button class="button button-large button-primary" type="submit" name="open_calendar">Open Calendar
                    <?php echo $year; ?></button>
                <input type="hidden" name="ordered_owners" value='<?php echo json_encode($ordered_owner_ids); ?>'>

                <?php
                    $ordered = array_combine(range(1, count(COLOR_CODES)), COLOR_CODES);
                    $json = json_encode($ordered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $attr = esc_attr($json);
                ?>
                <input type="hidden" name="ordered_colors" value='<?php echo $attr; ?>'>
                <?php else: ?>
                <button class="button button-large button-primary disabled" type="submit" name="open_calendar"
                    disabled>Open Calendar <?php echo $year; ?></button>
                <input type="hidden" name="ordered_owners" value="">
                <input type="hidden" name="ordered_colors" value="">
                <?php endif; ?>

                <input type="hidden" name="property_id" value="<?php echo $id_property; ?>">
                <input type="hidden" name="year" value="<?php echo $year; ?>">
            </form>
            <?php else: ?>
            <p>You need to associate owners with the property to open the calendar.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>