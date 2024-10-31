<?php

/**
 * Class Reword Plugin
 *
 * Main plugin class.
 */
class Reword_Plugin
{

    /**
     * Reword plugin init function.
     *
     * Registers hooks and actions.
     */
    public function reword_init()
    {
        register_activation_hook(REWORD_PLUGIN_BASENAME, array($this, 'reword_activate'));
        register_uninstall_hook(REWORD_PLUGIN_BASENAME, array('Reword_Plugin', 'reword_uninstall'));
        add_filter('plugin_action_links', array($this, 'reword_plugin_action_links'), 10, 2);
        add_action('admin_menu', array($this, 'reword_admin_menus'));
        add_action('wp_enqueue_scripts', array($this, 'reword_add_public_scripts'));
        add_action('plugins_loaded', array($this, 'reword_update_check'));
        add_action('wp_ajax_reword_send_mistake', array($this, 'reword_send_mistake'));
        add_action('wp_ajax_nopriv_reword_send_mistake', array($this, 'reword_send_mistake'));
    }

    /**
     * Reword database create or update.
     *
     * This function creates reword data table if does not exist, or update db schema if needed.
     * Database table is deleted only when reword plugin is removed, but not when deactivated,
     * to keep reports and settings data.
     *
     * Reword table data parameters:
     *  report_id           - Report ID (primary key)
     *  mistake             - Mistake reported by users
     *  user_fix            - Suggested fix sent by users
     *  admin_fix           - Fix set by admin
     *  time                - Report time or admin fix set time
     *  status              - Report status (key)
     *  full_text           - Mistake HTML element full text
     *  site_info           - Report URL
     *  reports_count       - Number of reports on a specific mistake
     *
     * @global Object $wpdb
     */
    private function reword_create_db()
    {
        global $wpdb;
        // SQL query to create reword table
        $sql = "CREATE TABLE " . REWORD_DB_NAME . " (
                    report_id int(25) NOT NULL AUTO_INCREMENT,
                    mistake text NOT NULL,
                    user_fix text NOT NULL,
                    time int(25) NOT NULL,
                    status enum('new','ignore') NOT NULL DEFAULT 'new',
                    full_text text NOT NULL,
                    site_info text NOT NULL,
                    reports_count int(25) NOT NULL DEFAULT '1',
                    PRIMARY KEY  (`report_id`),
                    KEY `status` (`status`)
                ) ENGINE=MyISAM " . $wpdb->get_charset_collate() . " COMMENT='Reword mistakes reports' AUTO_INCREMENT=1;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $db_changes = dbDelta($sql);

        if ($wpdb->last_error) {
            // log error, deactivate and die
            $this->reword_log(REWORD_ERR, $wpdb->last_error);
            $this->reword_deactivate();
            $this->reword_die('Reword failed to create DB');
        } else if ($db_changes) {
            // DB was created or updated
            foreach ($db_changes as $db_change) {
                $this->reword_log(REWORD_NOTICE, $db_change);
            }
        }
    }

    /**
     * Reword option set.
     *
     * Creates or reset reword setting to default.
     *
     * @global type $reword_options
     * @param string $action - reset or create (default)
     */
    private function reword_options_set($action = 'create')
    {
        global $reword_options;
        // Set option create or update function
        $option_func = (('create' === $action) ? 'add_option' :  'update_option');
        // Set options
        foreach ($reword_options as $option => $value) {
            $option_func($option, $value);
        }
    }

    /**
     * Activation hook
     */
    public function reword_activate()
    {
        $this->reword_create_db();
        $this->reword_options_set();
    }

    /**
     * Upgrade plugin DB and options hook
     */
    public function reword_update_check()
    {
        if ((is_admin()) && (REWORD_PLUGIN_VERSION !== get_option('reword_plugin_version'))) {
            $this->reword_log(REWORD_NOTICE, 'Upgrading plugin version from ' . get_option('reword_plugin_version') . ' to ' . REWORD_PLUGIN_VERSION);
            // Update version setting
            update_option('reword_plugin_version', REWORD_PLUGIN_VERSION);
            // Rerun plugin activation for updates
            $this->reword_activate();
        }
    }

    /**
     * Delete reword database
     *
     * Called by static function reword_uninstall()
     *
     * @global Object $wpdb
     */
    private static function reword_delete_db()
    {
        global $wpdb;
        // SQL query to delete reword table
        $sql = "DROP TABLE IF EXISTS " . REWORD_DB_NAME;
        $wpdb->query($sql);
    }

    /**
     * Delete reword options
     *
     * @global type $reword_options
     */
    private static function reword_options_delete()
    {
        global $reword_options;
        foreach (array_keys($reword_options) as $option) {
            delete_option($option);
        }
    }

    /**
     * Uninstall hook
     */
    public static function reword_uninstall()
    {
        // Send stats

        // Delete
        self::reword_delete_db();
        self::reword_options_delete();
    }

    /**
     * Plugins action links filter callback.
     *
     * Adds setting link to reword row in admin plugins page.
     *
     * @param array $links
     * @param string $file
     * @return array $links
     */
    public function reword_plugin_action_links($links, $file)
    {
        // Check if called for reword plugin
        if (REWORD_PLUGIN_BASENAME === $file) {
            // Push setting page link to links array
            array_unshift($links, '<a href="' . menu_page_url('reword-settings', false) . '">Settings</a>');
        }
        return ($links);
    }

    /**
     * Admin main menu callback.
     *
     * Displays reword reports Center page.
     */
    public function reword_admin_reports_page()
    {
        // Check user capabilities
        if (!current_user_can(get_option('reword_access_cap'))) {
            return;
        }
        // Handle submitted actions
        $action_notice = $this->reword_handle_report_action();
        if ($action_notice) {
            // Setting change successfully
            $this->reword_wp_notice($action_notice);
        }
        // Get table type tab
        $active_tab = $this->reword_fetch_data('active_tab');
        // Reword table types - new or ignore.
        if (empty($active_tab)) {
            // Default tab
            $active_tab = 'new';
        } else if (('new' !== $active_tab) && ('ignore' !== $active_tab)) {
            $this->reword_log(REWORD_ERR, 'Invalid reports tab:[' . $active_tab . '], setting to default:[new]');
            $active_tab = 'new';
        }
        // Reword list table class
        if (!class_exists('Reword_List_Table')) {
            require_once(REWORD_CLASS_DIR . '/class-reword-reports-table.php');
        }
        $reword_reports_table = new Reword_Reports_Table($active_tab);
        $reword_reports_table->prepare_items();
        // Page used parameters
        $reword_new_reports_count = $this->reword_get_reports_count('new');
        $reword_ignore_reports_count = $this->reword_get_reports_count('ignore');
        $reword_show_delete_all = ($reword_new_reports_count + $reword_ignore_reports_count > 0 ? true : false);
        // Show page
        include(REWORD_ADMIN_DIR . '/php/reword-reports.php');
    }

    /**
     * Admin sub main menu callback.
     *
     * Displays reword settings page.
     */
    public function reword_admin_settings_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        // Handle submitted settings changes
        $setting_change_notice = $this->reword_handle_settings_change();
        if ($setting_change_notice) {
            // Setting change successfully
            $this->reword_wp_notice($setting_change_notice);
        }
        // Show settings page
        include(REWORD_ADMIN_DIR . '/php/reword-settings.php');
    }

    /**
     * Admin sub main menu callback.
     *
     * Displays reword help page.
     */
    public function reword_admin_help_page()
    {
        // Check user capabilities
        if (!current_user_can(get_option('reword_access_cap'))) {
            return;
        }
        // Force plugin update status refresh
        wp_update_plugins();
        // Get reword plugin update status
        $update_plugins = get_plugin_updates();
        $enable_update_button = (!empty($update_plugins) && (isset($update_plugins[REWORD_PLUGIN_BASENAME])));
        // Show page
        include(REWORD_ADMIN_DIR . '/php/reword-help.php');
    }

    /**
     * Admin menu action callback.
     */
    public function reword_admin_menus()
    {
        // Main menu - reports center
        $new_report_count = $this->reword_get_reports_count('new');
        $reword_reports_menu_page = add_menu_page(
            null,
            'ReWord <span class="awaiting-mod count-' . absint($new_report_count) . '"><span class="pending-count">' . number_format_i18n($new_report_count) . '</span></span>',
            get_option('reword_access_cap'),
            'reword-reports',
            null,
            REWORD_PLUGIN_URL . '/reword-icon.png',
            65
        );
        // Register scripts function callback for reports page
        add_action('admin_print_scripts-' . $reword_reports_menu_page, array($this, 'reword_add_reports_page_scripts'));
        // Reports sub menu page
        add_submenu_page(
            'reword-reports',
            'ReWord Reports',
            'Reports',
            get_option('reword_access_cap'),
            'reword-reports',
            array($this, 'reword_admin_reports_page')
        );
        // Settings sub menu page
        $reword_settings_menu_page = add_submenu_page(
            'reword-reports',
            'ReWord Settings',
            'Settings',
            'manage_options',
            'reword-settings',
            array($this, 'reword_admin_settings_page')
        );
        // Register scripts function callback for settings page
        add_action('admin_print_scripts-' . $reword_settings_menu_page, array($this, 'reword_add_settings_page_scripts'));

        // Help sub menu page
        add_submenu_page(
            'reword-reports',
            'ReWord Help',
            'Help',
            get_option('reword_access_cap'),
            'reword-help',
            array($this, 'reword_admin_help_page')
        );
    }

    /**
     * Reports page scripts enqueue
     *
     * Adds JavaScript to admin Reports Center page.
     * Script gets the following parameters:
     *  rewordAdminPostPath - Path to php file to post reports actions
     *  rewordSendStats     - Send statistics option value (reword_send_stats)
     */
    public function reword_add_reports_page_scripts()
    {
        // Check user role
        if (is_admin()) {
            // Add script to page
            wp_register_script(
                'reword_reports_js',
                REWORD_ADMIN_URL . '/js/reword-reports.js',
                'jquery',
                '1',
                true
            );
            wp_enqueue_script('reword_reports_js');
        }
    }

    /**
     * Settings page scripts enqueue
     *
     * Adds JavaScript to admin settings page.
     */
    public function reword_add_settings_page_scripts()
    {
        // Check user role
        if (is_admin()) {
            // Add script to page
            wp_register_script(
                'reword_settings_js',
                REWORD_ADMIN_URL . '/js/reword-settings.js',
                'jquery',
                '1',
                true
            );
            wp_enqueue_script('reword_settings_js');
        }
    }

    /**
     * Setting submit handle.
     *
     * Handles settings changes via setting page forms.
     *
     * @global type $reword_options
     * @return string - Operation status, null if no setting changes submitted
     */
    private function reword_handle_settings_change()
    {
        global $reword_options;
        $ret_msg = null;
        if ("Save Changes" == $this->reword_fetch_data('reword_submit')) {
            if (false === $this->reword_verify_nonce('reword_settings_nonce')) {
                // Nonce verification failed
                $ret_msg = 'Operation failed. Please try again later...';
            } else {
                // Handle emails removal
                if ($removed_emails = $this->reword_fetch_data('reword_email_remove')) {
                    $this->reword_update_setting(
                        'reword_email_remove',
                        $removed_emails,
                        get_option('reword_email_new')
                    );
                }
                // Handle emails additions
                if ($added_emails = $this->reword_fetch_data('reword_email_add')) {
                    // Check if all addresses are valid emails
                    if (in_array(false, $added_emails)) {
                        // One or more addresses is invalid
                        $ret_msg = ' Some invalid email addresses have been excluded.';
                        // Remove them from array
                        $added_emails = array_filter($added_emails, function ($x) {
                            return !(is_null($x) || $x === false);
                        });
                    }
                    $this->reword_update_setting(
                        'reword_email_add',
                        $added_emails,
                        get_option('reword_email_new')
                    );
                }
                // Update other settings
                foreach (array_keys($reword_options) as $option) {
                    $this->reword_update_setting(
                        $option,
                        $this->reword_fetch_data($option),
                        get_option($option)
                    );
                }
                $ret_msg = 'ReWord settings saved.' . $ret_msg;
            }
        } else if ('Restore Defaults' == $this->reword_fetch_data('reword_default')) {
            if (false === $this->reword_verify_nonce('reword_settings_nonce')) {
                // Nonce verification failed
                $ret_msg = 'Operation failed. Please try again later...';
            } else {
                // Restore Default options
                $this->reword_options_set('reset');
                $ret_msg = 'ReWord settings restored to defaults.';
            }
        }
        return $ret_msg;
    }

    /**
     * Update reword option.
     *
     * Checks if option was set, and update it according to input format.
     *
     * @param string $name - option name
     * @param mixed $set - setting input value
     * @param mixed $conf - setting configured value
     */
    private function reword_update_setting($name, $set, $conf)
    {
        switch ($name) {
            case 'reword_reports_min':
            case 'reword_icon_pos':
            case 'reword_banner_pos':
            case 'reword_access_cap':
                // Number, radio or select
                if ((!empty($set)) && ($set !== $conf)) {
                    update_option($name, $set);
                }
                break;
            case 'reword_notice_banner':
            case 'reword_send_stats':
                // Checkbox true / false
                $set = (empty($set) ? 'false' : 'true');
                if ($set !== $conf) {
                    update_option($name, $set);
                }
                break;
            case 'reword_email_add':
                // Emails to add to array
                if (!empty($set)) {
                    update_option('reword_email_new', array_unique(array_merge($conf, $set)));
                }
                break;
            case 'reword_email_remove':
                // Checkboxes array to remove from array
                if (!empty($set)) {
                    // Update option with new array
                    update_option('reword_email_new', array_diff($conf, $set));
                }
                break;
            case 'reword_email_new':
            case 'reword_plugin_version':
                // These options are not part of settings update
                break;
            default:
                // Programming error
                $this->reword_log(REWORD_ERR, 'Bad setting name:[' . $name . ']');
                break;
        }
    }

    /**
     * Handle reports table actions.
     *
     * Gets reports action data and updates DB.
     *
     * @global Object $wpdb
     * @return String - null is success, notice message otherwise
     */
    private function reword_handle_report_action()
    {
        $ret_msg = null;
        // Get action
        if (($action = $this->reword_fetch_data('action')) && ('-1' != $action)) {
            if (false === $this->reword_verify_nonce('reword_reports_nonce')) {
                // Nonce verification failed
                $ret_msg = 'Operation failed. Please try again later...';
            } else {
                global $wpdb;
                // Handle reports DB flush
                if ('delete_all' === $action) {
                    // Delete all reports
                    $sql = "TRUNCATE " . REWORD_DB_NAME;
                    $wpdb->query($sql);
                    if ($wpdb->last_error) {
                        $this->reword_log(REWORD_ERR, $wpdb->last_error);
                        $ret_msg = 'Database error. Please try again later...';
                    }
                } else if ($reports_ids = $this->reword_fetch_data('id')) {
                    // Handle action on checked reports, or a specific report
                    if ('delete' === $action) {
                        // Delete reports
                        foreach ($reports_ids as $report_id) {
                            $wpdb->delete(REWORD_DB_NAME, array('report_id' => $report_id));
                            if ($wpdb->last_error) {
                                $this->reword_log(REWORD_ERR, $wpdb->last_error);
                                $ret_msg = 'Database error. Please try again later...';
                                break;
                            }
                        }
                    } else if ('ignore' === $action) {
                        // Ignore reports
                        foreach ($reports_ids as $report_id) {
                            $wpdb->update(REWORD_DB_NAME, array('status' => 'ignore'), array('report_id' => $report_id));
                            if ($wpdb->last_error) {
                                $this->reword_log(REWORD_ERR, $wpdb->last_error);
                                $ret_msg = 'Database error. Please try again later...';
                                break;
                            }
                        }
                    } else {
                        $ret_msg = 'Operation failed. Please try again later...';
                        $this->reword_log(REWORD_ERR, 'Illegal action [' . $action . '] received');
                    }
                } else {
                    $ret_msg = 'Operation failed. Please try again later...';
                    $this->reword_log(REWORD_ERR, 'Action [' . $action . '] invalid data');
                }
            }
        }
        return $ret_msg;
    }

    /**
     * Public scripts enqueue.
     *
     * Adds JavaScript to frontend pages.
     * Script receives the following parameters:
     *  rewordIconPos - Icon position option value (reword_icon_pos)
     *  rewordBannerEnabled - Reword banner option value (reword_notice_banner)
     *  rewordPublicPostPath - Path to php file to post report
     *  rewordSendStats - Send statistics option value (reword_send_stats)
     *  rewordMistakeReportNonce - Nonce for secure posting
     *
     * Adds CSS style to frontend pages.
     */
    public function reword_add_public_scripts()
    {
        if (!is_admin()) {
            // Add script to public pages
            wp_register_script(
                'reword_public_js',
                REWORD_PUBLIC_URL . '/js/reword-public.js',
                'jquery',
                '1',
                true
            );
            // Add parameters to script
            $reword_public_data = array(
                'rewordIconPos'            => get_option('reword_icon_pos'),
                'rewordBannerEnabled'      => get_option('reword_notice_banner'),
                'rewordBannerPos'          => get_option('reword_banner_pos'),
                'rewordPublicPostPath'     => admin_url('admin-ajax.php'),
                'rewordSendStats'          => get_option('reword_send_stats'),
                'rewordMistakeReportNonce' => wp_create_nonce('reword_mistake_report_nonce'),
            );
            wp_localize_script('reword_public_js', 'rewordPublicData', $reword_public_data);
            wp_enqueue_script('reword_public_js');
            // Banner script
            wp_register_script(
                'reword_banner_js',
                REWORD_PUBLIC_URL . '/js/reword-banner.js',
                'jquery',
                '1',
                true
            );
            wp_enqueue_script('reword_banner_js');
            // Add style to public pages
            wp_register_style('reword_public_css', REWORD_PUBLIC_URL . '/css/reword-public.css');
            wp_enqueue_style('reword_public_css');
            // Banner style
            wp_register_style('reword_banner_css', REWORD_PUBLIC_URL . '/css/reword-banner.css');
            wp_enqueue_style('reword_banner_css');
        }
    }

    /**
     * Handle reported mistakes.
     *
     * Gets report data from frontend AJAX, and updates DB.
     *
     * @global Object $wpdb
     */
    public function reword_send_mistake()
    {
        global $wpdb;
        // Get mistake data
        $text_selection = $this->reword_fetch_data('text_selection');
        $text_fix = $this->reword_fetch_data('text_fix');
        $full_text = $this->reword_fetch_data('full_text');
        $text_url = $this->reword_fetch_data('text_url');
        $current_time = time();
        // Validate data
        if (empty($text_selection)) {
            // No text selected to fix
            $this->reword_exit('No text selection reported');
        }
        // Set text correction string if empty
        if (empty($text_fix)) {
            $text_fix = '(remove)';
        }
        // Set full text string if empty
        if (empty($full_text)) {
            $full_text = 'NA';
        }
        // Set URL string if empty
        if (empty($text_url)) {
            $text_url = 'NA';
        }
        // Verify nonce
        if (false === $this->reword_verify_nonce('reword_mistake_report_nonce')) {
            $this->reword_exit('Mistake report nonce verification failed');
        }
        // Check if report was already sent
        $report_exist = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . REWORD_DB_NAME . " WHERE mistake = %s AND site_info = %s AND full_text = %s",
                $text_selection,
                $text_url,
                $full_text
            ),
            ARRAY_A
        );
        // Check error
        if ($wpdb->last_error) {
            $this->reword_exit($wpdb->last_error);
        }
        $reword_reports_min = get_option('reword_reports_min');
        if (null === $report_exist) {
            // First time report, insert to DB
            $data_arr = array(
                'mistake'          => $text_selection,
                'user_fix'         => $text_fix,
                'time'             => $current_time,
                'site_info'        => $text_url,
                'full_text'        => $full_text,
            );
            if (false === $wpdb->insert(REWORD_DB_NAME, $data_arr) || $wpdb->last_error) {
                $this->reword_exit($wpdb->last_error);
            }
            if ('1' === $reword_reports_min) {
                // Mail to admin first time report
                $this->reword_mail_new_report($text_selection, $text_fix, $text_url);
            }
        } else {
            // Update report with new fix suggestion
            $fix_update = $report_exist['user_fix'];
            // Check if already suggested
            if (false === strpos($fix_update, $text_fix)) {
                $fix_update .= ', ' . $text_fix;
            }
            if (
                false === $wpdb->update(
                    REWORD_DB_NAME,
                    array(
                        'reports_count' => $report_exist['reports_count'] + 1,
                        'user_fix'      => $fix_update,
                    ),
                    array('report_id' => $report_exist['report_id'])
                ) ||
                $wpdb->last_error
            ) {
                $this->reword_exit($wpdb->last_error);
            }
            if (strval($report_exist['reports_count'] + 1) === $reword_reports_min) {
                // Mail to admin
                $this->reword_mail_new_report($text_selection, $text_fix, $text_url);
            }
        }
        $this->reword_exit('Report:[' . $text_selection . '] succeeded');
    }

    /**
     * Fetch POST or GET data.
     *
     * return post sanitized input, based on data type.
     * Default type is string.
     *
     * @param string $data_name - posted input name
     * @return mixed - $ret_data
     */
    private function reword_fetch_data($data_name)
    {
        $ret_data = null;
        switch ($data_name) {
            case 'reword_submit':
            case 'reword_default':
            case 'reword_settings_nonce':
            case 'reword_reports_nonce':
            case 'reword_mistake_report_nonce':
            case 'reword_icon_pos':
            case 'reword_banner_pos':
            case 'reword_access_cap':
            case 'action':
            case 'text_selection':
            case 'text_fix':
            case 'full_text':
            case 'text_url':
                $ret_data = filter_input(INPUT_POST, $data_name, FILTER_SANITIZE_SPECIAL_CHARS);
                break;
            case 'reword_reports_min':
                $ret_data = filter_input(INPUT_POST, $data_name, FILTER_SANITIZE_NUMBER_INT);
                break;
            case 'reword_notice_banner':
            case 'reword_send_stats':
                $ret_data = filter_input(INPUT_POST, $data_name, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'reword_email_add':
                $ret_data = filter_input(INPUT_POST, $data_name, FILTER_VALIDATE_EMAIL, FILTER_REQUIRE_ARRAY);
                break;
            case 'reword_email_remove':
            case 'id':
                $ret_data = filter_input(INPUT_POST, $data_name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
                break;
            case 'active_tab':
                $ret_data = filter_input(INPUT_GET, $data_name, FILTER_SANITIZE_SPECIAL_CHARS);
                break;
            case 'reword_email_new':
            case 'reword_plugin_version':
                // No data to fetch for these
                break;
            default:
                // Unexpected input
                $this->reword_log(REWORD_ERR, 'Unexpected data:[' . $data_name . ']');
                break;
        }
        return $ret_data;
    }

    /**
     * Count number of reports.
     *
     * Returns number of reports in DB based on type:
     *  'new'
     *  'ignored'
     *
     * @global Object $wpdb
     * @param String $type - 'new' or 'ignore'
     * @return int
     */
    private function reword_get_reports_count($type)
    {
        global $wpdb;
        if ($type === 'new') {
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM " . REWORD_DB_NAME . " WHERE reports_count >= %s AND status = %s",
                get_option('reword_reports_min'),
                $type
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM " . REWORD_DB_NAME . " WHERE status = %s",
                $type
            );
        }
        $reports_count = $wpdb->get_var($sql);
        // Check error
        if ($wpdb->last_error) {
            $this->reword_log(REWORD_ERR, $wpdb->last_error);
            $reports_count = 0;
        }
        return $reports_count;
    }

    /**
     * Mail notification on new report
     *
     * @param String $mistake
     * @param String $fix
     * @param String $url
     */
    private function reword_mail_new_report($mistake, $fix, $url)
    {
        $email_addr_arr = get_option('reword_email_new');
        $subject = 'New mistake report from ' . get_bloginfo('name');
        $body = 'Hi,<br />' .
            'A new mistake was reported on ' . get_bloginfo('name') . ':<br />' .
            '"' . $mistake . '" was suggested to be - "' . $fix . '".<br />' .
            'Found at: ' . $url . '<br />' .
            'Log in to your admin panel for more info<br /><br />' .
            'Thanks,<br />' .
            'ReWord team.';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        foreach ($email_addr_arr as $email_addr) {
            wp_mail($email_addr, $subject, $body, $headers);
        }
    }

    /**
     * Error handling functions.
     */
    /**
     * Log to debug.log and send to reword
     *
     * @param String $level - REWORD_ERR, REWORD_NOTICE
     * @param String $msg
     * @param int $backtrace_index - backtrace level: 1 = calling function, 2 = 2nd calling function
     */
    public function reword_log($level, $msg,  $backtrace_index = 1)
    {
        // Get calling function details from backtrace
        $backtrace = debug_backtrace();
        if ($backtrace && count($backtrace) > $backtrace_index) {
            $file = $backtrace[$backtrace_index]['file'];
            $line = $backtrace[$backtrace_index]['line'];
            $func = $backtrace[$backtrace_index]['function'];
            $err_msg = '[' . $level . '] ' . $msg . ' - ' . $func . '() at ' . $file . '(' . $line . ')';
        } else {
            $err_msg = '[' . $level . '] ' . $msg;
        }
        // Log error
        error_log($err_msg);
        // Send stats to reword
        if ('true' === get_option('reword_send_stats')) {
            // Send $err_msg
        }
    }

    /**
     * Deactivate reword plugin
     */
    public function reword_deactivate()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        // Check if plugin is active
        if (is_plugin_active(REWORD_PLUGIN_BASENAME)) {
            $this->reword_log(REWORD_NOTICE, 'Reword plugin deactivated', 2);
            deactivate_plugins(REWORD_PLUGIN_BASENAME);
        }
    }

    /**
     * Stop process (die) using wordpress API
     *
     * @param String $msg
     */
    public function reword_die($msg)
    {
        $this->reword_log(REWORD_NOTICE, '[Reword die] ' . $msg, 2);
        wp_die(
            'Oops, something went wrong. ' . $msg . '<br />' .
                'Please try again later...<br /><br />' .
                '<a href="' . admin_url('plugins.php') . '">Click here to go back</a>'
        );
    }

    /**
     * Stop process (exit)
     *
     * @param String $msg
     */
    public function reword_exit($msg)
    {
        $this->reword_log(REWORD_NOTICE, '[Reword exit] ' . $msg, 2);
        exit($msg);
    }

    /**
     * Echo wordpress notice message
     *
     * @param String $msg
     */
    public function reword_wp_notice($msg)
    {
        echo (
            '<div class="notice notice-error is-dismissible">
                <p>' . $msg . '</p>
            </div>'
        );
    }

    /**
     * Nonce verification
     *
     * @param String $nonce_name
     * @return boolean - false if failed, true otherwise
     */
    private function reword_verify_nonce($nonce_name)
    {
        $reword_nonce = $this->reword_fetch_data($nonce_name);
        if (!wp_verify_nonce($reword_nonce, $nonce_name)) {
            $this->reword_log(REWORD_ERR, 'Reword nonce ' . $nonce_name . ':[' . $reword_nonce . '] verification failed');
            return false;
        }
        return true;
    }
}
