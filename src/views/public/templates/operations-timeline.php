<?php
if (empty($operations)) {
    return;
}
?>

<div class="mojo_panel-timeline">
    <ul>
        <?php foreach ($operations as $operation): ?>
            <?php
            $operation_date = $operation->operation_date;
            $actual_date    = date('Y-m-d');

            if ($operation->type === 'temporary' && $operation_date < $actual_date) {
                continue;
            }
            ?>
            <li class="<?php echo esc_attr($operation->type); ?>">
                <span><?php echo esc_html(transform_date($operation_date)); ?></span>
                <div>
                    <p><?php echo esc_html($operation->title); ?></p>

                    <?php if (!empty($operation->description)): ?>
                        <p><?php echo esc_html($operation->description); ?></p>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
