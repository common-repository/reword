<?php
/*
 * ReWord Reports Center page.
 *
 * Included from reword_admin_reports_page() (at Reword_Plugin class)
 *
 * Uses:
 * $reword_reports_table - Reword reports table object
 * $reword_new_reports_count - Number of new reports
 * $reword_ignore_reports_count - Number of ignored reports
 * $reword_show_delete_all - flag to show delete all button
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">ReWord Reports Center</h1>
    <?php if (true === $reword_show_delete_all) { ?>
        <button name="delete_all" class="page-title-action" onclick="return rewordRowAction( 'delete_all', null )">Delete All</button>
    <?php } ?>
    <hr class="wp-header-end">
    <h2 class="nav-tab-wrapper">
        <a href="?page=reword-reports&amp;active_tab=new" class="nav-tab <?php echo $active_tab == 'new' ? 'nav-tab-active' : ''; ?>">New (<?php echo $reword_new_reports_count ?>)</a>
        <a href="?page=reword-reports&amp;active_tab=ignore" class="nav-tab <?php echo $active_tab == 'ignore' ? 'nav-tab-active' : ''; ?>">Ignored (<?php echo $reword_ignore_reports_count ?>)</a>
    </h2>
    <form id="reword-report-form" method="post">
        <?php $reword_reports_table->display(); ?>
        <input type="hidden" id="reword-reports-nonce" name="reword_reports_nonce" value="<?php echo wp_create_nonce('reword_reports_nonce') ?>" />
    </form>
</div>