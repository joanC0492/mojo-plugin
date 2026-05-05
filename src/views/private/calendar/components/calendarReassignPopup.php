<div class="popup" id="reassigning">
    <div class="popup_bg close_popup"></div>
    <div class="popup_content postbox">
        <div class="postbox-header">
            <h2 style="margin-inline: auto">Reassigned Period</h2>
        </div>
        <div class="inside">
            <div class="popup_content-form">
                <form method="post" action="">
                    <table class="form-table">
                        <input type="hidden" name="id_booking_r" value="">
                        <tr>
                            <th style="vertical-align: middle">
                                <label>Start Date<br>&<br>End Date</label>
                            </th>
                            <td>
                                <input type="text" name="start_date_r" class="regular-text">
                                <br><br>
                                <input type="text" name="end_date_r" class="regular-text">
                            </td>
                        </tr>
                        <?php if (!empty($owners_by_position)): ?>
                        <tr>
                            <th>
                                <label for="owner_selected_r">Select Owner</label>
                            </th>
                            <td>
                                <?php if ($currentRound % 2 == 0): ?>
                                <select name="owner_selected_r" id="owner_selected_r" class="regular-text" required>
                                    <option value="">Select Owner</option>
                                    <?php
                                        for ($i = $qty_shares; $i >= 1; $i--):
                                            if (isset($order_owners_4_calendar[$i])):
                                        ?>
                                    <option
                                        value="<?php echo $i . ' - ' . esc_attr($order_owners_4_calendar[$i]['id']); ?>">
                                        <?php echo esc_html($order_owners_4_calendar[$i]['name']) . ' (' . $i . ')'; ?>
                                    </option>
                                    <?php endif;
                                        endfor;
                                    ?>
                                </select>
                                <?php else: ?>
                                <select name="owner_selected_r" id="owner_selected_r" class="regular-text" required>
                                    <option value="">Select Owner</option>
                                    <?php for ($i = 1; $i < intval($qty_shares + 1); $i++): ?>
                                    <?php if (isset($order_owners_4_calendar[$i])): ?>
                                    <option
                                        value="<?php echo $i . ' - ' . esc_attr($order_owners_4_calendar[$i]['id']); ?>">
                                        <?php echo esc_html($order_owners_4_calendar[$i]['name']) . ' (' . $i . ')'; ?>
                                    </option>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </select>
                                <?php endif; ?>

                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>
                                <label for="use_selected_r">Select Use</label>
                            </th>
                            <td>
                                <select name="use_selected_r" id="use_selected_r" class="regular-text" required>
                                    <option value="">Select Use</option>
                                    <option value="for rent">For Rent</option>
                                    <option value="for personal use">For Personal Use</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: center">
                                <input type="hidden" name="in_round" value="<?php echo $currentRound; ?>">
                                <input class="button button-primary button-large" type="submit" name="edit_period"
                                    value="Edit Period" />
                                <input class="button button-primary button-large" type="submit" name="delete_period"
                                    value="Delete Period" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>