/*
 * Reword admin settings page scripts.
 */

/**
 * Show or hide banner position settings based on banner enabled or disabled.
 */
(rewordShowHideBannerPositionSettings = function () {
    var bannerCheck = document.getElementById('banner-enable');
    var bannerPos = document.getElementById('banner-pos');
    if ((null !== bannerCheck) && (null !== bannerPos)) {
        if (bannerCheck.checked) {
            bannerPos.style.display = '';
        } else {
            bannerPos.style.display = 'none';
        }
    }
})();

/**
 * Enable "Save Changes" button on settings change.
 */
rewordOnSettingChange = function () {
    var saveChangesButton = document.getElementById('reword_submit');
    if (null !== saveChangesButton) {
        saveChangesButton.removeAttribute('disabled');
    }
};

/**
 * Confirm restore defaults.
 */
rewordOnRestoreDefault = function () {
    return confirm('Are you sure you want to reset all your settings?');
};

/**
 * Add another email text input
 */
rewordAddEmailText = function () {
    var emailAddList = document.getElementById('reword_email_add_list');
    var newEmailAddField = '<div><input name="reword_email_add[]" type="email" class="regular-text" oninput="rewordOnSettingChange()"><span class="dashicons dashicons-remove" style="vertical-align: middle; cursor: pointer; color: red;" onclick="rewordRemoveEmailText(this)"></span><br /><br /></div>';
    emailAddList.insertAdjacentHTML('beforeend', newEmailAddField);

    // Set focus on the newly added input
    var newInput = emailAddList.lastElementChild.querySelector('input');
    newInput.focus();
}

/**
 * Remove an email text input
 */
rewordRemoveEmailText = function (element) {
    var container = element.parentNode;
    container.parentNode.removeChild(container);
}
