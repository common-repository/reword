<?php
/*
 * ReWord Help page.
 *
 * Included from reword_admin_help_page() (at Reword_Plugin class)
 *
 * Uses:
 * $enable_update_button - reword plugin version update status
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">ReWord Help</h1>
    <hr class="wp-header-end">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">ReWord version</th>
                <td>
                    <p class="description">
                        <?php
                        echo get_option('reword_plugin_version');
                        if (current_user_can('update_plugins')) {
                            echo '&nbsp;';
                            if (true === $enable_update_button) {
                        ?>
                                <a class="button button-small" href="<?php echo admin_url('plugins.php') ?>#reword-update" target="_parent">Update Available</a>
                            <?php
                            } else {
                            ?>
                                <a class="button button-small" disabled>Latest Version Installed</a>
                        <?php
                            }
                        }
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">Support</th>
                <td>
                    <fieldset>
                        <p class="description">
                            For questions, bugs, issues and feedbacks - please visit <a href="https://wordpress.org/support/plugin/reword" target="_blank">ReWord Support Forum</a>
                        </p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Contact</th>
                <td>
                    <fieldset>
                        <p class="description">
                            Email us to <a href="mailto:giladti@gmail.com">giladti@gmail.com</a>
                        </p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Contribute</th>
                <td>
                    <fieldset>
                        <p class="description">
                            Support our development with a <a href="https://www.paypal.me/TiomKing" target="_blank">donation</a>
                        </p>
                    </fieldset>
                </td>
            </tr>
        </tbody>
    </table>
</div>