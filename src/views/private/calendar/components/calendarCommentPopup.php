<div class="popup" id="commenting">
    <div class="popup_bg close_popup"></div>
    <div class="popup_content postbox">
        <div class="postbox-header">
            <h2>Add a Comment</h2>
        </div>
        <div class="inside">
            <div class="popup_content-form">
                <form method="post" action="">
                    <table class="form-table">
                        <input type="hidden" name="calendar_id" value="<?php echo $id_calendar; ?>">
                        <tr>
                            <th style="vertical-align: middle">
                                <label>Date</label>
                            </th>
                            <td>
                               <input type="date" id="date" name="date" class="regular-text" value="<?php echo date('Y-m-d'); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="title">Comment</label>
                            </th>
                            <td>
                                <input type="text" name="title" class="regular-text" maxlength="50">
                            </td>
                        </tr>
                         <tr>
                            <th>
                                <label for="description">Description</label>
                            </th>
                            <td>
                                <textarea name="description" class="regular-text" maxlength="200"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: center">
                                <input id="submitComment" class="button button-primary button-large" type="submit" name="add_comment"
                                    value="Add Comment" disabled/>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>