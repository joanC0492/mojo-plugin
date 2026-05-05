<?php if (!empty($contacts)): ?>
<div class="mojo_panel-contacts <?php if (empty($operations) && empty($whatsapp) && empty($facebook)) { echo 'no_mb';} ?>">
    <div>
        <h3>Contact for administration</h3>
        <?php if (!empty($contacts->admin_name)) : ?>
        <p><span>Name:</span> <?php echo $contacts->admin_name ?></p>
        <?php else: ?>
        <p><span>Name:</span> -</p>
        <?php endif; ?>

        <?php if (!empty($contacts->admin_email)) : ?>
        <p><span>Email:</span> <a
                href="mailto:<?php echo $contacts->admin_email ?>"><?php echo $contacts->admin_email ?></a></p>
        <?php else: ?>
        <p><span>Email:</span> -</p>
        <?php endif; ?>

        <?php if (!empty($contacts->admin_phone)) : ?>
        <p><span>Phone:</span> <a
                href="tel:<?php echo $contacts->admin_phone ?>"><?php echo $contacts->admin_phone ?></a></p>
        <?php else: ?>
        <p><span>Phone:</span> -</p>
        <?php endif; ?>
    </div>
    <div>
        <h3>Contact for sale</h3>
        <?php if (!empty($contacts->sale_name)) : ?>
        <p><span>Name:</span> <?php echo $contacts->sale_name ?></p>
        <?php else: ?>
        <p><span>Name:</span> -</p>
        <?php endif; ?>

        <?php if (!empty($contacts->sale_email)) : ?>
        <p><span>Email:</span> <a
                href="mailto:<?php echo $contacts->sale_email ?>"><?php echo $contacts->sale_email ?></a></p>
        <?php else: ?>
        <p><span>Email:</span> -</p>
        <?php endif; ?>

        <?php if (!empty($contacts->sale_phone)) : ?>
        <p><span>Phone:</span> <a href="tel:<?php echo $contacts->sale_phone ?>"><?php echo $contacts->sale_phone ?></a>
        </p>
        <?php else: ?>
        <p><span>Phone:</span> -</p>
        <?php endif; ?>
    </div>

</div>
<?php endif; ?>