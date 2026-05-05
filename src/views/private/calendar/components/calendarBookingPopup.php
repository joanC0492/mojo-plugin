<div class="popup" id="booking">
    <div class="popup_bg close_popup"></div>
    <div class="popup_content postbox">
        <div class="postbox-header">
            <h2 style="margin-inline: auto">Book a period for Owner</h2>
        </div>
        <div class="inside">
            <div class="popup_content-form">
                <form method="post" action="">
                    <table class="form-table">
                        <tr>
                            <th style="vertical-align: middle">
                                <label>Start Date<br>&<br>End Date</label>
                            </th>
                            <td>
                                <input type="text" name="start_date" class="regular-text disabled">
                                <br><br>
                                <input type="text" name="end_date" class="regular-text disabled">
                            </td>
                        </tr>
                        <?php if (!empty($owners_by_position)): ?>
                        <tr>
                            <th>
                                <label for="owner_selected">Select Owner</label>
                            </th>
                            <td>
                                <?php if ($currentRound % 2 == 0): ?>
                                <select name="owner_selected" id="owner_selected" class="regular-text disabled"
                                    required>
                                    <option value="">Select Owner</option>
                                    <?php
                                        for ($i = $qty_shares; $i >= 1; $i--):
                                            if (isset($order_owners_4_calendar[$i])):
                                        ?>
                                    <option
                                        value="<?php echo $i . ' - ' . esc_attr($order_owners_4_calendar[$i]['id']); ?>"
                                        <?php echo $i == $currentTurn ? 'selected' : '' ?>>
                                        <?php echo esc_html($order_owners_4_calendar[$i]['name']); ?>
                                    </option>
                                    <?php
                                        endif;
                                        endfor;
                                    ?>
                                </select>
                                <?php else: ?>
                                <select name="owner_selected" id="owner_selected" class="regular-text disabled"
                                    required>
                                    <option value="">Select Owner</option>
                                    <?php for ($i = 1; $i < intval($qty_shares + 1); $i++): ?>
                                    <?php if (isset($order_owners_4_calendar[$i])): ?>
                                    <option
                                        value="<?php echo $i . ' - ' . esc_attr($order_owners_4_calendar[$i]['id']); ?>"
                                        <?php echo $i == $currentTurn ? 'selected' : '' ?>>
                                        <?php echo esc_html($order_owners_4_calendar[$i]['name']); ?>
                                    </option>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </select>
                                <?php endif; ?>

                                <?php if (NOT_APPEAR): ?>
                                <small class="howto" style="margin-top:4px">
                                    <i>
                                        <?php if (isset($order_owners_4_calendar[$currentTurn]['name'])): ?>
                                        The current turn corresponds to
                                        <b><?php echo $order_owners_4_calendar[$currentTurn]['name']; ?></b> with
                                        priority <b>N° <?php echo $currentTurn ?></b>
                                        <?php else: ?>
                                        The current turn corresponds to <b>Mojo Sharing</b> with priority <b>N°
                                            <?php echo $currentTurn ?></b>
                                        <?php endif; ?>
                                    </i>
                                </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>
                                <label for="use_selected">Select Use</label>
                            </th>
                            <td>
                                <select name="use_selected" id="use_selected" class="regular-text" required>
                                    <option value="">Select Use</option>
                                    <option value="for rent">For Rent</option>
                                    <option value="for personal use" selected>For Personal Use</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: center">
                                <input type="hidden" name="in_round" value="<?php echo $currentRound; ?>">
                                <input class="button button-primary button-large" type="submit" name="create_period"
                                    value="Book A Period" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>