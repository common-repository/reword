<?php
/*
 * ReWord Settings page.
 *
 * Included from reword_admin_settings_page() (at Reword_Plugin class)
 *
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">ReWord Settings</h1>
    <hr class="wp-header-end">
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Show reports after</th>
                    <?php $reword_reports_min = get_option('reword_reports_min') ?>
                    <td><input name="reword_reports_min" type="number" min="1" value="<?php echo $reword_reports_min ?>" class="small-text" onchange="rewordOnSettingChange()"><?php echo ($reword_reports_min > 1 ? ' Alerts' : ' Alert') ?></td>
                </tr>
                <tr>
                    <th scope="row">ReWord Icon Position</th>
                    <td>
                        <table>
                            <tbody>
                                <tr>
                                    <td>
                                        <input name="reword_icon_pos" type="radio" value="reword-icon-top reword-icon-left" <?php echo (get_option('reword_icon_pos') === 'reword-icon-top reword-icon-left' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        Top-Left
                                    </td>
                                    <td>
                                        <input name="reword_icon_pos" type="radio" value="reword-icon-top reword-icon-right" <?php echo (get_option('reword_icon_pos') === 'reword-icon-top reword-icon-right' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        Top-Right
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input name="reword_icon_pos" type="radio" value="reword-icon-bottom reword-icon-left" <?php echo (get_option('reword_icon_pos') === 'reword-icon-bottom reword-icon-left' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        bottom-Left
                                    </td>
                                    <td>
                                        <input name="reword_icon_pos" type="radio" value="reword-icon-bottom reword-icon-right" <?php echo (get_option('reword_icon_pos') === 'reword-icon-bottom reword-icon-right' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        bottom-Right
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th scope="row">ReWord Banner</th>
                    <td>
                        <fieldset>
                            <label>
                                <input id="banner-enable" name="reword_notice_banner" type="checkbox" value="true" <?php echo (get_option('reword_notice_banner') === 'true' ? 'checked' : ''); ?> onchange="rewordShowHideBannerPositionSettings(); rewordOnSettingChange()">
                                Show ReWord notice banner
                            </label>
                            <p class="description">
                                ReWord banner lets users know they can report mistakes.
                            </p>
                        </fieldset>
                    </td>
                </tr>
                <tr id="banner-pos" style="display: none;">
                    <th scope="row">ReWord Banner Position</th>
                    <td>
                        <table>
                            <tbody>
                                <tr>
                                    <td>
                                        <input name="reword_banner_pos" type="radio" value="bottom" <?php echo (get_option('reword_banner_pos') === 'bottom' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        Banner bottom
                                    </td>
                                    <td>
                                        <input name="reword_banner_pos" type="radio" value="top" <?php echo (get_option('reword_banner_pos') === 'top' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        Banner top
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input name="reword_banner_pos" type="radio" value="bottom-left" <?php echo (get_option('reword_banner_pos') === 'bottom-left' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        Floating left
                                    </td>
                                    <td>
                                        <input name="reword_banner_pos" type="radio" value="bottom-right" <?php echo (get_option('reword_banner_pos') === 'bottom-right' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                        Floating right
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Email reports</th>
                    <td>
                        <?php
                        $emails_arr = get_option('reword_email_new');
                        if (!empty($emails_arr)) { ?>
                            <p class="description">
                                Current mailing list (check to remove):
                            </p>
                            <br />
                            <?php
                            foreach ($emails_arr as $emails_address) {
                            ?>
                                <input name="reword_email_remove[]" type="checkbox" value="<?php echo $emails_address ?>" onchange="rewordOnSettingChange()">
                                <?php echo $emails_address ?>
                                <br /><br />
                            <?php
                            }
                            ?>
                        <?php
                        } else {
                        ?>
                            <p class="description">
                                No email addresses configured yet.
                            </p>
                        <?php
                        }
                        ?>
                        <br />
                        <p class="description">
                            Add emails to notify on new reports
                            <span class="dashicons dashicons-insert" style="vertical-align: middle; cursor: pointer;" onclick="rewordAddEmailText()"></span>
                        </p>
                        <br />
                        <div id="reword_email_add_list">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Reports access level</th>
                    <td>
                        <fieldset>
                            <label>
                                <select id="reword_access_cap" name="reword_access_cap" onchange="rewordOnSettingChange()">
                                    <?php $reword_access_cap = get_option('reword_access_cap') ?>
                                    <option value="manage_options" <?php echo ($reword_access_cap === 'manage_options' ? 'selected' : ''); ?>>Administrator</option>
                                    <option value="edit_others_posts" <?php echo ($reword_access_cap === 'edit_others_posts' ? 'selected' : ''); ?>>Editor</option>
                                    <option value="edit_published_posts" <?php echo ($reword_access_cap === 'edit_published_posts' ? 'selected' : ''); ?>>Author</option>
                                    <option value="edit_posts" <?php echo ($reword_access_cap === 'edit_posts' ? 'selected' : ''); ?>>Contributor</option>
                                </select>
                            </label>
                            <p class="description">
                                Users with this role can access ReWord reports page.
                            </p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Send statistics</th>
                    <td>
                        <fieldset>
                            <label>
                                <input name="reword_send_stats" type="checkbox" value="true" <?php echo (get_option('reword_send_stats') === 'true' ? 'checked' : ''); ?> onchange="rewordOnSettingChange()">
                                Help ReWord and send usage statistics
                            </label>
                            <p class="description">
                                We do not send any personal or sensitive information about you, your site or your users.
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />
        <input type="hidden" name="reword_settings_nonce" value="<?php echo wp_create_nonce('reword_settings_nonce') ?>" />
        <?php
        // Add the submit button
        $reword_submit_other_attributes = 'disabled';
        submit_button('Save Changes', 'primary', 'reword_submit', false, $reword_submit_other_attributes);
        ?>
        &nbsp;
        <?php
        // Add the default button
        $reword_default_other_attributes = 'onclick="rewordOnRestoreDefault()"';
        submit_button('Restore Defaults', 'secondary', 'reword_default', false, $reword_default_other_attributes);
        ?>
    </form>
</div>