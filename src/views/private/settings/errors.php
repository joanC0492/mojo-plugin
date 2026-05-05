<?php

require_once __DIR__ . '../../../../services/AlertsService.php';

function error_system_page()
{
    $template_service = new AlertsService();
    $id = 1;

    if (isset($_POST['save_alerts'])) {

        $alert = sanitize_text_field($_POST['alert']);
        $updated = $template_service->updateAlerts($id, $alert);

        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Error Alerts Updated Successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>There was an error updating.</p></div>';
        }
    }

    /*$alerts = $template_service->getAlerts($id);
    if (!$alerts) {
        echo '<div class="notice notice-error"><p>Error Alerts not found.</p></div>';
        return;
    }*/
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Error Alerts</h1>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Error Alerts</h2>
        <br>
        <form method="post" action="">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>For Calendar System</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th>
                                            <label for="alert">Alert 1</label>
                                        </th>
                                        <td>
                                            <input required type="text" name="alert" id="alert" class="regular-text" style="width:100%;display:block;" value="">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- /post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Actions</h2>
                            </div>
                            <div class="inside postbox_actions" style="margin-top:12px">
                                <input type="submit" name="save_alerts" class="button button-primary button-large" value="Save Alerts" />
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