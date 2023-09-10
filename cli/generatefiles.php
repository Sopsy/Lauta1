<?php
// Used to generate JS config and locale files

$cfg = new StdClass();
include(dirname(__DIR__) . '/public/inc/config.php');

foreach ($cfg->availableLanguages as $locale => $name) {
    // Load localization
    $locale = explode('|', $locale);
    if (empty($locale[1])) {
        $locale[1] = 'default';
    }
    [$locale, $domain] = $locale;

    putenv('LANGUAGE=');
    setlocale(LC_ALL, $locale);
    bindtextdomain($domain, $cfg->siteDir . '/inc/i18n');
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);

    $messages = [
        'captchaRunning' => _('Google reCAPTCHA is verifying that you are not a robot. Please wait...'),
        'remove' => _('Remove'),
        'emailRemoved' => _('Email address removed'),
        'resetPassword' => _('Reset password'),
        'areYouSure' => _('Are you sure?'),
        'loading' => _('Loading...'),
        'cancel' => _('Cancel'),
        'hideEmbed' => _('Close media'),
        'addReply' => _('Reply'),
        'save' => _('Save'),
        'saving' => _('Saving...'),
        'close' => _('Close'),
        'notifications' => _('Notifications'),
        'markAllRead' => _('Mark all as read'),
        'confirmPageLeave' => _('Your message might disappear if you leave this page.'),
        'timeoutWarning' => _('Loading timed out – please check your internet connection'),
        'networkError' => _('Network error – please check that you are connected to the internet'),
        'invalidResponse' => _('Invalid data received. This is an internal error.'),
        'emptyResponse' => _('No data received. This is an internal error.'),
        'userinfoChanged' => _('User information changed'),
        'error' => _('An error occurred'),
        'copy' => _('Copy'),
        'copySuccess' => _('Copied to clipboard'),
        'copyFailed' => _('Could not copy to clipboard'),
        'thread' => [
            'subject' => _('Subject'),
            'delete' => _('Delete thread'),
            'deleted' => _('Thread deleted'),
            'deleteConfirm' => _('Are you sure you want to delete this thread?'),
            'deletePost' => _('Delete the thread'),
            'noNewReplies' => _('No new replies'),
            'autoUpdater' => [
                'threadDeleted' => _('This thread was deleted'),
                'notInitialized' => _('Thread AutoUpdater is not initialized. Please report this bug.'),
                'noNewReplies' => _('No new replies'),
                'unknownError' => _('Loading new replies failed with an unknown error'),
                'retrying' => _('Loading new replies failed, trying again...'),
                'stoppedInactive' => _('Loading new posts automatically was stopped because there were no new posts for a long time'),
            ],
            'subscribe' => [
                'add' => _('Follow thread'),
                'added' => _('Thread added to followed threads'),
                'remove' => _('Remove from followed threads'),
                'removed' => _('Thread removed from followed threads'),
                'removeAllConfirm' => _('Are you sure you want to remove ALL of your followed threads on each board?'),
                'updated' => _('Followed threads updated'),
            ],
            'hide' => [
                'add' => _('Hide thread'),
                'remove' => _('Restore thread'),
            ],
        ],
        'goldKey' => [
            'activateConfirm' => _('Activate this Gold account key?'),
            'invalid' => _('Invalid Gold account key'),
            'activated' => _('Gold account activated!'),
        ],
        'deleteAccount' => [
            'confirm' => _('Do you really want to completely delete your user account? You will lose any Gold account time you have left! This operation cannot be undone.'),
            'canceled' => _('User account deletion was not confirmed, account not deleted'),
            'success' => _('User account deleted'),
        ],
        'deleteData' => [
            'confirm' => _('Do you really want to delete the selected data? All of them will be deleted permanently and cannot be restored afterwards.'),
            'canceled' => _('Deletion cancelled, nothing deleted.'),
            'success' => _('Selected data is now permanently deleted.'),
        ],
        'password' => [
            'noMatch' => _('Passwords do not match'),
            'newEmpty' => _('Please type in a password'),
            'currentEmpty' => _('Please type in a your current password'),
            'tooWeak' => _('This password is too weak'),
            'success' => _('Password changed successfully'),
        ],
        'post' => [
            'edit' => _('Edit post'),
            'edits' => _('Post edits'),
            'editCancel' => _('Cancel editing? You will lose your changes.'),
            'cancel' => _('Cancel this post?'),
            'delete' => _('Delete post'),
            'deleted' => _('Post deleted'),
            'deleteConfirm' => _('Are you sure you want to delete this post?'),
            'deleteFile' => _('Only delete the file'),
            'deletePost' => _('Delete the post'),
            'sent' => _('Post sent'),
            'sending' => _('Sending...'),
            'uploadingFile' => _('Uploading file...'),
            'fileTooBig' => _('The file you have chosen exceeds the maximum file size limit of your user account'),
            'saving' => _('Saving post...'),
            'replies' => _('Replies'),
            'message' => _('Message'),
            'share' => _('Share post'),
            'op' => _('OP'),
            'you' => _('You'),
            'linkHere' => _('Link to this post'),
            'maxSizeExceeded' => _('Your files exceed the maximum upload size.'),
            'waitingForFileUpload' => _('Your message will be sent after the file upload is completed.'),
            'fileDeleted' => _('File deleted'),
            'countByUser' => _('Number of posts'),
            'hide' => [
                'add' => _('Hide post'),
                'remove' => _('Restore post'),
            ],
            'report' => [
                'title' => _('Report a rule violation'),
                'success' => _('Rule violation reported'),
            ],
            'donateGold' => [
                'title' => _('Donate a Gold account'),
                'success' => _('Gold account donated!'),
            ]
        ],
        'oldBrowserWarningTitle' => _('Update your browser'),
        'oldBrowserWarning' => _('You are using an outdated browser. This site will not function properly with it.'),
        'oldBrowserWarningSuggestions' => _('Please check for updates for your browser and operating system - or switch to Firefox or Chrome.'),
    ];

    $out = 'window.messages=' . json_encode($messages) . '';
    file_put_contents(dirname(__DIR__) . '/static/js/Locale/' . $locale . '.' . $domain . '.js', $out);
}