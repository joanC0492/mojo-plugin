<?php

require_once __DIR__ . '../../../../services/CalendarService.php';
require_once __DIR__ . '../../../../services/PropertyService.php';

// Callback para la subpágina de creación
function properties_create_page()
{
    $property_service = new PropertyService();

    $table = 'cs_properties';

    $nextYear = intval(date('Y')) + 0;

    if (isset($_POST['create_property'])) {
        $result = $property_service->handleCreateProperty($_POST);

        if (!$result['success']) {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<script>window.location.href="' . $result['redirect'] . '";</script>';
            exit;
        }
    }

?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Add New Property</h1>
        <form method="post" action="">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Campo Name (como título) -->
                        <div id="titlediv">
                            <div id="titlewrap">
                                <?php if (isset($_POST['name'])): ?>
                                    <input type="text" name="name" id="title" placeholder="Enter Property Name here" value="<?php echo $_POST['name']; ?>" required />
                                <?php else: ?>
                                    <input type="text" name="name" id="title" placeholder="Enter Property Name here" required />
                                <?php endif; ?>
                            </div>
                            <div class="inside">
                                <div id="edit-slug-box" class="hide-if-no-js"></div>
                            </div>
                        </div>
                        <br>
                        <!-- Editor WYSIWYG -->
                        <div class="postarea wp-editor-expand">
                            <!-- <h2 class="hndle"><span>Descripción de la propiedad</span></h2> -->
                            <div class="inside">
                                <?php
                                $content = isset($_POST['property_description']) && !empty($_POST['property_description']) ? $_POST['property_description'] : '';
                                wp_editor(
                                    $content,
                                    'property_description', // ID del campo
                                    [
                                        'textarea_name' => 'property_description',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'teeny' => false,
                                    ]
                                );
                                ?>
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
                                    <tr>
                                        <th><label for="share_qty">Share Quantity <span class="required">*</span></label></th>
                                        <td>
                                            <?php if (isset($_POST['share_qty'])): ?>
                                                <select name="share_qty" id="share_qty" class="regular-text" required>
                                                    <option value="5" <?php echo $_POST['share_qty'] == '5' ? 'selected' : ''; ?>>5</option>
                                                    <option value="8" <?php echo $_POST['share_qty'] == '8' ? 'selected' : ''; ?>>8</option>
                                                </select>
                                            <?php else: ?>
                                                <select name="share_qty" id="share_qty" class="regular-text" required>
                                                    <option value="5">5</option>
                                                    <option value="8">8</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="code">Ref. <span class="required">*</span></label></th>
                                        <?php if (isset($_POST['code'])): ?>
                                            <td><input type="text" name="code" id="code" class="regular-text" value="<?php echo $_POST['code']; ?>" required /></td>
                                        <?php else: ?>
                                            <td><input type="text" name="code" id="code" class="regular-text" required /></td>
                                        <?php endif; ?>
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
                                <input type="submit" name="create_property" class="button button-primary button-large" value="Create Property" />
                            </div>
                        </div>
                        <div id="postimagediv" class="postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">Featured Image</h2>
                            </div>
                            <div class="inside">
                                <img id="property_image_preview" src="" style="display:none;margin-top:6px;" />
                                <p class="hide-if-no-js">
                                    <a id="upload_image_button" style="text-decoration:underline;cursor:pointer;">Set the featured image</a>
                                    <input type="hidden" id="property_image" name="property_image" value="">
                                </p>
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
