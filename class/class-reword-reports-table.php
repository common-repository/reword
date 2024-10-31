<?php

// Wordpress list table class
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Reword reports table class.
 *
 * Creates reports tables, displayed in reports admin page tabs:
 * - New
 * - Ignored
 *
 * Each row allows the following actions (also in bulk):
 * - "Ignore"     - Ignores any future reports on this specific mistake.
 * - "Delete"     - Remove mistake from DB. If reported again will be
 *                   inserted back to DB as "new" report.
 */
class Reword_Reports_Table extends WP_List_Table
{
    /**
     * @var string $table_type - new or ignore
     */
    private $table_type = null;
    private $reword_obj = null;

    /**
     * Class constructor.
     *
     * @param string $type
     */
    function __construct($type)
    {
        // Set parent defaults
        parent::__construct(array(
            'singular' => 'report',
            'plural'   => 'reports',
            'ajax'     => false,
        ));

        // Reword object for error handling
        $this->reword_obj = new Reword_Plugin;

        // Reword table types - new or ignore.
        if (('new' === $type) || ('ignore' === $type)) {
            $this->table_type = $type;
        } else {
            $this->reword_obj->reword_log(REWORD_ERR, 'Invalid table type:[' . $type . '], setting default:[new]');
            $this->table_type = 'new';
        }
    }

    /**
     * Set table classes.
     * "fixed" class is removed from default.
     * "plugins" class is used only when table not empty,
     * for better view empty tables on mobile.
     *
     * @return array
     */
    protected function get_table_classes()
    {
        return array('widefat', 'striped', $this->has_items() ? 'plugins' : '', $this->_args['plural']);
    }

    /**
     * Check-boxes column format.
     *
     * @param array $item
     * @return string
     */
    function get_column_cb($item)
    {
        return ('<input type="checkbox" name="id[]" value="' . $item['report_id'] . '" />');
    }

    /**
     * Mistakes column format.
     *
     * @param array $item
     * @return string - mistake (once / X times) and mistake actions - "Ignore" and "Delete"
     */
    function get_column_mistake($item)
    {
        // Actions links
        $actions = array();
        if ('ignore' !== $this->table_type) {
            $actions['ignore'] =
                '<a href="#" title="Ignored mistakes will not be reported again" ' .
                'onclick="return rewordRowAction(\'ignore\', ' . $item['report_id'] . ')">Ignore</a>';
        }
        $actions['delete'] =
            '<a href="#" title="Deleted mistakes are not shown unless reported again" ' .
            'onclick="return rewordRowAction(\'delete\', ' . $item['report_id'] . ')">Delete</a>';
        // Report number format
        $times = (($item['reports_count'] > 1) ? ($item['reports_count'] . ' times') : ('once'));
        return ('<p>&lt;' . $item['mistake'] . '&gt; <span style="color:silver">(' . $times . ')</span></p>' . $this->row_actions($actions));
    }

    /**
     * Details column format.
     *
     * @param array $item
     * @return string
     */
    function get_column_details($item)
    {
        $display_url = preg_replace("#^[^:/.]*[:/]+#i", "", preg_replace("{/$}", "", urldecode($item['site_info'])));
        if ('NA' === $item['full_text']) {
            $info_div = '<p><b>Text details are not available</b></p>';
        } else {
            $full_text = str_replace('__R1__' . $item['mistake'] . '__R2__', '<u><b>' . $item['mistake'] . '</u></b> { suggestions: <i>' . $item['user_fix'] . '</i> } ', $item['full_text']);
            $info_div = '<p>"...' . $full_text . '..."</p>';
        }
        $actions = array(
            'time' => '<span style="color:black">' . date("M j, H:i", $item['time']) . '</span>',
            'link' => wp_is_mobile() ?
                '<a href="' . $item['site_info'] . '" target="_blank">View page</a>' :
                '<span style="color:black">Found at: <a href="' . $item['site_info'] . '" target="_blank">' . $display_url . '</a></span>',
        );
        return ($info_div . $this->row_actions($actions, true));
    }

    /**
     * Displays single table row.
     *
     * @param array $item
     */
    public function single_row($item)
    {
        echo '
            <tr>
                <th scope="row" class="check-column">
                    ' . $this->get_column_cb($item) . '
                </th>
                <td class="mistake column-mistake has-row-actions column-primary">
                    ' . $this->get_column_mistake($item) . '
                </td>
                <td class="details column-details">
                    ' . $this->get_column_details($item) . '
                </td>
            </tr>';
    }

    /**
     * Table headers names.
     *
     * @return array $columns
     */
    function get_columns()
    {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'mistake'   => 'Mistake',
            'details'   => 'Details',
        );
        return ($columns);
    }

    /**
     * Bulk actions array.
     *
     * @return array $actions
     */
    function get_bulk_actions()
    {
        $actions = array();
        if ('ignore' !== $this->table_type) {
            $actions['ignore'] = 'Ignore';
        }
        $actions['delete'] = 'Delete';
        return ($actions);
    }

    /**
     * Prepare table items to display.
     *
     * Sets the table options and settings, reads and sorts table data.
     *
     * @global Object $wpdb
     */
    function prepare_items()
    {
        global $wpdb;
        // Define and build column headers
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        // Get data from DB
        if ($this->table_type === 'new') {
            $sql = $wpdb->prepare(
                "SELECT * FROM " . REWORD_DB_NAME . " WHERE reports_count >= %s AND status = %s ORDER BY reports_count DESC",
                get_option('reword_reports_min'),
                $this->table_type
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM " . REWORD_DB_NAME . " WHERE status = %s ORDER BY time DESC",
                $this->table_type
            );
        }
        $reports_arr = $wpdb->get_results($sql, ARRAY_A);
        // Check error
        if ($wpdb->last_error) {
            $this->reword_obj->reword_log(REWORD_ERR, $wpdb->last_error);
            $this->reword_obj->reword_wp_notice('ReWord failed to access DB. Please try again later...');
        }
        // Items count
        $total_items = $wpdb->num_rows;
        $this->items = $reports_arr;
        // Register pagination options & calculations
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $total_items,
        ));
    }
} // End Reword_List_Table class
