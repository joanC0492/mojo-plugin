<?php

require_once __DIR__ . '../../../../services/OwnerService.php';

function owners_admin_edit_page_callback()
{
    $owner_service = new OwnerService();
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$id) {
        echo '<div class="notice notice-error"><p>Invalid ID.</p></div>';
        cs_log('❌ Invalid ID received for Owner edit page');
        return;
    }

    // Si se envió el formulario
    if (isset($_POST['edit_owner'])) {
        $name     = sanitize_text_field($_POST['name']);
        $email    = sanitize_email($_POST['email']);
        $phone    = sanitize_text_field($_POST['phone']);
        $password = sanitize_text_field($_POST['password']);
        $visible_info = isset($_POST['visible_info']) ? 1 : 0;
        $is_active = sanitize_text_field($_POST['is_active']);

        $updated = $owner_service->updateOwner($id, $name, $email, $password, $phone, $visible_info, $is_active);

        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Owner updated successfully.</p></div>';
            cs_log('✅ Owner updated successfully', [
                'id'    => $id,
                'name'  => $name,
                'email' => $email
            ]);
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Error updating Owner.</p></div>';
            cs_log('❌ Failed to update Owner', [
                'id'    => $id,
                'name'  => $name,
                'email' => $email
            ]);
        }
    }

    $owner = $owner_service->getOwner($id);

    if (!$owner) {
        echo '<div class="notice notice-error"><p>Owner not found.</p></div>';
        cs_log('❌ Owner not found', [
            'id' => $id
        ]);
        return;
    }

?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Edit Owner</h1>
        <form method="post" action="">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Campo Name (como título) -->
                        <div id="titlediv">
                            <div id="titlewrap">
                                <input type="text" name="name" id="title" placeholder="Enter Owner Name here" value="<?php echo esc_attr($owner->getName()); ?>" required />
                            </div>
                        </div>
                        <br>
                        <!-- Metabox con campos -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Owner Details</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="email">Email <span class="required">*</span></label></th>
                                        <td><input type="email" name="email" id="email" class="regular-text" value="<?php echo esc_attr($owner->getEmail()); ?>" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="password">Password <span class="required">*</span></label></th>
                                        <td><input type="text" name="password" id="password" class="regular-text" value="<?php echo esc_attr($owner->getPassword()); ?>" required /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="phone">Phone</label></th>
                                        <td><input type="text" name="phone" id="phone" class="regular-text" value="<?php echo esc_attr($owner->getPhone()); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="visible_info">Contact Information Visible?</label></th>
                                        <td>
                                            <input type="checkbox" name="visible_info" id="visible_info" class="regular-text" <?php echo $owner->getVisibleInfo() ? 'checked' : ''; ?> />
                                            <small class="howto" style="margin-top:4px"><i>Email & Phone</i></small>
                                        </td>
                                    </tr>
                                    <input type="hidden" name="is_active" id="is_active" value="<?php echo esc_attr($owner->getStatus()); ?>">
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- /post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle">Actions</h2>
                            </div>
                            <div class="inside" style="margin-top:12px">
                                <input type="submit" name="edit_owner" class="button button-primary button-large" value="Save Changes" />
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
