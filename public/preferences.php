<?php
// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => '',
    'posts' => '',
    'fileupload' => '',
    'user' => '',
];
include("inc/engine.class.php");
new Engine($loadClasses);

$html->printHeader();
$html->printSidebar();

echo '<div id="right" class="preferences">

<ul id="tabchooser">
    <li></li>
    <li class="tab" data-tabid="site">' . _('Site preferences') . '</li>
    <li class="tab" data-tabid="boards">' . _('Boards') . '</li>
    <li class="tab" data-tabid="profile">' . _('User profile') . '</li>
    <li class="tab" data-tabid="goldaccount">' . _('Gold account') . '</li>
    <li class="tab" data-tabid="tags">' . _('Tags') . '</li>
    <li class="tab" data-tabid="sessions">' . _('Active logins') . '</li>
    <li></li>
</ul>

<div id="site" class="tab">

<form action="' . $engine->cfg->siteUrl . '/scripts/ajax/savesettings.php" method="post" data-e="submitForm">
    <h3>' . _('General preferences') . '</h3>
    <span class="block">
        <input type="checkbox" id="hide_images" name="hide_images"' . ($user->getPreferences('hide_images') ? ' checked' : '') . ' />
        <label for="hide_images">' . _('Hide image thumbnails') . '</label>
    </span>

    <h3>' . _('Notifications and subscribed threads') . '</h3>
    <span class="block">
        <input type="checkbox" id="auto_follow" name="auto_follow"' . ($user->getPreferences('auto_follow') ? ' checked' : '') . ' />
        <label for="auto_follow">' . _('Automatically subscribe to all threads I create') . '</label>
    </span>
    <span class="block">
        <input type="checkbox" id="auto_follow_reply" name="auto_follow_reply"' . ($user->getPreferences('auto_follow_reply') ? ' checked' : '') . ' />
        <label for="auto_follow_reply">' . _('Automatically subscribe to all threads I reply to') . '</label>
    </span>
    <span class="block">
        <input type="checkbox" id="notification_from_thread_replies" name="notification_from_thread_replies"' . ($user->getPreferences('notification_from_thread_replies') ? ' checked' : '') . ' />
		<label for="notification_from_thread_replies">' . _('Notify me about new replies to my threads') . '</label>
    </span>
    <span class="block">
        <input type="checkbox" id="notification_from_followed_replies" name="notification_from_followed_replies"' . ($user->getPreferences('notification_from_followed_replies') ? ' checked' : '') . ' />
		<label for="notification_from_followed_replies">' . _('Notify me about new replies to threads I am subscribed to') . '</label>
    </span>
    <span class="block">
		<input type="checkbox" id="notification_from_post_replies" name="notification_from_post_replies"' . ($user->getPreferences('notification_from_post_replies') ? ' checked' : '') . ' />
		<label for="notification_from_post_replies">' . _('Notify me about new replies to my posts') . '</label>
    </span>
    <span class="block">
        <input type="checkbox" id="notification_from_post_upvotes" name="notification_from_post_upvotes"' . ($user->getPreferences('notification_from_post_upvotes') ? ' checked' : '') . ' />
		<label for="notification_from_post_upvotes">' . _('Notify me about upvotes to my posts') . '</label>
    </span>
    <span class="block">
        <input type="checkbox" id="follow_show_floatbox" name="follow_show_floatbox"' . ($user->getPreferences('follow_show_floatbox') ? ' checked' : '') . ' />
        <label for="follow_show_floatbox">' . _('Show floating followed threads -box') . '</label>
    </span>
    <h4>' . _('Order of followed threads') . '</h4>
    <select id="follow_order_by_bumptime" name="follow_order_by_bumptime">
        <option value="0">' . _('Threads with most unread replies first') . '</option>
        <option value="1"' . ($user->getPreferences('follow_order_by_bumptime') ? ' selected' : '') . '>' . _('Threads with latest replies first') . '</option>
    </select>
    <h3>' . _('Gold account functions') . '</h3>';

if (!$user->hasGoldAccount) {
    echo '
    <p>' . _('To activate these functions, you need to have a Ylilauta Gold account.') . '</p>';
}

echo '
    <span class="block">
		<input type="checkbox" id="hide_ads" name="hide_ads"' . ($user->getPreferences('hide_ads') ? ' checked' : '') . (!$user->hasGoldAccount ? ' disabled' : '') . ' />
		<label for="hide_ads">' . _('Remove advertisements and analytics') . '</label>
    </span>
    <span class="block">
        <h4>' . _('Hide all posts from these users (one per row)') . '</h4>
        <textarea name="hide_names"' . (!$user->hasGoldAccount ? ' disabled' : '') . '>' . implode("\n", $user->getHiddenNames()) . '</textarea>
    </span>
    <h4>' . _('Custom CSS') . '</h4>
    <span class="block">
        <button class="linkbutton" data-e="displayCustomCss" type="button">' . _('Display text input') . '</button>';
    $areaHeight = (preg_match_all("/\n/", $user->getPreferences('custom_css')) * 15 + 15);
    if ($areaHeight < 150) {
        $areaHeight = 150;
    } elseif ($areaHeight > 800) {
        $areaHeight = 800;
    }
    echo '
    <div id="custom_css" hidden>
        <textarea name="custom_css" maxlength="16000"' . (!$user->hasGoldAccount ? ' disabled' : '') . '>' . htmlspecialchars($user->getPreferences('custom_css')) . '</textarea>
        <p class="info">' . sprintf(_('If you accidentally do something stupid with this field, just open <b>%s</b> to restore the defaults.'),
    $engine->cfg->siteUrl . '/clearcss.php') . '</p>
    </div>
    </span>';

echo '
    <h3>' . _('Display language') . '</h3>
    <select name="language">';
foreach ($engine->cfg->availableLanguages AS $locale => $localeName) {
    echo '
        <option value="' . $locale . '"' . ($user->language == $locale ? ' selected' : '') . '>' . $localeName . '</option>';
}
echo '
    </select>

    <h3>' . _('Layout and appearance') . '</h3>
	<h4>' . _('Stylesheet') . '</h4>
    <select name="style">';
foreach ($engine->cfg->availableStyles AS $styleKey => $style) {
    echo '<option value="' . $styleKey . '"' . ($user->getPreferences('style') == $styleKey ? ' selected' : '') . '>' . $style['name'] . '</option>';
}
echo '</select>';

if (!$user->hasGoldAccount) {
    echo '
    <p>' . _('To activate these functions, you need to have a Ylilauta Gold account.') . '</p>';
}
echo '
    <h4>' . _('Threads per board page') . '</h4>
    <select id="threads_per_page" name="threads_per_page"' . (!$user->hasGoldAccount ? ' disabled' : '') . '>
        <option ' . ($user->getPreferences('threads_per_page') == 5 ? ' selected' : '') . '>5</option>
        <option ' . ($user->getPreferences('threads_per_page') == 10 ? ' selected' : '') . '>10</option>
        <option ' . ($user->getPreferences('threads_per_page') == 15 ? ' selected' : '') . '>15</option>
        <option ' . ($user->getPreferences('threads_per_page') == 20 ? ' selected' : '') . '>20</option>
        <option ' . ($user->getPreferences('threads_per_page') == 25 ? ' selected' : '') . '>25</option>
    </select>

    <h4>' . _('Posts per thread on board page') . '</h4>
    <select id="preview_posts_per_thread" name="preview_posts_per_thread"' . (!$user->hasGoldAccount ? ' disabled' : '') . '>';
for ($i = 0; $i <= 10; ++$i) {
    echo '
        <option ' . ($user->getPreferences('preview_posts_per_thread') == $i ? ' selected' : '') . '>' . $i . '</option>';
}
echo '
    </select>

    <span class="block">
    	<input class="linkbutton" type="submit" value="' . _('Save changes to preferences') . '" />
    </span>
</form>
</div>

<div id="boards" class="tab">
    <h3>' . _('Hiding of boards') . '</h3>
    <p>' . _('Select the boards to be hidden from board listings and from pages that show threads from multiple boards.') . '</p>
    <p>' . _('The percentage after the board name tells you how many users have hidden that particular board.') . '</p>
    <button class="linkbutton" class="button" data-e="prefsToggleBoards">' . _('Toggle all') .'</button>
    <form action="' . $engine->cfg->siteUrl . '/scripts/ajax/savesettings.php" method="post" data-e="submitForm">
    <input type="hidden" name="hideboards" value="true" />
    <input type="hidden" name="csrf-token" value="' . $user->csrf_token . '" />
        <div class="board_hide_table">';

    $hide_stats = $user->getBoardHideStats();
    foreach ($html->getBoardList(true) as $list_board) {
        echo '
        <label>
            <input type="checkbox" name="hideboard[' . $list_board['boardid'] . ']" value="' . $list_board['boardid'] . '"' .
                (in_array($list_board['boardid'], $user->getHiddenBoards())?' checked':'') . ' />
            ' . $list_board['boardname'] . ' (' . $hide_stats[$list_board['boardid']] . '%)
        </label>';
    }
    echo '
        </div>
	    <input class="linkbutton" type="submit" value="' . _('Save changes to preferences') . '" />
	</form>
</div>
<div id="profile" class="tab">';

echo '
<h3>' . _('Trivia') . '</h3>
<div class="trivia">
    <p><span>' . _('Account created') . '</span> <time datetime="' . date(DateTime::ATOM, $user->account_created) . '">'
        . $engine->formatTime($user->language, $user, $user->account_created) .'</time></p>
    <p><span>' . _('User ID') . '</span> ' . number_format($user->info->id, 0, ',', ' ') . '</p>
    <p><span>' . _('Started threads') . '</span> ' . number_format($user->getStats('total_threads'), 0, ',', ' ') . '</p>
    <p><span>' . _('Sent messages') . '</span> ' . number_format($user->getStats('total_posts'), 0, ',', ' ') . '</p>
    <p><span>' . _('Total pageloads') . '</span> ' . number_format($user->getStats('total_pageloads'), 0, ',', ' ') . '</p>
    <p><span>' . _('Total characters in messages') . '</span> ' . number_format($user->getStats('total_post_characters'), 0, ',', ' ') . '</p>
    <p><span>' . _('Average message length') . '</span> ' . number_format((empty($user->getStats('total_posts')) ? 0 : $user->getStats('total_post_characters') / $user->getStats('total_posts')), 2, ',', ' ') . ' ' . _('characters') . '</p>
    <p><span>' . _('Epic threads') . '</span> ' . number_format($user->getStats('epic_threads'), 0, ',', ' ') . '</p>
    <p><span>' . _('Threads hidden') . '</span> ' . number_format($user->getStats('threads_hidden'), 0, ',', ' ') . '</p>
    <p><span>' . _('Threads followed') . '</span> ' . number_format($user->getStats('threads_followed'), 0, ',', ' ') . '</p>
    <p><span>' . _('Uploaded files') . '</span> ' . number_format($user->getStats('total_uploaded_files'), 0, ',', ' ') . '</p>
    <p><span>' . _('Total uploaded data') . '</span> ' . $engine->convertFilesize($user->getStats('total_uploaded_filesize')) . '</p>
    
    <p><span>' . _('Upboats given') . '</span> ' . number_format($user->getStats('total_upboats_given'), 0, ',', ' ') . '</p>
    <p><span>' . _('Upboats received') . '</span> ' . number_format($user->getStats('total_upboats_received'), 0, ',', ' ') . '</p>
    
    <p><span>' . _('Gold accounts donated') . '</span> ' . number_format($user->getStats('gold_accounts_donated'), 0, ',', ' ') . '</p>
    <p><span>' . _('Gold account donations received') . '</span> ' . number_format($user->getStats('gold_account_donations_received'), 0, ',', ' ') . '</p>
    <p><span>' . _('Activity points') . '</span> ' . number_format($user->activity_points, 0, ',', ' ') . '</p>
    <p><a class="linkbutton" href="/ownposts.php">' . _('View a list of my posts') . '</a></p>
</div>';
if (!$user->is_anonymous) {
    echo '
<h3>' . _('User account') . '</h3>
    <form class="async-form" name="userinfo" action="/scripts/ajax/saveloginname.php" method="post" data-e="changeUserInfo">
        <span class="block">
            <label class="fixedwidth" for="username">' . _('Username') . '</label>
            <input type="text" id="username" name="username" data-e="checkUsername" value="' . htmlspecialchars($user->info->username) . '" name="name" disabled maxlength="' . $engine->cfg->nameMaxLength . '" />
            <button class="linkbutton" data-e="changeUsername">' . _('Change') . '</button>
        </span>
        <span class="block">
            <label class="fixedwidth" for="email">' . _('Email') . '</label>
            <input type="email" id="email" name="email" placeholder="' . $user->emailIssetString() . '" name="name" disabled/>
            <button class="linkbutton" data-e="changeUserEmail">' . _('Change') . '</button>
        </span>
        <p>' . _('Your email address is only used for account recovery. We can not see it or use it to contact you.') . '</p>
        <span class="block">
            <label class="fixedwidth" for="password">' . _('Current password') . '</label>
            <input type="password" id="password" name="password" />
        </span>
        <button class="linkbutton" id="saveinfo">' . _('Save') . '</button>
    </form>
    <h3>' . _('Change password') . '</h3>
    <form name="changepassword" class="password-change async-form" action="/scripts/ajax/saveloginname.php" method="post" data-e="userChangePassword" autocomplete="off">
        <span class="block">
            <label class="fixedwidth" for="currentpassword">' . _('Current password') . '</label>
            <input type="password" id="currentpassword" autocomplete="new-password" />
        </span>
        <span class="block">
            <label class="fixedwidth" for="newpassword">' . _('New password') . '</label>
            <input type="password" id="newpassword" name="newpassword"  pattern=".{6,}" data-e="checkPassword" autocomplete="new-password" />
        </span>
        <span class="block">
            <label class="fixedwidth" for="confirmpassword">' . _('Password confirmation') . '</label>
            <input type="password" id="confirmpassword" name="confirmpassword" data-e="checkPassword" autocomplete="new-password" />
        </span>
        <button class="linkbutton">' . _('Change password') . '</button>
    </form>';
}

echo '
<h3>' . _('Delete user account') . '</h3>
<p>' . _('Deleting your user account permanently removes all your site preferences, statistics and other account related data.') . '</p>';
if ($user->hasGoldAccount) {
    echo '<p class="warning">' . _('Deleting your user account also removes any Gold account time you have left. It cannot be restored afterwards!') . '</p>';
}
if (empty($user->info->password)) {
    echo '<p class="info">' . _('As you have not registered, your user account will be deleted automatically after a few days of inactivity.') . '</p>';
}
echo '
<form class="async-form" action="/scripts/ajax/deleteprofile.php" method="post" data-e="userDeleteAccount">
    <span class="block"' . (empty($user->info->password) ? ' hidden' : '') . '>
        <label class="fixedwidth" for="deletionpassword">' . _('Password') . '</label>
        <input type="password" id="deletionpassword" name="deletionpassword" autocomplete="new-password" />
    </span>
    <span class="block">
        <label><input type="checkbox" id="confirmdelete" /> ' . _('Yes, I want to permanently delete my user account') . '</label>
    </span>
    <span class="block">
        <label><input type="checkbox" id="alsoposts" /> ' . _('Also delete all posts I have sent') . '</label>
    </span>
    <span class="block">
        <button class="linkbutton" id="deleteprofile">' . _('Delete account') . '</button>
    </span>
</form>

</div>

<div id="goldaccount" class="tab">';

if (!$user->hasGoldAccount) {
    echo '<h3>' . _('You don\'t have a Gold account') . '</h3>';
} else {
    echo '<h3>' . _('You have a Gold account') . '</h3>';

    if ($user->is_anonymous) {
        echo '<p class="warning">' . _('You risk losing your Gold account because you have not created an user account. Please create an account to make sure it does not suddenly disappear.') . '</p>';
    }

    echo '<p>' . sprintf(_('Your Gold account expires %s.'),
        '<time datetime="' . date(DateTime::ATOM, strtotime($user->info->gold_account_expires)) . '">'
        . $engine->formatTime($user->language, $user, strtotime($user->info->gold_account_expires)) .'</time>') . '</p>';
}

echo '
<h3>' . _('Activate Gold account') . '</h3>
<div class="gold-key">
    <input type="text" placeholder="' . _('Gold account key') . '" />
    <button data-e="goldKeyActivate" class="linkbutton">' . _('Activate') . '</button>
</div>';

if ($user->hasGoldAccount) {
    echo '
<p class="info">' . _('Your Gold account duration will be extended with the length of the key.') . '</p>';
}

echo '
<p><a class="linkbutton" href="' . $engine->cfg->goldAccountLink . '">' . _('Purchase a Gold account') . '</a></p>';

// Unused gold keys
$unused_keys = $user->getUnusedGoldKeys();

if (!empty($unused_keys)) {
    echo '<h3>' . _('Your user account has unused gold keys') . '</h3>';
    foreach ($unused_keys AS $key): ?>
        <div class="gold-key">
            <input type="text" value="<?= htmlspecialchars($key['key']) ?>" />
            <button class="linkbutton" data-e="goldKeyActivate"><?= _('Activate') ?></button>
            <button class="linkbutton" data-e="goldKeyCopy"><?= _('Copy') ?></button>
            <p>
                <?= sprintf(_('Length: %s'), $user->goldLengthToHumanReadable($key['length'])) ?>,
                <?= sprintf(_('Expires: %s'), '<time datetime="' . date(DateTime::ATOM, strtotime($key['expires'])) . '">'
                    . $engine->formatTime($user->language, $user, strtotime($key['expires'])) .'</time>') ?>,
                <?= sprintf(_('Donatable: %s'), !$key['is_donated'] ? _('Yes') : _('No')) ?>
            </p>
        </div>
    <?php endforeach;
}

echo '
</div>';

echo '
<div id="tags" class="tab">
    <div class="tag-preview-flex">';

    foreach ($engine->cfg->postTags as $key => $tag) {
        if (!$tag['obtainable'] || !$tag['listed']) {
            continue;
        }
        $usable = false;
        if ($user->hasTag($key)) {
            $usable = true;
        }
        $display = ' ' . str_replace('[NAME]', _($tag['name']), $tag['display']);

        echo '
            <div class="tag-preview-box' . ($usable ? '' : ' not-usable') . '">
                <h4>' . _($tag['name']) . '</h4>
                <div class="tag-preview">' . $display . '</div>
                <p>' . _($tag['description']) . '</p>
            </div>';
    }

    echo '
    </div>
    <h2>' . _('These tags are no longer obtainable') . '</h2>
    <div class="tag-preview-flex">';
    foreach ($engine->cfg->postTags as $key => $tag) {
        if ($tag['obtainable'] || !$tag['listed']) {
            continue;
        }
        $usable = false;
        if ($user->hasTag($key)) {
            $usable = true;
        }
        $display = ' ' . str_replace('[NAME]', _($tag['name']), $tag['display']);

        echo '
            <div class="tag-preview-box' . ($usable ? '' : ' not-usable') . '">
                <h4>' . _($tag['name']) . '</h4>
                <div class="tag-preview">' . $display . '</div>
                <p>' . _($tag['description']) . '</p>
            </div>';
    }

    echo '
    </div>
</div>';

// SESSION MANAGEMENT
echo '
<div id="sessions" class="tab">
<h3>' . _('Active logins') . '</h3>

<style nonce="' . SCRIPT_NONCE . '">
table { margin-top: 10px }
table td { padding: 2px 5px }
</style>

<table>
<tr>
    <th>' . _('IP-address') . '</th>
    <th>' . _('Login time') . '</th>
    <th>' . _('Last activity') . '</th>
    <th>' . _('Actions') . '</th>
</th>
';

    $sessions = $user->getSessions($user->id);
    foreach ($sessions AS $session) {
        echo '
    <tr>
        <td>' . $session['login_ip'] . '</td>
        <td><time datetime="' . date(DateTime::ATOM, $session['login_time']) . '">'
            . $engine->formatTime($user->language, $user, $session['login_time']) .'</time></td>
        <td><time datetime="' . date(DateTime::ATOM, $session['last_active']) . '">'
            . $engine->formatTime($user->language, $user, $session['last_active']) .'</time></td>
        <td>';
        if ($user->session_id == $session['session_id']) {
            echo '<em>' . _('Current session') . '</em>';
        } else {
            echo '
            <a class="linkbutton" data-e="deleteSession" session_id="' . $session['session_id'] . '">' . _('Log out') . '</a>';
        }
        echo '</td>
    </tr>';
    }

    echo '
</table>
<p><button class="linkbutton" data-e="deleteSessions">' . _('Log out from other places') . '</button></p>

</div>';

echo '</div>';
