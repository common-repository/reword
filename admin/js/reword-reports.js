/*
 * Reword admin reports page scripts.
 */

/**
 * Onclick callback for reports table row actions.
 *
 * Submits form with action data.
 *
 * @param {String} action - ignore or delete
 * @param {int} id - report ID
 * @returns {Boolean} - false if delete not confirmed, true otherwise
 */
function rewordRowAction(action, id) {
    // Confirm delete
    if (('delete' === action) &&
        (false === confirm('Are you sure you want to delete this report?'))) {
        // Delete confirmation canceled
        return false;
    }
    if (('delete_all' === action) &&
        (false === confirm('Are you sure you want to delete all reports (new and ignored)?'))) {
        // Delete confirmation canceled
        return false;
    }
    // Create form and add inputs
    var rewordReportsForm = document.createElement('form');
    rewordReportsForm.method = 'POST';
    rewordReportsForm.action = '';

    var reportId = document.createElement('input');
    reportId.type = 'hidden';
    reportId.name = 'id[0]';
    reportId.value = id;
    rewordReportsForm.appendChild(reportId);

    var reportAction = document.createElement('input');
    reportAction.type = 'hidden';
    reportAction.name = 'action';
    reportAction.value = action;
    rewordReportsForm.appendChild(reportAction);

    rewordReportsForm.appendChild(document.getElementById('reword-reports-nonce'));

    document.body.appendChild(rewordReportsForm);
    rewordReportsForm.submit();
    return true;
}

/**
 * Reports table bulk action data check and delete confirmation
 */
var rewordReportForm = document.getElementById('reword-report-form');
if (null != rewordReportForm) {
    rewordReportForm.onsubmit = function () {
        data = new FormData(rewordReportForm);
        if (data && data.get('id[]')) {
            if ('delete' == data.get('action')) {
                return confirm('Are you sure you want to delete these reports?')
            }
        } else {
            return false;
        }
        return true;
    }
}