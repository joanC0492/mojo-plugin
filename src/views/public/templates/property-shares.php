<?php if ($property && $property->getShowShares()): ?>
<div class="mojo_panel-content mojo_panel-share-owners">
    <?php
        $shares_left = 0;
        for ($i = 1; $i <= $qty_shares; $i++):
            $owner = $owners_by_position[$i] ?? null;
        ?>
    <div class="share_owner">
        <?php if ($owner && $owner['is_active'] == 1): ?>
        <div class="share_owner-head">
            <p>Share Owner <?php echo $i; ?> <?php //echo $i == $property_share ? '(here)' : ''; ?></p>
        </div>
        <div class="share_owner-body">
            <p><?php echo esc_html($owner['name']); ?></p>

            <?php if ($owner['visible_info'] == 0): ?>
            <p><span>Email: &nbsp;&nbsp;</span> -</p>
            <p><span>Phone: &nbsp;</span> -</p>
            <?php else: ?>
            <?php if (!empty($owner['email'])): ?>
            <p><span>Email: &nbsp;&nbsp;</span> <?php echo esc_html($owner['email']); ?></p>
            <?php else: ?>
            <p><span>Email: &nbsp;&nbsp;</span> -</p>
            <?php endif; ?>

            <?php if (!empty($owner['phone'])): ?>
            <p><span>Phone: &nbsp;</span> <?php echo esc_html($owner['phone']); ?></p>
            <?php else: ?>
            <p><span>Phone: &nbsp;</span> -</p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php else: $shares_left++; ?>
        <div class="share_owner-head">
            <p>Share Owner <?php echo $i; ?></p>
        </div>
        <div class="share_owner-body">
            <p>Mojo Sharing</p>
            <p><span>Email: &nbsp;&nbsp;</span> -</p>
            <p><span>Phone: &nbsp;</span> -</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div>
<?php endif; ?>