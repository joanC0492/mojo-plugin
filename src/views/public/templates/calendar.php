<div class="mojo_panel-calendar">
    <div class="w-100 relative">
        <div class="mojo_panel-select-and-buttons">
            <?php get_booking_calendar($property_id); ?>
            <?php if (!empty($calendars)): ?>
                <div class="mojo_panel-select">
                    <select name="year" id="select_year">
                        <?php foreach ($calendars as $calendarYear): ?>
                            <option value="<?php echo esc_attr($calendarYear); ?>"
                                <?php selected($year, $calendarYear); ?>>
                                <?php echo esc_html($calendarYear); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <div id="calendar"></div>
    </div>
    <div class="mojo_panel--aside">
        <div class="mojo_panel--aside-top">
            <?php get_aside_calendar(); ?>

            <div class="color_codes">
                <h4>COLOR CODES FOR OWNERS</h4>
                <ul class="color_codes-list">
                    <?php get_list_color_codes_for_owners($qty_shares, $owners_by_position, true, $property_id); ?>
                </ul>
            </div>

            <?php if ($calendar && $calendar->getStatus() == 'open' && $is_turn_of_the_owner && $is_share_of_the_owner): ?>
                <div class="selected_dates box_body_head">
                    <h4>SELECTED NIGHTS: <span
                            id="countSelectedCells"><?php echo $selected_days . '/' . $max_days; ?></span></h4>
                    <h4 id="date_range"></h4>
                </div>

                <?php
                $booked_dates = $serviceB->getSelectedDates($calendar_id, $owner_position, $round, 'object');
                if (!empty($booked_dates)):
                ?>
                    <div class="booked_dates box_body_head">
                        <h4>BOOKED DATES</h4>
                        <div>
                            <ul>
                                <?php foreach ($booked_dates as $n => $booked_date): ?>
                                    <li>
                                        <?php
                                        $start = new DateTime($booked_date->getStartDate());
                                        $end = new DateTime($booked_date->getEndDate());

                                        echo '<p>' . $start->format('d/m/y') . ' - ' . $end->format('d/m/y') . '</p>';
                                        ?>
                                        <button type="button" class="delete_booked_date"
                                            data-id="<?php echo $booked_date->getId(); ?>">&#x2715;</button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($calendar): ?>
                <div class="box_body_head box_body_request" data-state="0">
                    <h4>your selected dates</h4>
                    <div>
                        <p id="from_booking"></p>
                        <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="32" d="M320 120l48 48-48 48" />
                            <path d="M352 168H144a80.24 80.24 0 00-80 80v16M192 392l-48-48 48-48" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" />
                            <path d="M160 344h208a80.24 80.24 0 0080-80v-16" fill="none" stroke="currentColor"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="32" />
                        </svg>
                        <p id="to_booking"></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($calendar): ?>
            <div class="mojo_panel--aside-bottom">
                <?php if (NOT_APPEAR): ?>
                    <div class="mojo_panel--advice">
                        <div class="mojo_panel--advice_box">
                            <p>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_298_5364)">
                                        <path
                                            d="M9 11.5H8.5V7.031C8.5 7.0255 8.4985 7.0205 8.4985 7.0155C8.4985 7.0105 8.5 7.0055 8.5 7C8.5 6.724 8.276 6.5 8 6.5H7C6.724 6.5 6.5 6.724 6.5 7C6.5 7.276 6.724 7.5 7 7.5H7.5V11.5H7C6.724 11.5 6.5 11.724 6.5 12C6.5 12.276 6.724 12.5 7 12.5H9C9.276 12.5 9.5 12.276 9.5 12C9.5 11.724 9.276 11.5 9 11.5ZM8 5.5C8.5525 5.5 9 5.052 9 4.5C9 3.948 8.5525 3.5 8 3.5C7.4475 3.5 7 3.948 7 4.5C7 5.052 7.448 5.5 8 5.5ZM8 0C3.582 0 0 3.5815 0 8C0 12.4185 3.5815 16 8 16C12.4185 16 16 12.4185 16 8C16 3.5815 12.4185 0 8 0ZM8 15.0155C4.14 15.0155 1 11.8595 1 7.9995C1 4.1395 4.14 0.9995 8 0.9995C11.86 0.9995 15 4.1395 15 7.9995C15 11.8595 11.86 15.0155 8 15.0155Z"
                                            fill="#33475B" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_298_5364">
                                            <rect width="16" height="16" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                                To turn your booked dates from personal to rental (or revert that change), first you must select
                                the dates which that change will be applied to.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ------------------------------------------ -->

                <?php if ($selected_days < $max_days): ?>
                    <?php if ($calendar->getStatus() == 'open' && $is_turn_of_the_owner && $is_share_of_the_owner): ?>
                        <button type="submit" name="book" class="mojo_panel-submit mojo_panel-boooking">Book</button>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($selected_days >= $max_days): ?>
                    <?php if ($calendar->getStatus() == 'open' && $is_turn_of_the_owner && $is_share_of_the_owner): ?>
                        <button type="submit" name="confirm_dates" class="mojo_panel-submit mojo_panel-boooking">Confirm
                            Dates</button>
                    <?php endif; ?>
                <?php endif; ?>

                <?php //var_dump($is_share_of_the_owner);
                ?>
                <?php if (!$is_turn_of_the_owner || !$is_share_of_the_owner || $calendar->getStatus() != 'open'): ?>
                    <!-- It's not your turn yet -->
                    <div class="mojo_panel--actions">
                        <button type="submit" name="rent" class="mojo_panel-submit mojo_panel-renting">
                            Up for Rental
                        </button>
                        <button type="submit" name="cancel_rent" class="mojo_panel-submit mojo_panel-renting off"
                            style="background-color: #FF6161;">
                            Cancel Rental
                        </button>
                        <button type="submit" name="request" class="mojo_panel-submit mojo_panel-request off">
                            Request for personal use
                        </button>

                        <?php if (intval($round) >= 2 || intval($round) == 0): ?>
                            <button type="submit" name="exchange" class="mojo_panel-submit mojo_panel-exchange">
                                Exchange dates
                            </button>
                            <button type="submit" name="cancel_exchange" class="mojo_panel-submit mojo_panel-exchange off"
                                style="background-color: #FF6161;">
                                Cancel Exchange
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Purchase Rental Dates (front-only mockup, no backend yet) - jcc -->
                <div class="mojo_panel--actions mojo_panel--purchase" style="margin-top: 10px;">
                    <button type="button" name="buy" class="mojo_panel-submit mojo_panel-purchase">
                        Buy Rental Dates
                    </button>
                    <button type="button" name="cancel_buy" class="mojo_panel-submit mojo_panel-purchase off"
                        style="background-color: #FF6161;">
                        Cancel Purchase
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>