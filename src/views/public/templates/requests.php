<?php if (!empty($request_from) || !empty($request_to)): ?>
    <div class="mojo_panel-content mojo_panel-exchange-requests">
        <ul class="exchange_requests">
            <?php if (!empty($request_from)): ?>
                <?php foreach ($request_from as $from): ?>
                    <?php $status = $from['status']; ?>
                    <li class="request_from request" data-id="<?php echo $from['id']; ?>">
                        <div class="request_info">
                            <p><b>You requested <?php echo $from['to_owner']->getName(); ?></b></p>
                            <p>
                                <?php echo transform_date($from['start_from']); ?> - <?php echo transform_date($from['end_from']); ?>
                                &nbsp;&#8594;&nbsp;
                                <?php echo transform_date($from['start_to']); ?> - <?php echo transform_date($from['end_to']); ?>
                            </p>
                            <p>Status: <b><?php echo ucfirst($status); ?></b></p>
                        </div>
                        <div class="request_actions">
                            <?php if ($status == 'pending'): ?>
                                <button type="button" class="request-button" data-action="canceled">
                                    Cancel
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                                        <circle cx="256" cy="256" r="208" fill="none" stroke="white" stroke-miterlimit="10"
                                            stroke-width="32" />
                                        <path fill="none" stroke="white" stroke-miterlimit="10" stroke-width="32"
                                            d="M108.92 108.92l294.16 294.16" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($request_to)): ?>
                <?php foreach ($request_to as $to): ?>
                    <?php $status = $to['status']; ?>
                    <li class="request_to request" data-id="<?php echo $to['id']; ?>">
                        <input type="hidden" name="from_owner" value="<?php echo $to['from_owner']->getId() ?>">
                        <input type="hidden" name="to_owner" value="<?php echo $to['to_owner']->getId() ?>">
                        <input type="hidden" name="requestor_dates" value="<?php echo transform_date($to['start_from']); ?> - <?php echo transform_date($to['end_from']); ?>">
                        <input type="hidden" name="recipient_dates" value="<?php echo transform_date($to['start_to']); ?> - <?php echo transform_date($to['end_to']); ?>">
                        <div class="request_info">
                            <p><b><?php echo $to['from_owner']->getName(); ?> requested you</b></p>
                            <p>
                                <?php echo transform_date($to['start_to']); ?> - <?php echo transform_date($to['end_to']); ?>
                                &nbsp;&#8594;&nbsp;
                                <?php echo transform_date($to['start_from']); ?> - <?php echo transform_date($to['end_from']); ?>
                            </p>
                            <p>Status: <b><?php echo ucfirst($status); ?></b></p>
                        </div>
                        <div class="request_actions">
                            <?php if ($status == 'pending'): ?>
                                <button type="button" class="request-button" data-action="approved">
                                    Approve
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                                        <path fill="none" stroke="white" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="32" d="M416 128L192 384l-96-96" />
                                    </svg>
                                </button>
                                <button type="button" class="request-button" data-action="rejected">
                                    Decline
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                                        <path fill="none" stroke="white" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="32" d="M368 368L144 144M368 144L144 368" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>