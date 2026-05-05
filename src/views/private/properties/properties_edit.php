<?php

require_once __DIR__ . '../../../../services/PropertyOperationService.php';
require_once __DIR__ . '../../../../services/PropertyService.php';
require_once __DIR__ . '../../../../services/OwnerService.php';
require_once __DIR__ . '../../../../services/CalendarService.php';

function properties_admin_edit_page_callback()
{
    $property_service     = new PropertyService();
    $calendar_service     = new CalendarService();
    $owner_service        = new OwnerService();
    $property_op_service  = new PropertyOperationService();

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) {
        echo '<br><div class="notice notice-error"><p>Invalid ID.</p></div>';
        return;
    }
    // Si se envió el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_property'])) {
        $result = $property_service->handleUpdateProperty($id, $_POST);

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    // Cargar propiedad
    $property = $property_service->getProperty($id);
    if (!$property) {
        echo '<div class="notice notice-error"><p>Property not found.</p></div>';
        return;
    }

    $owners = $owner_service->getAllOwners(1, 10000);
    usort($owners, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    $selected_owner_ids = $property_service->getOwnerIdsByProperty($id);
    $year = intval($_GET['year'] ?? (date('Y') + 1));
    $calendar = $calendar_service->getCalendarByProperty($id, $year);
    $calendar_status = $calendar?->getStatus() ?? '';

    $operations = $property_op_service->getAllProperties($id);
    $number_of_shares = $property->getShare() ?? 0;
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">Edit Property</h1>
        <form method="post" action="">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Campo Name (como título) -->
                        <div id="titlediv">
                            <div id="titlewrap">
                                <input type="text" name="name" id="title" placeholder="Enter Owner Name here" value="<?php echo esc_attr($property->getName()); ?>" required />
                            </div>
                            <div class="inside">
                                <div id="edit-slug-box" class="hide-if-no-js">
                                    <strong><?php _e('Enlace permanente:', 'text-domain'); ?></strong>
                                    <span id="sample-permalink">
                                        <a target="_blank" href="<?php echo esc_url(home_url('/property/' . $property->getSlug() . '/')); ?>"><?php echo esc_html(home_url('/property/')); ?><span id="editable-post-name"><?php echo esc_html($property->getSlug()); ?></span>/</a>
                                    </span>
                                    <?php $l = 0;
                                    if ($l == 1): ?>
                                        &lrm;
                                        <span id="edit-slug-buttons">
                                            <button type="button" class="edit-slug button button-small hide-if-no-js">
                                                <?php _e('Editar', 'mojo-sharing'); ?>
                                            </button>
                                        </span>
                                        <span id="editable-post-name-full"><?php echo $property->getSlug(); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <br>
                        <!-- Editor WYSIWYG -->
                        <div class="postarea wp-editor-expand">
                            <div class="inside">
                                <?php wp_editor($property->getDescription() ?? '', 'property_description', [
                                    'textarea_name' => 'property_description',
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                    'teeny' => false,
                                ]); ?>
                            </div>
                        </div>
                        <br>
                        <!-- Metabox con campos -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Card Property</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <input type="number" name="is_active" id="is_active" value="<?php echo esc_attr($property->getStatus()); ?>" style="display:none;">
                                    <input type="text" name="slug" id="slug" value="<?php echo esc_attr($property->getSlug()); ?>" style="display:none;">
                                    <tr>
                                        <th><label for="share_qty">Share Quantity <span class="required">*</span></label></th>
                                        <td><input type="text" name="share_qty" id="share_qty" value="<?php echo esc_attr($number_of_shares); ?>" class="regular-text disabled"></td>
                                    </tr>
                                    <tr>
                                        <th><label for="code">Ref. <span class="required">*</span></label></th>
                                        <td><input type="text" name="code" id="code" class="regular-text" value="<?php echo esc_attr($property->getCode()); ?>" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="property_type">Property Type</label></th>
                                        <td>
                                            <?php
                                            $property_type_selected = $property->getPropertyType() ?? '';
                                            ?>
                                            <select name="property_type" id="property_type" class="regular-text">
                                                <?php
                                                $types = ['Apartment','Ground Floor Apartment','Duplex Penthouse','Garden Apartment','Villa'];
                                                foreach ($types as $type):
                                                ?>
                                                    <option value="<?php echo $type; ?>" <?php selected($property->getPropertyType(), $type); ?>><?php echo $type; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="location">Location</label></th>
                                        <td><input type="text" name="location" id="location" class="regular-text" value="<?php echo esc_attr($property->getLocation()); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="rental_booking_page">Rental Booking Page</label></th>
                                        <td><input type="text" name="rental_booking_page" id="rental_booking_page" class="regular-text" value="<?php echo esc_attr($property->getRentalBookingPage()); ?>" /></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Single Property Page</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="title">Property Page Title</label></th>
                                        <td><input type="text" name="title" id="title" class="regular-text" value="<?php echo esc_attr($property->getTitle()); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="resell_shares">Resell Shares</label></th>
                                        <td>
                                            <input type="number" name="resell_shares" id="resell_shares" class="regular-text" value="<?php echo esc_attr($property->getResellShares()); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="bedroom">Bedroom</label></th>
                                        <td><input type="number" name="bedroom" id="bedroom" class="regular-text" value="<?php echo esc_attr($property->getBedroom()); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="bathroom">Bathroom</label></th>
                                        <td><input type="number" name="bathroom" id="bathroom" class="regular-text" value="<?php echo esc_attr($property->getBathroom()); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="key_features">Key Features</label></th>
                                        <td>
                                            <input type="text" name="key_features" id="key_features" class="regular-text" value="<?php echo esc_attr($property->getKeyFeatures()); ?>" />
                                            <small class="howto"><i><b>Separate by commas.</b> Only first 6 features will be displayed.</i></small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>On The User Panel Page</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="facebook_group">Facebook Group</label></th>
                                        <td><input type="text" name="facebook_group" id="facebook_group" value="<?php echo esc_attr($property->getFbGroup()); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="whatsapp_group">Whatsapp Group</label></th>
                                        <td><input type="text" name="whatsapp_group" id="whatsapp_group" class="regular-text" value="<?php echo esc_attr($property->getWspGroup()); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="show_shares">Show Shares Owners?</label></th>
                                        <td>
                                            <input type="checkbox" name="show_shares" id="show_shares" class="regular-text" <?php echo $property->getShowShares() ? 'checked' : ''; ?> />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- List of Owners -->
                        <?php
                            $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y')) + 0;
                            $selected_owner_ids = $property_service->getOwnerIdsByProperty($id);
                            $calendar = $calendar_service->getCalendarByProperty($id, $year);

                            $calendar_status = '';
                            if (!empty($calendar) && method_exists($calendar, 'getStatus')) {
                                $calendar_status = $calendar->getStatus();
                            }

                            // $is_disabled = in_array($calendar_status, ['open', 'pause']);
                            $is_disabled = false;
                        ?>

                        <?php if (!empty($number_of_shares) && !empty($owners)): ?>
                            <div class="postbox postbox_owners">
                                <div class="postbox-header">
                                    <h2>List of Owners</h2>
                                </div>
                                <div class="inside">
                                    <?php $number_of_shares++; ?>
                                    <table class="form-table">
                                        <?php for ($i = 1; $i < intval($number_of_shares); $i++): ?>
                                            <tr>
                                                <th><label for="owner_<?php echo $i; ?>">Share <?php echo $i; ?></label></th>
                                                <td>
                                                    <select name="owner_<?php echo $i; ?>" id="owner_<?php echo $i; ?>"
                                                        class="regular-text <?php echo $is_disabled ? 'disabled' : ''; ?>"
                                                        <?php //echo $is_disabled ? 'disabled' : ''; ?>>
                                                        <option value="<?php echo NOT_ASSIGNED_ID; ?>">Mojo Sharing</option>
                                                        <?php foreach ($owners as $owner): ?>
                                                            <?php
                                                            $selected = '';
                                                            foreach ($selected_owner_ids as $selected_owner) {
                                                                if ($selected_owner->owner_id == $owner['id'] && $selected_owner->owner_position == $i) {
                                                                    $selected = 'selected="selected"';
                                                                    break;
                                                                }
                                                            }
                                                            ?>
                                                            <option value="<?php echo $owner['id']; ?>" <?php echo $selected; ?>>
                                                                <?php echo esc_html($owner['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php
                        $property_operation = new PropertyOperationService();
                        $operations = $property_operation->getAllProperties($id);
                        // var_dump($operations);
                        ?>
                        <div class="postbox postbox-property-operation">
                            <div class="postbox-header">
                                <h2>Property Operation</h2>
                            </div>
                            <div class="inside">

                                <table class="form-table" id="property_operation" data-property-id="<?php echo $id; ?>">
                                    <thead>
                                        <tr>
                                            <th>
                                                <label for="operation_date">Date <span class="required">(*)</span></label>
                                                <input type="date" name="operation_date" id="operation_date" class="regular-text">
                                            </th>
                                            <th>
                                                <label for="operation_title">Title <span class="required">(*)</span></label>
                                                <input type="text" name="operation_title" id="operation_title" placeholder="Title" class="regular-text">
                                            </th>
                                            <th>
                                                <label for="operation_description">Description</label>
                                                <input type="text" name="operation_description" id="operation_description" placeholder="Description" class="regular-text">
                                            </th>
                                            <th>
                                                <label for="operation_type">Type</label>
                                                <select name="operation_type" id="operation_type" class="regular-text">
                                                    <option value="temporary">Temporary</option>
                                                    <option value="fixed">Fixed</option>
                                                </select>
                                            </th>
                                            <th>
                                                <label for=""> ︎ ︎ ︎ ︎ ︎ ︎ ︎</label>
                                                <button type="button" id="add_property_operation" class="button button-primary" title="Add">ADD</button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($operations)): ?>
                                            <?php foreach ($operations as $operation): ?>
                                                <tr>
                                                    <td colspan="4">
                                                        <?php if (!empty($operation->type)): ?>
                                                            <p><b><?php echo $operation->title; ?></b> - <span><i><?php echo $operation->operation_date; ?> (<?php echo ucfirst($operation->type); ?>)</i></span></p>
                                                        <?php else: ?>
                                                            <p><b><?php echo $operation->title; ?></b> - <span><i><?php echo $operation->operation_date; ?></i></span></p>
                                                        <?php endif; ?>


                                                        <?php if (!empty($operation->description)): ?>
                                                            <p><?php echo $operation->description; ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="delete_prop_operation" data-id="<?php echo $operation->id; ?>">&#10006;</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                    <!-- /post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Actions -->
                        <div class="postbox">
                            <div class="postbox-header"><h2>Actions</h2></div>
                            <div class="inside">
                                <input type="submit" name="edit_property" class="button button-primary button-large" value="Save Changes" />
                            </div>
                        </div>
                        <!-- Featured Image -->
                        <div id="postimagediv" class="postbox">
                            <div class="postbox-header"><h2>Featured Image</h2></div>
                            <div class="inside">
                                <img id="property_image_preview" src="<?php echo esc_attr($property->getThumbnail()); ?>" />
                                <p class="hide-if-no-js">
                                    <a id="upload_image_button">Set the featured image</a>
                                    <input type="hidden" name="property_image" id="property_image" value="<?php echo esc_attr($property->getThumbnail()); ?>" />
                                </p>
                            </div>
                        </div>
                        <!-- Gallery -->
                        <div id="postgallerydiv" class="postbox">
                            <div class="postbox-header">
                                <h2>Image Gallery</h2>
                            </div>
                            <div class="inside">
                                <div id="gallery_preview" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;">
                                    <?php
                                    $gallery_json = $property->getGallery(); // Esto es un string (ej: '["url1","url2"]')
                                    $gallery_images = $gallery_json ? json_decode($gallery_json, true) : [];
                                    if (!empty($gallery_images)) {
                                        foreach ($gallery_images as $img_url) {
                                            echo '<div class="gallery-item" style="position:relative;">
                                                <span class="remove-image">&times;</span>
                                                <img src="' . esc_url($img_url) . '" style="width:80px;height:auto;border-radius:4px;" />
                                            </div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <br>
                                <p class="hide-if-no-js">
                                    <a id="upload_gallery_button" style="text-decoration:underline;cursor:pointer;">Add images to the gallery</a>
                                </p>
                                <input type="hidden" name="property_gallery" id="property_gallery" value="<?php echo esc_attr(json_encode($gallery_images)); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /post-body -->
                <br class="clear" />
            </div>
        </form>
    </div>
<?php
}