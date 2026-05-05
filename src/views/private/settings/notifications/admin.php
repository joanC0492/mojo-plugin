<?php

require_once __DIR__ . '../../../../../services/TemplateService.php';

function admin_notifications_system_page()
{
    $template_service = new TemplateService();

    if (isset($_POST['save_template'])) {

        $id = sanitize_text_field($_POST['id_template']);
        $template_name = sanitize_text_field($_POST['template_name']);

        $email_enabled = isset($_POST['email_enabled']) ? 1 : 0;
        $subject = sanitize_text_field($_POST['subject']);
        $body = wp_kses_post($_POST["body_$id"]);

        $push_enabled = isset($_POST['push_enabled']) ? 1 : 0;
        $message = sanitize_text_field($_POST['message']);


        /* --------------------------------------------------------------------------------- */
        $email_empty_validation = false;
        if ((int) $email_enabled === 1) {
            $subject_ok = trim($subject) !== '';
            $body_ok = trim(wp_strip_all_tags((string) $body)) !== '';

            if (!$subject_ok || !$body_ok) {
                $email_empty_validation = true;
            }
        }

        $push_empty_validation = false;
        if ((int) $push_enabled === 1) {
            $message_ok = trim($message) !== '';

            if (!$message_ok) {
                $push_empty_validation = true;
            }
        }

        if ($push_empty_validation || $email_empty_validation) {
            echo '<div class="notice notice-error is-dismissible"><p>Make sure you dont leave any fields empty in "' . esc_html($template_name) . '"</p></div>';
        } else {
            $updated = $template_service->updateNotification($id, $subject, $body, $email_enabled, $message, $push_enabled);

            if ($updated) {
                echo '<div class="notice notice-success is-dismissible"><p>Notifications Templates Updated Successfully.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>There was an error updating.</p></div>';
            }
        }
    }

    $templates = ['When the calendar opens', 'When turn starts', 'When turn is lost', 'When calendar close', 'For Rent', 'For Send Credentials', 'For Exchange Dates'];
?>

    <style>
        .custom_form_6 .form-table tr:first-child th,
        .custom_form_6 .form-table tr:last-child {
            display: none !important;
        }

        .custom_form_1,
        .custom_form_2,
        .custom_form_3,
        .custom_form_4,
        .custom_form_6 {
            display: none !important;
        }
    </style>

    <div class="wrap">
        <h1 class="wp-heading-inline">Notifications</h1>
        <hr class="wp-header-end">
        <h2 class="screen-reader-text">Notifications</h2>
        <br>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <?php foreach ($templates as $index => $template): ?>
                        <?php
                        $index = ($index + 1);
                        $template_row = $template_service->getNotifications($index);
                        ?>
                        <form method="post" action="" class="custom_form_<?php echo $index; ?>">
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2><?php echo $template; ?></h2>
                                </div>
                                <div class="inside">
                                    <input type="text" style="display:none;" name="template_name" class="regular-text" value="<?php echo $template; ?>">
                                    <input type="text" style="display:none;" name="id_template" class="regular-text" value="<?php echo $index; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th>
                                                <label>
                                                    <input type="checkbox" name="email_enabled" id="email_enabled" <?php echo $template_row->email_enabled ? 'checked' : ''; ?>>
                                                    Email
                                                </label>
                                            </th>
                                            <td>
                                                <input type="text" name="subject" id="subject" class="regular-text" style="width:100%;display:block;margin-bottom:20px;" placeholder="Subject" value="<?php echo $template_row->subject; ?>">
                                                <?php
                                                wp_editor(
                                                    $template_row->body ?? '', // contenido inicial (vacío)
                                                    "body_$index", // ID del campo
                                                    [
                                                        'textarea_name' => "body_$index",
                                                        'media_buttons' => true,
                                                        'textarea_rows' => 10,
                                                        'teeny' => false,
                                                        'tinymce' => true,
                                                    ]
                                                );
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label>
                                                    <input type="checkbox" name="push_enabled" id="push_enabled" <?php echo $template_row->push_enabled ? 'checked' : ''; ?>>
                                                    Push
                                                </label>
                                            </th>
                                            <td>
                                                <input type="text" name="message" id="message" class="regular-text" maxlength="255" style="width:100%" value="<?php echo $template_row->message; ?>">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="inside">
                                    <input type="submit" style="display:block;margin-left:auto;" name="save_template" class="button button-primary button-large" value="Save Template" />
                                </div>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- /post-body -->
            <br class="clear" />
        </div>
    </div>
<?php
}
