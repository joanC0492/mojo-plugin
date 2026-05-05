<?php

require_once __DIR__ . '../../../../services/OwnerService.php';

// Callback para la subpágina de creación
function owners_create_page()
{

    $owner_service = new OwnerService();

    // cs_log('📄 Loading page: owners_create_page()');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_owner'])) {
        $owner_service = new OwnerService();
        $result = $owner_service->handleCreateOwner($_POST);

        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Add New Owner</h1>
    <form method="post" action="">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Campo Name (como título) -->
                    <div id="titlediv">
                        <div id="titlewrap">
                            <input type="text" name="name" id="title" placeholder="Enter Owner Name here" required />
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
                                    <td><input type="email" name="email" id="email" class="regular-text" required /></td>
                                </tr>
                                <tr>
                                    <th><label for="password">Password <span class="required">*</span></label></th>
                                    <td><input type="text" name="password" id="password" class="regular-text" required /></td>
                                </tr>
                                <tr>
                                    <th><label for="phone">Phone</label></th>
                                    <td><input type="text" name="phone" id="phone" class="regular-text" /></td>
                                </tr>
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
                        <div class="inside">
                            <br>
                            <input type="submit" name="create_owner" class="button button-primary button-large" value="Create Owner" />
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
