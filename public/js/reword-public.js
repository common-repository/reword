/*
 * Reword public pages script.
 *
 * Handles:
 * - Text selection events
 * - Reword notice banner
 * - Mistakes report data post
 */

/* global rewordPublicData */

/* Constants */
const REWODR_ICON_ACTIVE_TITLE = 'Click to report marked mistake to admin using ReWord';
const REWODR_ICON_INACTIVE_TITLE = 'Found a mistake? Mark it and click to report using ReWord';
const REWORD_ICON_TEXT = 'R';
const REWORD_ICON_ID = 'reword';
const REWORD_FULL_TEXT_CHARS = 50;
const REWORD_MAX_SENT_CHARS = 200;

// Globals
var rewordBanner = null;
var rewordIcon = rewordIconCreate();
var rewordHTTP = rewordHTTPCreate();
var rewordSelection = document.getSelection();
var rewordSelectedText = null;
var rewordFullText = null;
var rewordTextUrl = null;

/**
 * Create reword icon badge element.
 * This badge is used to send mistakes to admin.
 */
function rewordIconCreate() {
    var iconElm = document.createElement('div');
    iconElm.id = REWORD_ICON_ID;
    iconElm.innerText = REWORD_ICON_TEXT;
    iconElm.className = 'reword-icon reword-icon-inactive ' + rewordPublicData.rewordIconPos;
    iconElm.addEventListener('click', rewordIconClickCallBack);
    iconElm.title = REWODR_ICON_INACTIVE_TITLE;
    // Append icon to page body
    document.body.appendChild(iconElm);
    return iconElm;
}

/**
 * Set icon state:
 *  'active'    - red and ready to send marked mistake.
 *  'inactive'  - gray, waiting for text selection
 *
 * @param {String} state
 */
function rewordIconStateSet(state) {
    if ('active' === state) {
        rewordIcon.classList.remove('reword-icon-inactive');
        rewordIcon.classList.add('reword-icon-active');
        rewordIcon.title = REWODR_ICON_ACTIVE_TITLE;
    } else {
        rewordIcon.classList.remove('reword-icon-active');
        rewordIcon.classList.add('reword-icon-inactive');
        rewordIcon.title = REWODR_ICON_INACTIVE_TITLE;
    }
}

/**
 * Create HTTP request for sending mistake data to admin.
 * Set response function.
 *
 * @returns {XMLHttpRequest} httpReq
 */
function rewordHTTPCreate() {
    // HTTP post request
    var httpReq = new XMLHttpRequest();
    // Response callback
    httpReq.onreadystatechange = function () {
        if (httpReq.readyState === XMLHttpRequest.DONE) {
            console.dir(httpReq.responseText);
            if ('true' === rewordPublicData.rewordSendStats) {
                // Send stats
            }
        }
    };
    return httpReq;
}

/**
 * Add event listeners for text selection
 */
(function rewordAddEventListener() {
    // Set events listeners
    document.addEventListener('selectionchange', rewordSelectionCallBack);
    // Events listeners to check if text is marked
    document.addEventListener('mouseup', rewordDismissEventCallBack);
    // This event handles the case were user change marked text with keyboard
    document.addEventListener('keyup', rewordDismissEventCallBack);
    // Mobile touch event
    document.addEventListener('touchend', rewordDismissEventCallBack);
}());

/**
 * Selection event callback.
 */
function rewordSelectionCallBack() {
    if ((null !== rewordSelection) &&
        (null !== rewordSelection.toString()) &&
        ('' !== rewordSelection.toString())) {
        // Set selected text range
        var rewordRange = rewordSelection.getRangeAt(0);
        if (rewordRange) {
            rewordSelectedText = rewordRange.toString().trim();
            rewordFullText = rewordGetFullText(rewordRange);
            rewordTextUrl = rewordGetURL(rewordRange);
        }
        // Activate reword icon link
        rewordIconStateSet('active');
    }
}

/**
 * Dismiss selection event
 */
function rewordDismissEventCallBack(e) {
    if ((REWORD_ICON_ID !== e.target.id) &&
        ((null === rewordSelection) ||
            (null === rewordSelection.toString()) ||
            ('' === rewordSelection.toString()))) {
        // Reset selection
        rewordSelectedText = null;
        rewordFullText = null;
        rewordTextUrl = null;
        // Deactivate reword icon link
        rewordIconStateSet('inactive');
    }
}

/**
 * Handle icon click event
 */
function rewordIconClickCallBack() {
    // If we have selected text, prompt user to send fix
    if (null !== rewordSelectedText) {
        if (rewordSelectedText.length > REWORD_MAX_SENT_CHARS) {
            alert('Selected text too long (' + REWORD_MAX_SENT_CHARS + ' chars maximum). Please select shorter text');
        } else {
            var fixedText = prompt('ReWord - "' + rewordSelectedText + '" needs to be:');
            if (null !== fixedText) {
                if (fixedText.length > REWORD_MAX_SENT_CHARS) {
                    alert('Fixed text too long (' + REWORD_MAX_SENT_CHARS + ' chars maximum). Please send shorter text');
                } else {
                    if (rewordSelectedText !== fixedText) {
                        // Send HTTP post request
                        var params =
                            'text_selection=' + rewordSelectedText +
                            '&text_fix=' + fixedText +
                            '&full_text=' + rewordFullText +
                            '&text_url=' + rewordTextUrl +
                            '&reword_mistake_report_nonce=' + rewordPublicData.rewordMistakeReportNonce +
                            '&action=reword_send_mistake';

                        rewordHTTP.open('POST', rewordPublicData.rewordPublicPostPath, true);
                        rewordHTTP.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        rewordHTTP.send(params);
                    }
                }
            }
        }
    } else {
        // Notify user how to report mistakes
        alert('To report mistake, mark it and click ReWord icon');
    }
    // Reset selection
    rewordSelectedText = null;
    rewordFullText = null;
    rewordTextUrl = null;
    // Deactivate reword icon link
    rewordIconStateSet('inactive');
}

/**
 * Return marked mistake full text context to send to admin
 *
 * @param {Range} - User selection range
 * @returns {String} - Selection range element full text, 'NA' if range is null
 */
function rewordGetFullText(rewordRange) {
    if (null !== rewordRange) {
        // Check trimmed white spaces
        var selectedText = rewordRange.toString();
        var startOffset = rewordRange.startOffset + (selectedText.length - selectedText.trimStart().length);
        var endOffset  = rewordRange.endOffset - (selectedText.length - selectedText.trimEnd().length);
        // Marked full text start and end with maximum REWORD_FULL_TEXT_CHARS at each side
        var fromIndex = ((startOffset < REWORD_FULL_TEXT_CHARS) ? 0 : (startOffset - REWORD_FULL_TEXT_CHARS));
        var toIndex = ((endOffset + REWORD_FULL_TEXT_CHARS > rewordRange.endContainer.textContent.length) ? rewordRange.endContainer.textContent.length : (endOffset + REWORD_FULL_TEXT_CHARS));
        // return full text with marked mistake
        return (rewordRange.startContainer.textContent.substring(fromIndex, startOffset) +
            '__R1__' + selectedText.trim() + '__R2__' +
            rewordRange.endContainer.textContent.substring(endOffset, toIndex));
    } else {
        return 'NA';
    }
}

/**
 * Return mistake URL (with tag)
 *
 * @param {Range} - User selection range
 * @returns {String} - Selection range element URL, 'NA' if range is null
 */
function rewordGetURL(rewordRange) {
    if (null !== rewordRange) {
        // Get element ID, or closest parent ID (if any)
        var textElementDataTmp = rewordRange.commonAncestorContainer.parentElement;
        var textTag = null;
        while ((!textTag) && (textElementDataTmp)) {
            textTag = textElementDataTmp.id;
            textElementDataTmp = textElementDataTmp.parentElement;
        }
        return rewordRange.commonAncestorContainer.baseURI.split('#')[0] + '#' + textTag;
    } else {
        return 'NA';
    }
}

/**
 * Set reword notice banner.
 *
 * Original code taken from https://cookieconsent.insites.com/
 *
 * @param {String} rewordBannerEnabled - true, false
 * @param {String} rewordBannerPos
 */
(function rewordBannerSet(rewordBannerEnabled, rewordBannerPos) {
    // Reword notice banner script
    if ('true' === rewordBannerEnabled) {
        window.addEventListener('load', function () {
            window.cookieconsent.initialise({
                'palette': {
                    'popup': {
                        'background': '#000'
                    },
                    'button': {
                        'background': '#f1d600'
                    }
                },
                'theme': 'edgeless',
                'position': rewordBannerPos,
                'content': {
                    'message': 'Found a mistake? Mark text and click ReWord \"R\" icon to report.',
                    'dismiss': 'Got it',
                    'link': 'About ReWord',
                    'href': 'https://wordpress.org/plugins/reword/'
                }
            },
                // Global to use cookieconsent functions
                function (popup) {
                    rewordBanner = popup;
                });
        });
    }
}(rewordPublicData.rewordBannerEnabled, rewordPublicData.rewordBannerPos));
