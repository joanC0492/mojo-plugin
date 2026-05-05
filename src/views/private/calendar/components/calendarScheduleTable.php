<div class="postbox">
    <div class="postbox-header">
        <h2>Picking Period Schedule</h2>

        <?php if ($calendar_status == 'open'): ?>
        <?php if (isset($order_owners_4_calendar[$currentTurn]['name'])): ?>
        <p style="margin:0;padding-inline:12px;">The current turn corresponds to
            <b><?php echo $order_owners_4_calendar[$currentTurn]['name']; ?></b> with priority <b>N°
                <?php echo $currentTurn ?></b>
        </p>
        <?php endif; ?>
        <?php endif; ?>

    </div>
    <div class="inside" style="margin-top:12px">
        <?php if (!empty($calendar_status) && $calendar_status == 'close' && $all_owner_id_is_1): ?>
        <p>The picking period schedule will be displayed here when the calendar opens.</p>
        <?php else: ?>

        <?php if ($calendar_status == 'completed'): ?>
        <style>
        #owners_schedule td {
            background: transparent !important;
        }
        </style>
        <?php endif; ?>

        <?php
        if (!empty($owners_by_position)) {
            get_picking_period_schedule($qty_shares, $owners_by_position, $order_owners_4_calendar, $currentRound, $currentTurn);
            //get_table_period($qty_shares, $owners_priority, $currentRound, $currentTurn);
            //echo get_current_share_of_current_owner($qty_shares, $owners_by_position, $order_owners_4_calendar, $currentRound, $currentTurn);
        } else {
            $edit_url = admin_url('admin.php?page=properties-admin-edit&id=' . $id_property);
            echo '<p>You have not selected any owner for your property yet. <br>
            <a href="' . esc_url($edit_url) . '">Click here to choose owners.</a>
            </p>';
        }
        ?>

        <?php endif; ?>
    </div>
    <?php if ($calendar_status == 'open'): ?>
    <?php if (isset($order_owners_4_calendar[$currentTurn]['owner_position'])): ?>
    <div class="postbox-footer">
        <form action="" method="post">
            <input type="hidden" name="in_round" value="<?php echo $currentRound; ?>">
            <input type="hidden" name="property_id" value="<?php echo $id_property; ?>">
            <input type="hidden" name="owner_position"
                value="<?php echo $order_owners_4_calendar[$currentTurn]['owner_position']; ?>">

            <?php if (($qty_shares == 5 && $currentRound == 6 && $currentTurn == 1) || ($qty_shares == 8 && $currentRound == 5 && $currentTurn == 8)): ?>
            <button class="button button-large button-primary" type="submit" name="pass_turn">Close Calendar</button>
            <?php else: ?>
            <button class="button button-large button-primary" type="submit" name="pass_turn">Next Turn</button>
            <?php endif; ?>

        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>