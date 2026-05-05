<?php

require_once __DIR__ . '../../../../services/SettingService.php';
require_once __DIR__ . '../../../../services/PropertyService.php';
require_once __DIR__ . '../../../../services/CalendarService.php';

function contacts_system_page()
{
    $setting_service = new SettingService();
    $property_service = new PropertyService();
    $calendar_service = new CalendarService();

    $id = 1;

    if (isset($_POST['update_contacts'])) {

        // Contact For Administration
        $admin_name = sanitize_text_field($_POST['admin_name']);
        $admin_email = sanitize_text_field($_POST['admin_email']);
        $admin_phone = sanitize_text_field($_POST['admin_phone']);

        // Contact For Sale
        $sale_name = sanitize_text_field($_POST['sale_name']);
        $sale_email = sanitize_text_field($_POST['sale_email']);
        $sale_phone = sanitize_text_field($_POST['sale_phone']);

        // Setting
        $request_email = sanitize_text_field($_POST['request_email']);
        $rent_email = sanitize_text_field($_POST['rent_email']);
        $exchange_email = sanitize_text_field($_POST['exchange_email']);
        $contact_us_email = sanitize_text_field($_POST['contact_us_email']);

        // Email Footer Information
        $facebook = sanitize_text_field($_POST['facebook']);
        $instagram = sanitize_text_field($_POST['instagram']);
        $linkedin = sanitize_text_field($_POST['linkedin']);
        $youtube = sanitize_text_field($_POST['youtube']);

        $phone_footer = sanitize_text_field($_POST['phone_footer']);
        $mail_footer = sanitize_text_field($_POST['mail_footer']);
        $direction_footer = sanitize_text_field($_POST['direction_footer']);

        $bgcolor_pdf = sanitize_text_field($_POST['bgcolor_pdf']);

        $updated = $setting_service->updateSetting($id, $admin_name, $admin_email, $admin_phone, $sale_name, $sale_email, $sale_phone, $request_email, $rent_email, $exchange_email, $contact_us_email, $facebook, $instagram, $linkedin, $youtube, $phone_footer, $mail_footer, $direction_footer, $bgcolor_pdf);

        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Settings updated successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>There was an error updating.</p></div>';
        }
    }

    if (isset($_POST['new_calendar_year'])) {
        $year = sanitize_text_field($_POST['year']);

        $calendars_created = 0;
        $active_properties = $property_service->getAllProperties(1);

        if (!empty($active_properties)) {
            foreach ($active_properties as $property) {
                $id_property = $property['id'];

                $calendar = $calendar_service->getCalendarByProperty($id_property, $year);
                if (!$calendar) {
                    $create_calendars = $calendar_service->createCalendar($id_property, $year, 'close');
                    if ($create_calendars) {
                        $calendars_created++;
                    }
                }
            }
        }

        // ✅ Mostrar mensaje de resultado
        if ($calendars_created > 0) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . sprintf('%d calendar(s) created successfully for the year %s.', $calendars_created, esc_html($year))
                . '</p></div>';
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>'
                . sprintf('No new calendars were created for %s — they already exist for all active properties.', esc_html($year))
                . '</p></div>';
        }
    }

    $contacts = $setting_service->getContacts($id);

    if (!$contacts) {
        echo '<div class="notice notice-error"><p>Contact information not found.</p></div>';
        return;
    }

?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Contact Information</h1>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Contact Information</h2>
        <br>
        <form method="post" action="">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Metabox con campos -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Contact For Administration</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="admin_name">Name</label></th>
                                        <td><input type="text" name="admin_name" id="admin_name" class="regular-text" value="<?php echo $contacts->admin_name; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="admin_email">Email</label></th>
                                        <td><input type="text" name="admin_email" id="admin_email" class="regular-text" value="<?php echo $contacts->admin_email; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="admin_phone">Phone</label></th>
                                        <td><input type="text" name="admin_phone" id="admin_phone" class="regular-text" value="<?php echo $contacts->admin_phone; ?>" /></td>
                                    </tr>
                                </table>
                            </div>
                            <hr>
                        </div>
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Contact For Sale</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="sale_name">Name</label></th>
                                        <td><input type="text" name="sale_name" id="sale_name" class="regular-text" value="<?php echo $contacts->sale_name; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="sale_email">Email</label></th>
                                        <td><input type="text" name="sale_email" id="sale_email" class="regular-text" value="<?php echo $contacts->sale_email; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="sale_phone">Phone</label></th>
                                        <td><input type="text" name="sale_phone" id="sale_phone" class="regular-text" value="<?php echo $contacts->sale_phone; ?>" /></td>
                                    </tr>
                                </table>
                            </div>
                            <hr>
                        </div>
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="request_email">Request For Quote - Email</label></th>
                                        <td><input type="text" name="request_email" id="request_email" class="regular-text" value="<?php echo $contacts->request_email; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="rent_email">Rent - Email</label></th>
                                        <td><input type="text" name="rent_email" id="rent_email" class="regular-text" value="<?php echo $contacts->rent_email; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="exchange_email">Exchange Dates - Email</label></th>
                                        <td><input type="text" name="exchange_email" id="exchange_email" class="regular-text" value="<?php echo $contacts->exchange_email; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="contact_us_email">Contact Us - Email</label></th>
                                        <td><input type="text" name="contact_us_email" id="contact_us_email" class="regular-text" value="<?php echo $contacts->contact_us_email; ?>" /></td>
                                    </tr>
                                </table>
                            </div>
                            <hr>
                        </div>
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Email Footer Information</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="facebook">Facebook</label></th>
                                        <td><input type="text" name="facebook" id="facebook" class="regular-text" value="<?php echo $contacts->facebook; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="instagram">Instagram</label></th>
                                        <td><input type="text" name="instagram" id="instagram" class="regular-text" value="<?php echo $contacts->instagram; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="linkedin">LinkedIn</label></th>
                                        <td><input type="text" name="linkedin" id="linkedin" class="regular-text" value="<?php echo $contacts->linkedin; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="youtube">YouTube</label></th>
                                        <td><input type="text" name="youtube" id="youtube" class="regular-text" value="<?php echo $contacts->youtube; ?>" /></td>
                                    </tr>
                                </table>
                                <hr>
                                <table class="form-table">
                                    <tr>
                                        <th><label for="phone_footer">Phone</label></th>
                                        <td><input type="text" name="phone_footer" id="phone_footer" class="regular-text" value="<?php echo $contacts->phone_footer; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="mail_footer">Email</label></th>
                                        <td><input type="text" name="mail_footer" id="mail_footer" class="regular-text" value="<?php echo $contacts->mail_footer; ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th><label for="direction_footer">Direction</label></th>
                                        <td><input type="text" name="direction_footer" id="direction_footer" class="regular-text" value="<?php echo $contacts->direction_footer; ?>" /></td>
                                    </tr>
                                </table>
                            </div>
                            <hr>
                        </div>
                        <div class="postbox" style="margin: 0;">
                            <div class="postbox-header">
                                <h2>PDF</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="bgcolor_pdf">Selected Color Dates</label></th>
                                        <td><input type="color" name="bgcolor_pdf" id="bgcolor_pdf" value="<?php echo $contacts->bgcolor_pdf; ?>" /></td>
                                    </tr>
                                </table>
                            </div>
                            <hr>
                        </div>
                    </div>
                    <!-- /post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Actions</h2>
                            </div>
                            <div class="inside">
                                <br>
                                <input type="submit" name="update_contacts" class="button button-primary button-large" value="Save Changes" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <form method="post" action="">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Generate Calendars</h2>
                            </div>
                            <div class="inside">

                                <table class="form-table">
                                    <tr>
                                        <th>
                                            <label for="admin_name">Year</label>
                                        </th>
                                        <td>
                                            <select name="year" id="year" class="regular-text">
                                                <?php
                                                $currentYear = date('Y');
                                                $startYear = $currentYear + 1; // Año siguiente
                                                $endYear = $startYear + 4;     // 4 años posteriores

                                                for ($year = $startYear; $year <= $endYear; $year++) {
                                                    echo '<option value="' . $year . '">' . $year . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <input type="submit" name="new_calendar_year" class="button button-primary button-large" value="Create">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <hr>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php
}
