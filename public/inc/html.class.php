<?php

class html
{

    protected $ads = false;
    protected $boardList;

    public function __construct($skipDestruct = false)
    {
        $this->skipDestruct = $skipDestruct;
    }

    public function __destruct()
    {
        if (!$this->skipDestruct) {
            $this->printFooter();
        }
    }

    public function printHeader($title = "Ylilauta", $threadOpen = false, $threadId = false)
    {
        global $engine, $user, $db, $board, $thread;

        if (!empty($user)) {
            $user->incrementStats('total_pageloads');
        }

        $style = $engine->cfg->defaultStyle;
        if (!empty($user->getPreferences('style')) && array_key_exists($user->getPreferences('style'),
                $engine->cfg->availableStyles)
        ) {
            $style = $user->getPreferences('style');
        }

        if (!empty($user)) {
            $this->locale = $user->language;
        } else {
            $this->locale = $engine->cfg->fallbackLanguage;
        }
        $this->domain = 'default';

        header('Link: <' . $engine->cfg->staticUrl . '>; rel=preconnect', false);
        header('Link: <' . $engine->cfg->thumbsUrl . '>; rel=preconnect', false);
        ?>
        <!DOCTYPE html>
        <html lang="fi">

        <head>
            <meta name="robots" content="index, follow, noarchive">
            <meta name="viewport" content="width=device-width,initial-scale=1">

            <script nonce="<?= SCRIPT_NONCE ?>">
                window.user = {csrfToken: '<?= !empty($user->session) ? $user->session->csrf_token : '' ?>'};
                window.captchaPublicKey = '<?= htmlspecialchars($engine->cfg->reCaptchaPublicKey, ENT_QUOTES | ENT_HTML5) ?>';
            </script>

            <script src="<?= $engine->cfg->staticUrl ?>/js/1.5/Locale/<?= $this->locale ?>.<?= $this->domain ?>.js" defer></script>
            <script type="module" src="<?= $engine->cfg->staticUrl ?>/js/1.21/Bootstrap.js"></script>
            <script nomodule src="<?= $engine->cfg->staticUrl ?>/js/1.4/old-browser-warning.js" defer></script>
            <link href="<?= $engine->cfg->staticUrl ?>/css/1.5/icons.css" rel="stylesheet">
            <link href="<?= $engine->cfg->staticUrl ?>/css/1.11/<?= $style ?>.css" rel="stylesheet">

            <?php if ($thread && !empty($thread['message'])): ?>
                <meta property="og:description" content="<?= htmlspecialchars(mb_substr($thread['message'], 0, 200)) ?>">
            <?php elseif ($board && !empty($board->info)): ?>
                <meta property="og:description" content="<?= htmlspecialchars($board->info['description']) ?>">
            <?php else: ?>
                <meta property="og:description" content="Ylilauta on anonyymi keskustelufoorumi. Ylilaudalle voit kirjoittaa aiheesta kuin aiheesta ilman rekisteröitymistä. Keskusteluja ei sensuroida turhaan.">
            <?php endif ?>
            <meta property="og:image" content="<?= $engine->cfg->staticUrl ?>/img/logo/norppa.png">
            <link rel="icon" sizes="192x192" href="<?= $engine->cfg->staticUrl ?>/img/logo/norppa_icon.png">
            <link rel="apple-touch-icon" sizes="192x192" href="<?= $engine->cfg->staticUrl ?>/img/logo/norppa_icon.png">
            <meta name="mobile-web-app-capable" content="yes">
            <meta name="theme-color" content="<?= $engine->cfg->availableStyles[$style]['color'] ?>">
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="black">
            <title><?= htmlspecialchars($title) ?></title>

            <?php
            if ($user->hasGoldAccount && !empty($user->getPreferences('custom_css'))) {
                echo '<style nonce="' . SCRIPT_NONCE . '">' . str_replace('</style>', '', $user->getPreferences('custom_css')) . '</style>';
            }
            if ($user->getPreferences('hide_images')) {
                echo '<style nonce="' . SCRIPT_NONCE . '">.thumb img {opacity:0.03}.thumb img:hover {opacity:0.15}</style>';
            }

            if (empty($board)) {
                $board = new stdClass();
            }
            if (empty($board->info['boardid'])) {
                $board->info['boardid'] = false;
            }
            if ($user->useCaptcha()) {
                echo '<script nonce="' . SCRIPT_NONCE . '" src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>';
            }
            if (!empty($board->info['description']) && !empty($_GET['page']) && $_GET['page'] == 1): ?>
                <meta name="description" content="<?= htmlspecialchars($board->info['description']) ?>">
            <?php endif ?>
        </head>
        <body<?= ($threadId?' data-threadid="' . $threadId . '"':'') . ($user->getPreferences('hide_sidebar')?' class="no-sidebar"':'') ?>>
    <?php if (!$user->getPreferences('hide_ads')) : ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-12345678-9"></script>
        <script nonce="<?= SCRIPT_NONCE ?>">
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'UA-12345678-9', {'anonymize_ip': true});
        </script>
    <?php endif;

    }

    public function printFooter()
    {
        global $engine;

        echo '
        </div>
        </body>
        </html>';

        if (!empty($engine)) {
            echo '<!-- ', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4), 's -->';
        }
    }

    public function printThreadFollowBox($ajax = false)
    {
        global $engine, $user, $db;

        if (empty($user) || $user->isLimited) {
            return;
        }

        if (!$user->getPreferences('follow_show_floatbox')) {
            return;
        }

        $followedThreads = $user->getFollowedThreads();

        if ($ajax !== true) {
            echo '
    <div id="followedthreads">
        <h4 id="followedtitle">' . _('Followed threads') . '</h4>
        <div id="followedbg">
            <div id="followedcontent">';
        }

        if (count($followedThreads) == 0) {
            echo '
            <p>' . _('You don\'t have any followed threads.') . '</p>';
        } else {
            $i = 0;
            foreach ($followedThreads AS $thread) {
                $unreadColor = '';
                if ($thread['unread_count'] != 0) {
                    $unreadColor = ' class="followedunread"';
                }

                $lastSeenReply = '';
                if (!empty($user->info->followedThreads[$thread['thread_id']]['last_seen_reply'])) {
                    $lastSeenReply = '#no' . $user->info->followedThreads[$thread['thread_id']]['last_seen_reply'];
                }

                echo '
            <p class="thread" data-thread-id="' . $thread['thread_id'] . '">
                <button class="linkbutton" data-e="threadUnfollowFromBox"><span class="icon-cross"></span></button>
                <a href="/' . $thread['url'] . '/' . $thread['thread_id'] . $lastSeenReply . '">
                    ' . (empty($thread['subject']) ? $thread['thread_id'] : htmlspecialchars($thread['subject'])) . '
                </a>
                <span' . $unreadColor . '>(' . $thread['unread_count'] . ')</span>
            </p>';
                ++$i;
                if ($i >= 100) {
                    break;
                }
            }
        }

        if ($ajax !== true) {
            echo '
                </div>
            <p id="followedfunctions">
                <button class="linkbutton" data-e="threadClearFollowed">' . _('Clear all') . '</button>
                <button class="linkbutton" data-e="threadHideFollowBox">' . _('Hide') . '</button>
                <button class="linkbutton" data-e="threadUpdateFollowBox">' . _('Update') . '</button>
            </p>
        </div>
    </div>';
        }
    }

    public function printSidebar($boardList = true, $threadOpen = false)
    {
        global $engine, $user, $db, $board;
        echo '
    <div id="sidebar" data-e="shadowHide">
        <a class="logo" href="/"><img src="' . $engine->cfg->staticUrl . $engine->cfg->siteLogoUrl . '" alt=""> ' . $engine->cfg->siteName . '</a>
        <nav class="meta">
            <a href="' . $engine->cfg->goldAccountLink . '">Kultatili</a>
            <a href="/?saannot">Säännöt</a>
            <a href="/?tietoa">Tietoa</a>
            <a href="/?english">In English</a>
            <a href="https://tv-opas.ylilauta.org/" target="_blank">TV-opas</a>
            <a href="https://meemi.info/" target="_blank" rel="nofollow noopener">Meemi.info</a>
        </nav>';

        if (!$user->loggedIn) {
            echo '
        <nav>
            <form class="login" name="login" action="/scripts/ajax/login.php" method="post" data-e="submitForm">
                <input type="text" name="username" placeholder="' . _('Username') . '" autocomplete="username" required>
                <input type="password" name="password" placeholder="' . _('Password') . '" autocomplete="current-password" required>
                <div class="buttons">
                    <button type="button" class="linkbutton" data-e="createAccount">' . _('Create account') . '</button>
                    <button type="submit" class="linkbutton">' . _('Log in') . '</button>
                </div>
                <button type="button" class="buttonlink" data-e="forgotPassword">' . _('Forgot password?') . '</button>
            </form>
            <form name="register" class="login" action="/scripts/ajax/register.php" method="post" data-e="submitForm" hidden>
                <input type="text" name="username" placeholder="' . _('Username') . '" autocomplete="username" maxlength="' . $engine->cfg->nameMaxLength . '" required>
                <input type="password" name="password" pattern=".{6,}" placeholder="' . _('Password') . '" autocomplete="current-password" title="' . sprintf(_('At least %d characters'), 6) . '" required>
                <input type="password" name="passwordconfirm" placeholder="' . _('Password confirmation') . '" required>
                <input type="email" name="email" placeholder="' . _('Email (optional)') . '" maxlength="1000">
                <div class="buttons">
                    <button type="button" class="linkbutton" data-e="cancelCreateAccount">' . _('Cancel') . '</button>
                    <button type="submit" class="linkbutton">' . _('Create') . '</button>
                </div>';
                if($user->useCaptcha()) {
                    echo '<p class="protectedbyrecaptcha">' . $engine->getCaptchaText() . '</p>';
                }
                echo '
            </form>
            </nav>
            <nav class="user">
            ';
        } else {
            echo '
            <nav class="user">
            <h4>' . sprintf(_('Logged in as %s'), '<a href="/preferences?profile">' . htmlspecialchars($user->info->username) . '</a>') . '</h4>';
        }

        echo '<div class="sq-buttons">
                <a class="button square" href="/preferences" title="' . _('Preferences') . '"><span class="icon-cog"></span></a>';
        if ($user->isMod) {
            echo '<a title="' . _('Management') . '" href="/mod/"><span class="icon-badge"></span></a>';
        }
        if ($user->loggedIn) {
            echo '<button title="' . _('Log out') . '" data-e="logout"><span class="icon-exit"></span></button>';
        }
        echo '</div>';

        echo '<a href="/preferences?profile">' . _('Activity points') . ': ' . number_format($user->activity_points, 0, ',', ' ') . '</a>';
        if ($user->isMod) {
            $importantReports = $db->q("SELECT post_id FROM post_report WHERE cleared = 0 GROUP BY post_id HAVING COUNT(*) > 5 LIMIT 1");
            $importantReports = $importantReports->num_rows;

            if ($user->unread_reports != 0 AND $user->hasPermissions("reportedposts")) {
                echo '<a href="/mod/index.php?action=reportedposts" class="bold' . ($importantReports ? ' red' : '') . '">' .
                    ($user->unread_reports == 1 ? _('1 report') : sprintf(_('%s reports'), $user->unread_reports)) . '</a>';
            }
            if ($user->unread_ban_appeals != 0 AND $user->hasPermissions("banappeals")) {
                echo '<a href="/mod/index.php?action=banappeals" class="bold">' .
                    ($user->unread_ban_appeals == 1 ? _('1 ban appeal') : sprintf(_('%s ban appeals'), $user->unread_ban_appeals)) . '</a>';
            }
        }

        echo '
        </nav>
        <nav class="customized">';
        $extraLinks = $this->extraLinks();
        foreach ($extraLinks AS $link => $data) {
            echo '
                <a href="' . $link . '" class="' . ($data[1] ?: '') . ($data[2] ? ' bold' : '') . '">' . $data[0] . '</a>';
        }
        echo '
        </nav>';

        if ($boardList) {
            echo '<nav class="boardlist">';

            foreach ($this->getBoardList() as $singleBoard) {
                echo '<a href="/' . $singleBoard['url'] . '/"' . ($singleBoard['is_hidden'] ? ' class="hidden"' : '') . '><span>' . $singleBoard['boardname'] . '</span></a>';
            }
            echo '</nav>';
        }
        echo '
        </div>
        <div class="wrapper">
        <div id="topbar">
            <button id="e-sidebar-toggle" data-e="sidebarToggle" class="icon-menu"></button>
            <button id="e-sidebar-hide" data-e="sidebarHide"></button>
            <div id="boardselector" data-e="toggleBoardSelector"><span>';

        if (empty($board->info['url'])) {
            echo _('Boards');
        } else {
            echo htmlspecialchars($board->info['boardname']);
        }

        $mv = $db->get_mv(['onlinecount']);
        $onlineCount = json_decode($mv->onlinecount, true);

        echo '</span><div><nav>';
        foreach ($this->getBoardList() as $singleBoard) {
            if (empty($onlineCount[$singleBoard['boardid']])) {
                $boardOnlineCount = 0;
            } else {
                $boardOnlineCount = $onlineCount[$singleBoard['boardid']];
            }

            $boardOnlineCount .= ' ' . ($boardOnlineCount != 1 ? _('readers') : _('reader'));

            echo '<a href="/' . $singleBoard['url'] . '/">'
                . htmlspecialchars($singleBoard['boardname']);

            if ($user->hasGoldAccount) {
                echo '<span class="online-count">' . $boardOnlineCount . '</span>';
            }

            echo '</a>';
        }
        echo '</nav></div></div>
            <div class="right">';
            if ($threadOpen) {
                echo '<a href="/' . $board->info['url'] . '/" title="' . _('Return to board') . '"><span class="icon-arrow-left"></span></a>';
            }
            echo '
                <button class="icon-alarm notifications-button" data-e="notificationsOpen" title="' . _('Notifications') . '">
                    <span class="unread-notifications' . ($user->unread_notifications == 0 ? ' none' : '') . '">' . ($user->unread_notifications <= 99 ? $user->unread_notifications : ':D') . '</span>
                </button>
                <button data-e="scrollToBottom" class="icon-enter-down2" title="' . _('Go to bottom') . '"></button>
                <button data-e="scrollToTop" class="icon-enter-up2" title="' . _('Back to top') . '"></button>
                <button data-e="pageReload" class="icon-sync" title="' . _('Refresh page') . '"></button>
            </div>
        </div>';

        $this->printThreadFollowBox();
    }

    public function extraLinks()
    {
        global $engine, $user;

        // Url => [name, class, bold]
        $array = [];
        $array['/allthreads'] = [_('All threads'), false, false];
        $array['/followedthreads'] = [_('Followed threads'), false, false];
        $array['/mythreads'] = [_('My threads'), false, false];
        $array['/repliedthreads'] = [_('Replied threads'), false, false];
        $array['/hiddenthreads'] = [_('Hidden threads'), false, false];

        return $array;
    }

    public function getBoardList($showHidden = false, $order = true)
    {
        global $db, $user;

        if (isset($this->boardList) && !$showHidden) {
            return $this->boardList;
        }
        if (isset($this->boardListWithHidden) && $showHidden) {
            return $this->boardListWithHidden;
        }

        $q = $db->q("SELECT `id` AS boardid, `description`, `name` AS boardname, `url`, `is_hidden` FROM `board` FORCE INDEX (name) ORDER BY `name` ASC");
        $boardList = $q->fetch_all(MYSQLI_ASSOC);

        if (empty($boardList)) {
            return false;
        }

        $newBoardList = [];
        foreach ($boardList AS $board) {
            if (!$showHidden && in_array($board['boardid'], $user->getHiddenBoards())) {
                continue;
            }
            if (!$user->hasGoldAccount AND $board['is_hidden']) {
                continue;
            }
            if (!$user->hasPlatinumAccount AND $board['url'] == 'platina') {
                continue;
            }

            $board['skipMenus'] = false;
            $newBoardList[] = $board;
        }
        if (!$showHidden) {
            $this->boardList = $newBoardList;
        } else {
            $this->boardListWithHidden = $newBoardList;
        }

        return $newBoardList;
    }

    public function printBoardheader($custHeader = false, $custSubHeader = false)
    {
        global $board, $user, $db, $engine;

        echo '
        <div class="boardheader">
            <h1>' . (!$custHeader ? '<a href="/' . $board->info['url'] . '/">' . $board->info['boardname'] . '</a>' : $custHeader) . '</h1>
            <h2>' . (!$custSubHeader ? $board->info['description'] : $custSubHeader) . '</h2>
        </div>';
    }

    public function printPostForm($threadId = 0)
    {
        global $engine, $board, $user, $db;

        if (!empty($board->info) && isset($board->info['is_locked']) && $board->info['is_locked'] && !$user->isMod) {
            return true;
        }

        if ( !$user->hasGoldAccount && (
                !empty($board->info['url']) && $board->info['url'] === 'sekalainen' && $engine->antispam() && $user->account_created > (time() - $engine->antispam())
        )) {
            $hours = ceil($engine->antispam() / 3600);
            echo '<p class="infobar">' .
                sprintf(_('Temporary spam protection active: You will need to wait %d hour(s) or login to an older user account to post.'), $hours) .
                '</p>';

            return true;
        }

        if ($user->isBanned) {
            echo '<p class="infobar">' . _('You are banned.') . ' <a href="/banned">' . _('Click for more info...') . '</a></p>';

            return true;
        }

        if ($threadId == 0) {
            $isReply = false;
        } else {
            $isReply = true;
        }

        if (!$isReply) {
            echo '<button class="linkbutton" id="display_postform" data-e="postFormShow">' . _('Create thread') . '</button>';
        }

        echo '
    <div id="postform">
    <form id="post" name="post" class="' . ($isReply ? 'reply' : 'create') . '" action="/post" method="post"
        enctype="multipart/form-data" data-e="postSubmit">
        <input name="thread" id="thread" value="' . $threadId . '" type="hidden">
        <input name="author" id="author" type="hidden">';

        if (!empty($board->info['boardid']) && !$isReply) {
            echo '
        <input name="board" id="board" value="' . $board->info['url'] . '" type="hidden">';
        }

        if (!$isReply) {
            echo '
                <div class="row" id="row-subject">
                    <label class="label" for="subject">' . _('Subject') . '</label>
                    <input name="subject" id="subject" minlength="4" maxlength="' . $engine->cfg->subjectMaxLength . '" type="text" placeholder="' . _('Subject (optional)') . '">
                </div>';

            if (empty($board->info['url'])) {
                echo '
                <div class="row" id="row-board">
                    <label class="label" for="board">' . _('Board') . '</label>
                    <select name="board" id="board" required>
                        <option selected>' . _('Choose board') . '</option>';

                        foreach ($this->getBoardList() AS $singleBoard) {
                            if (!empty($singleBoard['skipMenus']) && $singleBoard['skipMenus']) {
                                continue;
                            }
                            echo '<option value="' . $singleBoard['url'] . '">' . $singleBoard['boardname'] . '</option>';
                        }

                echo '
                    </select>
                </div>';
            }
        }

        echo '
        <div class="row" id="row-file">
            <label class="label" for="file">' . _('File') . '</label>
            <input name="file" id="file" type="file" accept=".' . implode(',.', array_keys($engine->cfg->allowedFiletypes)) . '" data-max-size="' . (int)$engine->cfg->maxFileSize . '" data-e="checkFile';
            echo '">
        </div>';

        echo '
        <div class="row" id="postbuttons">
            <button type="button" class="icon-bold" data-e="addBbCode" data-code="b"></button>
            <button type="button" class="icon-italic" data-e="addBbCode" data-code="em"></button>
            <button type="button" class="icon-strikethrough" data-e="addBbCode" data-code="s"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
            <button type="button" class="icon-underline" data-e="addBbCode" data-code="u"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
            <button type="button" class="icon-eye-crossed" data-e="addBbCode" data-code="spoiler"></button>
            <button type="button" class="icon-plus" data-e="addBbCode" data-code="big"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
            <button type="button" class="icon-minus" data-e="addBbCode" data-code="small"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
            <button type="button" class="icon-bubble-quote" data-e="addBbCode" data-code="quote"></button>
            <button type="button" class="icon-code" data-e="addBbCode" data-code="code"></button>
            <button type="button" class="icon-superscript" data-e="addBbCode" data-code="sup"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
            <button type="button" class="icon-subscript" data-e="addBbCode" data-code="sub"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
            <button type="button" class="icon-palette" data-e="toggleColorButtons"' . (!$user->hasGoldAccount?' disabled':'') . '></button>
        </div>
        <div class="row" id="color-buttons">
            <button type="button" class="icon-palette black" data-e="addBbCode" data-code="black"></button>
            <button type="button" class="icon-palette gray" data-e="addBbCode" data-code="gray"></button>
            <button type="button" class="icon-palette white" data-e="addBbCode" data-code="white"></button>
            <button type="button" class="icon-palette brown" data-e="addBbCode" data-code="brown"></button>
            <button type="button" class="icon-palette red" data-e="addBbCode" data-code="red"></button>
            <button type="button" class="icon-palette orange" data-e="addBbCode" data-code="orange"></button>
            <button type="button" class="icon-palette yellow" data-e="addBbCode" data-code="yellow"></button>
            <button type="button" class="icon-palette green" data-e="addBbCode" data-code="green"></button>
            <button type="button" class="icon-palette blue" data-e="addBbCode" data-code="blue"></button>
            <button type="button" class="icon-palette purple" data-e="addBbCode" data-code="purple"></button>
            <button type="button" class="icon-palette pink" data-e="addBbCode" data-code="pink"></button>
        </div>';

        echo '<textarea spellcheck="false" autocomplete="off" name="msg" id="msg" maxlength="' . $engine->cfg->messageMaxLength . '" placeholder="' . _('Message') . '"';

        echo '></textarea>';
        echo '
        <div class="row" id="row-buttons">
            <button type="button" class="linkbutton" data-e="togglePostOptions">' . _('Options') . '</button>
            <button id="submit-btn" class="linkbutton" type="submit">' . _('Submit') . '</button>
        </div>

    <div id="postoptions">
        <div class="col">
            <h4>' . _('Post settings') . '</h4>
            <label><input type="checkbox" name="show_username" data-e="toggleUsername"' . ($user->getPreferences('show_username') ? ' checked' : '') . '> ' . _('Show username') . '</label>
            <label><input type="checkbox" name="show_filename"> ' . _('Show filename') . '</label>
            <h4>' . _('Gold account functions') . '</h4>
            <label><input type="checkbox" name="goldhide"' . (!$user->hasGoldAccount ? ' disabled' : '') . '> ' . _('Make post visible only with a Gold account') . '</label>';

        if ($user->isMod) {
            echo '
                <h4>' . _('Moderator functions') . ' ' . _('(for new threads)') . '</h4>
                <label><input name="lockthread" type="checkbox"> ' . _('Lock thread') . '</label>
                <label><input name="stickthread" type="checkbox"> ' . _('Stick thread') . '</label>
                <label><input type="checkbox" name="admin_tag"> ' . _('Show admin-tag') . '</label>';
        }

        echo '
        </div>
        <div class="col">
            <h4>' . _('Tags') . '</h4>';
        if (empty($user->tags())) {
            echo '<p>' . _('You don\'t have any tags') . '</p>';
        } else {
            echo '<div class="taglist">';
            foreach ($engine->cfg->postTags as $tagId => $tag) {
                if (!$user->hasTag($tagId)) {
                    continue;
                }
                echo '
                <label title="' . _($tag['name']) . '">
                    <input type="checkbox" name="posttag[' . $tagId . ']">
                    <span class="tag-container">' . str_replace('[NAME]', _($tag['name']), $tag['display']) . '</span>
                </label>';
            }
            echo '</div>';
        }
        echo '
            <p><a class="linkbutton" href="/preferences?tags">' . _('How to get tags?') . '</a></p>
        </div>
    </div>';
        if($user->useCaptcha()) {
            echo '<p class="protectedbyrecaptcha">' . $engine->getCaptchaText() . '</p>';
        }
    echo '</form></div>';
    }

    public function printNavigationBar($top = true, $page = false, $pageCount = false, $urls = [], $names = [])
    {
        global $engine, $board, $user;

        echo '<div class="navigationbar ' . ($top ? 'top' : 'bottom') . '">';

        if (count($urls) == count($names) && count($urls) != 0 && count($names) != 0) {
            echo '<p class="navigatebar"><a href="/">' . $engine->cfg->siteName . '</a>';

            $url = '/';
            for ($i = 0; $i < count($urls); ++$i) {
                $url .= htmlspecialchars($urls[$i]);
                echo ' &raquo; <a href="' . $url . '">' . htmlspecialchars($names[$i]) . '</a>';
            }

            echo '</p>';
        }

        if ($top && count($urls) == 2) {
            echo '<div class="replybutton"><button class="linkbutton" data-e="postFormFocus">' . _('Reply to thread') . '</button></div>';
        }

        if ($page) {
            echo '
        <div class="pagination_container">';
            $this->printBoardPagination($page, $pageCount, (empty($urls[0]) ? false : $urls[0]));
            echo '</div>';
        }

        echo '</div>';
    }

    public function printBoardPagination($curPage, $pageCount = false, $url = false)
    {
        global $engine, $user;

        // Used to prevent double ids
        $ids = !isset($this->paginationPrinted);
        $this->paginationPrinted = true;

        if ($url === false) {
            return;
        }

        $catalog = false;
        $url_suffix = '';
        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
            $catalog = true;
            $url_suffix = '/';
        }

        echo '
        <nav class="pagination">
            <span class="pages">';

        if ($curPage != 1) {
            echo '<a' . ($ids ? ' id="prev"' : '') . ' class="previous icon-chevron-left" href="/' . $url . (($curPage - 1) != 1 ? '-' . ($curPage - 1) : '') . $url_suffix . '"></a>';
        }

        if ($pageCount > $curPage + 3) {
            if ($curPage < 3) {
                $pages = (3 - $curPage) + $curPage + 3;
            } else {
                $pages = $curPage + 3;
            }
        } else {
            $pages = $pageCount;
        }

        if ($curPage - 2 < 1) {
            $startIterator = 1;
        } else {
            $startIterator = $curPage - 2;
        }

        // Prepare page navigation
        if ($startIterator >= 2) {
            echo '<a href="/' . $url . $url_suffix . '">1</a>';
        }
        if ($startIterator >= 3) {
            echo '<span class="dots">...</span>';
        }

        for ($i = $startIterator; $i <= $pages; ++$i) {
            if ($curPage == $i) {
                echo '<span class="cur">' . $i . '</span>';
            } else {
                echo '<a href="/' . $url . ($i != 1 ? '-' . $i : '') . $url_suffix . '">' . $i . '</a>';
            }
        }
        if ($pageCount > $pages) {
            echo '<span class="dots">...</span>';
        }

        $nexttxt = _('Next');
        if ($curPage != $pageCount) {
            echo '<a' . ($ids ? ' id="next"' : '') . ' class="next icon-chevron-right" href="/' . $url . '-' . ($curPage + 1) . $url_suffix . '"></a>';
        }

        echo '
            </span
            ><span class="display-styles"
                ><a data-e="changeDisplayStyle" data-style="0" class="button icon-list4"></a
                ><a data-e="changeDisplayStyle" data-style="1" class="button icon-grid"></a
                ><a data-e="changeDisplayStyle" data-style="2" class="button icon-text-align-justify"></a
            ></span>
        </nav>';
    }

    public function printThread($thread, $threadOpen = false, $boardInfoUrl = false, $boardInfoName = false)
    {
        global $engine, $db, $posts, $board, $user;

        $extraClasses = '';
        foreach ($user->info->followedThreads as $followThread) {
            if ($followThread['thread_id'] == $thread['id']) {
                $extraClasses .= ' followed';
                break;
            }
        }

        if ($user->getHiddenThreads() && in_array($thread['id'], $user->getHiddenThreads())) {
            $extraClasses .= ' hidden';
        }
        if ($user->id === $thread['user_id']) {
            $extraClasses .= ' own-thread';
        }

        echo '<div id="t' . $thread['id'] . '" class="thread' . $extraClasses . '" data-thread-id="' . $thread['id'] . '" data-board="' . $board->info['url'] . '" data-newest-reply="' . $thread['lastReplyId'] . '">';

        if ($user->getDisplayStyle() !== 'style-compact') {
            if (!empty($boardInfoUrl) && !empty($boardInfoName)) {
                echo '<p class="thread-boardinfo"><a href="/' . $boardInfoUrl . '/">' . $boardInfoName . '</a></p>';
            }
        }

        // Print first post
        if ($threadOpen || $user->getDisplayStyle() != 'style-box') {

            echo '<div class="postsubject"><a  href="/' . $board->info['url'] . '/' . $thread['id'] . '">';

            if ($thread['is_locked']) {
                echo '<span class="thread-icon icon-lock"></span>';
            }
            if ($thread['is_sticky']) {
                echo '<span class="thread-icon icon-pushpin"></span>';
            }

            if ($thread['op_post']) {
                if ($thread['op_post']['gold_get']) {
                    echo '<span class="thread-icon icon-medal-empty"></span>';
                }
                if ($thread['op_post']['upvote_count'] > 10) {
                    echo '<span class="thread-icon icon-pointer-upright"></span>';
                }
            }

            echo '<span class="subject">' . htmlspecialchars($thread['subject']) . '</span></a>';

            if ($thread['user_id'] !== $user->id) {
                echo '<button class="icon-button thread-hide icon-minus" data-e="threadHide" title="' . _('Hide thread') . '"></button>';
                echo '<button class="icon-button thread-unhide icon-plus" data-e="threadUnhide" title="' . _('Restore thread') . '"></button>';
            }
            echo '<button class="icon-button thread-follow icon-eye" data-e="threadFollow" title="' . _('Follow thread') . '"></button>';
            echo '<button class="icon-button thread-unfollow icon-eye-crossed" data-e="threadUnfollow" title="' . _('Remove from followed') . '"></button>';

            if ($user->isMod) {
                echo '<button class="icon-button icon-hammer-wrench" data-e="threadManage" title="' . _('Manage thread') . '"></button>';
            }

            if ($thread['user_id'] === $user->id || $user->isMod) {
                echo '<button class="icon-button icon-trash2" data-e="threadDelete" title="' . _('Delete thread') . '"></button>';
            }

            echo '</div>';

            if ($user->getDisplayStyle() !== 'style-compact' || $threadOpen) {
                if ($thread['op_post']) {
                    $this->printPost($thread['op_post'], false, $threadOpen, false, true);
                } else {
                    echo '<div class="postinfo">';
                    echo '<time class="posttime" datetime="' . date(DateTime::ATOM, strtotime($thread['time'])) . '">';
                    echo $engine->formatTime($user->language, $user, strtotime($thread['time']));
                    echo '</time>';
                    echo '<span class="op-deleted">' . _('Original post deleted') . '</span>';
                    echo '</div>';
                }
            } elseif ($user->getDisplayStyle() === 'style-compact' || !$threadOpen) {
                echo '<div class="postinfo">';
                echo '<time class="posttime" datetime="' . date(DateTime::ATOM, strtotime($thread['time'])) . '">';
                echo $engine->formatTime($user->language, $user, strtotime($thread['time']));
                echo '</time>';
                echo '</div>';
            }
        } else {
            $this->printThreadlistPost($thread, $boardInfoUrl, $boardInfoName);
        }

        // Thread info
        echo '<div class="thread-info">';
        if ($thread['reply_count'] == 0) {
            echo _('No replies');
        } else {
            if ($thread['reply_count'] == 1) {
                echo _('One reply');
            } else {
                printf(_('%d replies'), $thread['reply_count']);
            }

            if ($thread['distinct_reply_count'] == 1) {
                echo ' ' . _('from a single user');
            } else {
                printf(' ' . _('from %d users'), $thread['distinct_reply_count']);
            }
        }
        echo ', ' . sprintf(($thread['read_count'] == 1 ? _('opened once') : _('opened %d times')), $thread['read_count']);

        if ($user->hasGoldAccount) {
            $extraInfo = ', ' . sprintf(($thread['follow_count'] == 1 ? _('one follower') : _('%d followers')),
                    $thread['follow_count']) . ',
            ' . sprintf(($thread['hide_count'] == 1 ? _('hidden by one user') : _('hidden by %d users')),
                    $thread['hide_count']);

            echo '<span class="extrainfo">' . $extraInfo . '</span>';
        }

        echo '</div>';

        if ($threadOpen || $user->getDisplayStyle() == 'style-replies') {
            // More replies -button
            if ($thread['reply_count'] > count($thread['replies'])) {
                echo '
                <div class="more-replies">
                    <a class="morereplies linkbutton" href="/' . $board->info['url'] . '/' . $thread['id'] . ($thread['lastReplyId'] ? '#no' . $thread['lastReplyId'] : '') .'">' . _('Show earlier replies') . '</a>
                    <span class="reply-count-visible">' . count($thread['replies']) . '</span>/<span class="reply-count-total">' . $thread['reply_count'] . '</span>
                </div>';
            }

            // Print replies
            echo '<div class="answers">';

            $i = 0;
            foreach ($thread['replies'] as $reply) {
                if ($i == 10) {
                    $this->print_ad($board->info['url'], 5);
                }

                if ($i > 20 && $i == count($thread['replies']) - 10) {
                    $this->print_ad($board->info['url'], 6);
                }
                $this->printPost($reply, true, $threadOpen);
                ++$i;
            }
            if ($threadOpen && $i <= 10) {
                $this->print_ad($board->info['url'], 5);
            }

            echo '</div>';

            if (!$board->info['is_locked'] && !$threadOpen) {
                echo '
                <div class="threadbuttons">
                    <div class="buttons_left">
                        <a class="linkbutton" href="/' . $board->info['url'] . '/' . $thread['id'] . ($thread['lastReplyId'] ? '#no' . $thread['lastReplyId'] : '') .'">' . _('Open') . '</a>
                    </div>
                </div>';
            }
        }
        echo '
    </div>';
    }

    public function printThreadlistPost($post, $boardInfoUrl = null, $boardInfoName = null)
    {
        global $engine, $db, $board, $posts, $user, $thread;

        $gold_get = '';
        if ($post['gold_get'] >= 1) {
            $gold_get = ' gold-get';
        }

        $own_post = $post['user_id'] == $user->id ? ' own_post' : '';
        echo '<div class="post ' . $own_post . $gold_get . '" data-id="' . $post['post_id'] . '">';
        echo '<div class="postheader">';

        echo '<a class="postsubject" href="/' . $board->info['url'] . '/' . $post['id'] . '">';

        if ($post['is_locked']) {
            echo '<span class="thread-icon icon-lock"></span>';
        }
        if ($post['is_sticky']) {
            echo '<span class="thread-icon icon-pushpin"></span>';
        }

        echo '<span class="subject">' . htmlspecialchars($post['subject']) . '</span></a>';
        echo '<div class="postinfo">';
        if (empty($post['post_id'])) {
            echo '<span class="op-deleted">' . _('Original post deleted') . '</span>';
        }

        echo '<div class="right">';
        echo '<span class="upvote_count" data-count="' . $post['upvote_count'] . '">' . ($post['upvote_count'] != 0 ? '+' . $post['upvote_count'] : '')
            . '<span class="gold_get">' . ($post['gold_get'] == 0 ? '' : ' (+' . $post['gold_get'] . ')') . '</span></span>';

        echo '<div class="messageoptions">';

        if ($thread['user_id'] !== $user->id) {
            echo '<button class="icon-button thread-hide icon-minus" data-e="threadHide" title="' . _('Hide thread') . '"></button>';
            echo '<button class="icon-button thread-unhide icon-plus" data-e="threadUnhide" title="' . _('Restore thread') . '"></button>';
        }
        echo '<button class="icon-button thread-follow icon-eye" data-e="threadFollow" title="' . _('Follow thread') . '"></button>';
        echo '<button class="icon-button thread-unfollow icon-eye-crossed" data-e="threadUnfollow" title="' . _('Remove from followed') . '"></button>';

        if (!empty($post['post_id']) && $post['user_id'] !== $user->id && !$user->isMod) {
            echo '<button class="icon-button icon-flag2" data-e="postReportForm" title="' . _('Report message') . '"></button>';
        }
        if (!empty($post['post_id']) && $user->isMod) {
            echo '<button class="icon-button icon-hammer2" data-e="postBanUser" title="' . _('Ban user') . '"></button>';
        }
        echo '<button class="icon-button icon-share2" data-e="postShare" title="' . _('Share') . '"></button>';

        echo '</div></div></div></div>';

        $goldHide = false;
        if ($post['gold_hide'] && !$user->hasGoldAccount) {
            $post['message'] = $posts->scramble($post['message']);
            $goldHide = true;
        }

        echo '<blockquote class="message' . ($goldHide?' goldhide':'') . '">';

        if (!empty($post['fileid'])) {
            $this->printFile($post, false);
        }

        echo '<div class="postcontent">';

        $posts->printMessage($post['message'], $post['id'], $post['id']);

        echo '</div></blockquote>';

        if ($post['gold_hide']) {
            echo '<div class="goldhide-info"><a href="' . $engine->cfg->goldAccountLink . '">' . _('This message is only visible to users with a Gold account.') . '</a></div>';
        }

        echo '</div>';
    }

    public function printPost($post, $isReply, $threadOpen = false, $ajaxPreview = false, $printFile = true)
    {
        global $engine, $db, $board, $posts, $user, $thread;

        $gold_get = '';
        if ($post['gold_get'] >= 1) {
            $gold_get = ' gold-get';
        }
        if (!$ajaxPreview) {
            $own_post = $post['user_id'] == $user->id ? ' own_post' : '';

            echo '
        <div data-id="' . $post['id'] . '" class="post ' . ($isReply ? 'answer' : 'op_post') . $own_post . $gold_get . '"' . (!$ajaxPreview ? ' id="no' . $post['id'] : '') . '">';
        }

        $threadId = $post['thread_id'];

        echo '<div class="postinfo"><div class="left">';

        // Flags
        if ($post['admin_post'] == 1) {
            // Admins
            $post['country_code'] = 'ADM';
        }
        if ($board->info['show_flags'] && !empty($post['country_code'])) {
            $csrc = strtolower($post['country_code']) . '.png';

            // Does the flag exist?
            $src = $engine->cfg->flagsDir . '/' . $csrc;

            if (!is_file($engine->cfg->staticDir . $src)) {
                $src = $engine->cfg->flagsDir . '/unk.png';
            }

            $src = $engine->cfg->staticUrl . $src;
            if (array_key_exists($post['country_code'], $engine->cfg->countryNames)) {
                $countryName = $engine->cfg->countryNames[$post['country_code']];
            } else {
                $countryName = $engine->cfg->countryNames['UNK'];
            }

            $flagAlt = $countryName . ' (' . $post['country_code'] . ')';
            echo '<img class="flag" src="' . $src . '" alt="' . $flagAlt . '" title="' . $flagAlt . '"> ';
        }

        if (!empty($post['name'])) {
            echo '<span class="postername">' . htmlspecialchars($post['name']) . '</span>';
        }

        if ($post['admin_post'] == 1) {
            echo '<span class="tag text admin">' . _('Administration') . '</span>';
        }

        // Thread specific user ID
        if ($post['admin_post'] != 1) {
            if ($post['public_user_id'] == 0) {
                echo '<span class="postuid op">' . _('OP') . '</span>';
            } else {
                echo '<span class="postuid">' . base_convert($post['public_user_id'], 10, 36) . '</span>';
            }
        }

        $event = ' data-e="postReply"';
        if (!$threadOpen) {
            $event = '';
        }
        if (!empty($board->info['url'])) {
            echo '<a class="postnumber" href="/' . $board->info['url'] . '/' . $threadId . '#no' . $post['id'] . '"' . $event . ' data-id="' . $post['id'] . '">' . _('No.') . ' ' . $post['id'] . '</a>';
        } else {
            echo '<span class="postnumber">' . _('No.') . ' ' . $post['id'] . '</span>';
        }

        echo '<time class="posttime" datetime="' . date(DateTime::ATOM, $post['time']) . '">';
        echo $engine->formatTime($user->language, $user, $post['time']);
        echo '</time>';

        // Message edited
        if (!empty($post['edited']) && (strtotime($post['edited']) - 60) > $post['time']) {
            echo '<a class="message-edited" data-e="postShowEdits" title="' . _('Show edit history') . '">(' . _('edited') . ')</a>';
        }

        // Post tags
        if (!empty($post['post_tags'])) {
            echo '<div class="tags">';
            $postTags = explode(',', $post['post_tags']);
            foreach ($engine->cfg->postTags as $tagId => $tag) {
                if (!in_array($tagId, $postTags)) {
                    continue;
                }
                echo str_replace('[NAME]', _($tag['name']), $tag['display']);
            }
            echo '</div>';
        }

        // Right
        echo '</div><div class="right">';

        echo '<span class="upvote_count" data-count="' . $post['upvote_count'] . '">' . ($post['upvote_count'] != 0 ? '+' . $post['upvote_count'] : '')
            . '<span class="gold_get">' . ($post['gold_get'] == 0 ? '' : ' (+' . $post['gold_get'] . ')') . '</span></span>';

        if (!$ajaxPreview) {
            echo '<button class="icon-button icon-reply" data-e="postReply" data-id="' . $post['id'] . '" title="' . _('Reply') . '"></button>';
            echo '<button class="icon-button messageoptions_mobile icon-menu" data-e="postToggleOptions"></button>';
            echo '<div class="messageoptions">';

            if ($post['user_id'] != $user->id) {
                echo '<button class="icon-button icon-pointer-upright" data-e="postUpvote" title="' . _('Upboat') . '"></button>';
                echo '<button class="icon-button icon-medal-empty" data-e="postDonateGold" title="' . _('Donate gold') . '"></button>';
            }

            echo '<button class="icon-button post-hide icon-minus" data-e="postHide" title="' . _('Hide post') . '"></button>';
            echo '<button class="icon-button post-unhide icon-plus" data-e="postUnhide" title="' . _('Restore post') . '"></button>';

            if ($user->isMod || ($post['user_id'] === $user->id)) {
                echo '<button class="icon-button icon-pencil-line" data-e="postEdit" title="' . _('Edit message') . '"></button>';
            }

            if ($post['user_id'] !== $user->id && !$user->isMod) {
                echo '<button class="icon-button icon-flag2" data-e="postReportForm" title="' . _(
                        'Report message'
                    ) . '"></button>';
            } else {
                echo '<button class="icon-button icon-trash2" data-e="postDelete" title="' . _(
                        'Delete message'
                    ) . '"></button>';
            }

            if ($user->isMod) {
                echo '<button class="icon-button icon-hammer2" data-e="postBanUser" title="' . _('Ban user') . '"></button>';
            }
            if ($user->isSuperMod) {
                echo '<button class="icon-button icon-badge" data-e="postToggleModBar" title="' . _('Moderator functions') . '"></button>';
            }

            echo '<button class="icon-button icon-share2" data-e="postShare" title="' . _('Share') . '"></button>';
            echo '</div>';
        }
        echo '</div></div>';

        if ($user->isSuperMod && ($threadOpen || $user->getDisplayStyle() == 'style-replies')) {
            $modUrl = '/mod/index.php';
                echo '
            <div class="modbar">
                ' . _('IP') . ':
                ' . ($user->hasPermissions("displaypostip") ? '
                    <span class="userip" id="ip' . $post['id'] . '">' . inet_ntop($post['ip']) . (!empty($post['remote_port']) ? ':' . $post['remote_port'] : '') . ' (' . $post['country_code'] . ')</span> UID: ' . $post['user_id'] . ' |' : '') . '
                ' . ($user->hasPermissions("deletepost") ? '<a title="' . _('Delete message') . '" href="' . $modUrl . '?action=deletepost&amp;id=' . $post['id'] . '">D</a> |' : '') . '
            </div>';
        }

        $goldHide = false;
        if ($post['gold_hide'] && !$user->hasGoldAccount) {
            $post['message'] = $posts->scramble($post['message']);
            $goldHide = true;
        }

        echo '<blockquote class="message' . ($goldHide?' goldhide':'') . '">';

        if ($printFile && !empty($post['fileid'])) {
            $this->printFile($post, $threadOpen);
        }

        echo '<div class="postcontent">';

        $posts->printMessage($post['message'], $post['id'], $post['op_post_id']);

        echo '</div></blockquote>';

        if ($post['gold_hide']) {
            echo '<div class="goldhide-info"><a href="' . $engine->cfg->goldAccountLink . '">' . _('This message is only visible to users with a Gold account.') . '</a></div>';
        }

        if (!empty($post['post_replies'])) {
            echo '<div class="replies"><span>' . _('Replies') . ':</span>';
            foreach (explode(',', $post['post_replies']) as $post_reply) {
                echo '
                <a data-id="' . $post_reply . '" href="/scripts/redirect.php?id=' . $post_reply . '" class="ref backlink">&gt;&gt;' . $post_reply . '</a>';
            }

            echo '</div>';
        }

        if (!$ajaxPreview) {
            echo '</div>';
        }
    }

    public function printFile($post, $threadOpen = false)
    {
        global $engine, $user, $board, $user;

        if (!$threadOpen && $user->getDisplayStyle() == 'style-compact') {
            return true;
        }

        if ($post['gold_hide'] && !$user->hasGoldAccount) {
            echo '
            <figure class="post-file thumb">
                <img src="' . $engine->cfg->staticUrl . '/img/kultatili.jpg" alt="">
            </figure>';
            return true;
        }

        $data = '';

        $fileName = str_pad(base_convert($post['fileid'], 10, 36), 5, '0', STR_PAD_LEFT);

        if (in_array($post['extension'], ['jpg', 'jpeg', 'png'])) {
            $fileSource = $engine->cfg->filesUrl . '/' . $fileName . '.' . $post['extension'];
        } elseif (in_array($post['extension'], ['mp3', 'm4a', 'aac', 'mp4'])) {
            $fileSource = $engine->cfg->videosUrl . '/' . $fileName . '.' . $post['extension'];
        }

        $thumbnail = false;
        if (in_array($post['extension'], ['jpg', 'jpeg', 'png'])) {
            $thumbnail = $engine->cfg->thumbsUrl . '/' . $fileName . '.' . $post['extension'];
            $thumbnail2x = $engine->cfg->thumbsUrl . '/' . $fileName . '-480.' . $post['extension'];
            $thumbnail3x = $engine->cfg->thumbsUrl . '/' . $fileName . '-720.' . $post['extension'];
            $data = ' data-e="expand"';
        } elseif (in_array($post['extension'], ['mp3', 'm4a', 'aac', 'mp4'])) {
            if ($post['extension'] == 'mp4') {
                $thumbnail = $engine->cfg->thumbsUrl . '/' . $fileName . '.jpg';
                $thumbnail2x = $engine->cfg->thumbsUrl . '/' . $fileName . '-480.jpg';
                $thumbnail3x = $engine->cfg->thumbsUrl . '/' . $fileName . '-720.jpg';
            }
            $data = ' data-e="playMedia" data-file-id="' . $post['id'] . '"';
        }

        // Threadlist
        if (!$threadOpen && $user->getDisplayStyle() == 'style-box') {
            $fileSource = '/' . $board->info['url'] . '/' . $post['thread_id'];
            $data = '';
        }

        echo '<figure class="post-file thumb">';
        echo '<a href="' . $fileSource . '" class="file-content ' . htmlspecialchars($post['extension'], ENT_QUOTES | ENT_HTML5) . '" ' . $data . '>';

        if ($thumbnail) {
            echo '<img class="file-data" src="' . $thumbnail . '" ';
            if (isset($thumbnail2x, $thumbnail3x)) {
                echo 'srcset="' . $thumbnail . ' 1x, ' . $thumbnail2x . ' 2x, ' . $thumbnail3x . ' 3x" ';
            }
            echo 'alt="" loading="lazy"/>';
        } else {
            echo '<span class="file-data icon-floppy-disk"></span>';
        }

        if ($threadOpen || $user->getDisplayStyle() == 'style-replies') {
            if (in_array($post['extension'], ['mp3', 'm4a', 'aac', 'mp4'])) {
                if ($post['extension'] == 'mp4' || $post['extension'] == 'mp3' || $post['extension'] == 'm4a') {
                    echo '<span class="overlay center icon-play4"></span>';
                    echo '<span class="overlay bottom left">' . $engine->formatDuration($post['duration']);

                    if ($post['has_sound'] || $post['extension'] == 'mp3' || $post['extension'] == 'm4a') {
                        echo ' <span class="icon-volume-high"></span>';
                    } else {
                        echo ' <span class="icon-mute"></span>';
                    }
                    echo '</span>';
                }
            }
            /*
            elseif (in_array($post['extension'], ['jpg', 'png'])) {
                $folder = "{$fileName[0]}/{$fileName[1]}/$fileName[2]";
                [$width, $height] = getimagesize("{$engine->cfg->filesDir}/{$folder}/{$fileName}.{$post['extension']}");
                echo '<span class="overlay bottom left">' . $width . '&times;' . $height . '</span>';
            }
            */
        }

        echo '</a>';

        if (($user->getDisplayStyle() == 'style-replies' || $threadOpen) && !empty($post['orig_name'])) {
            echo '<figcaption><span>' . htmlspecialchars($post['orig_name']) . '</span></figcaption>';
        }

        echo '</figure>';
    }

    public function print_ad($area_id, $position_id)
    {
        global $user;

        if (!empty($user) && $user->getPreferences('hide_ads')) {
            return false;
        }

        $ad = $this->get_ad($area_id, $position_id);
        if (!$ad) {
            return false;
        }

        $ad['html'] = str_replace('[TIMESTAMP]', time(), $ad['html']);
        $ad['html'] = str_replace('[AREAID]', rawurlencode($area_id), $ad['html']);

        echo $ad['html'];

        return null;
    }

    protected function get_ad($area_id, $position_id)
    {
        if ($this->ads === false) {
            $this->ads = $this->load_ads();
        }

        if (empty($this->ads[$position_id])) {
            return false;
        }

        $ads = $this->ads[$position_id];

        foreach ($ads as $key => $ad) {
            // Don't display on specific areas
            if (in_array($area_id, $ad['not_areas'])) {
                unset($ads[$key]);
                continue;
            }

            // Display on all areas if empty
            if (empty($ad['areas'])) {
                continue;
            }

            // Display only on specific areas
            if (in_array($area_id, $ad['areas'])) {
                continue;
            }

            // No match, don't display
            unset($ads[$key]);
        }

        $ads = array_values($ads);

        if (empty($ads)) {
            return false;
        }

        $ad = $ads[array_rand($ads)];

        return $ad;
    }

    protected function load_ads()
    {
        global $db;

        // Ads
        $adq = $db->q('SELECT id, position_id, areas, not_areas, html FROM ads WHERE disabled = 0');

        $adArray = [];
        while ($row = $adq->fetch_assoc()) {
            $positions = explode(',', $row['position_id']);
            foreach ($positions AS $position) {
                if (!isset($adArray[$position])) {
                    $adArray[$position] = [];
                }

                $adArray[$position][] = [
                    'id' => $row['id'],
                    'html' => $row['html'],
                    'areas' => empty($row['areas']) ? [] : explode(',', $row['areas']),
                    'not_areas' => empty($row['not_areas']) ? [] : explode(',', $row['not_areas']),
                ];
            }
        }

        return $adArray;
    }

    public function printModbar()
    {
        global $user;

        echo '
    <div id="sidebar">
        <nav>
            <h4>' . _('Management') . '</h4>
            ' . ($user->hasPermissions("bulletinboard") ? '<a href="/mod/index.php">' . _('Bulletin board') . '</a>' : '') . '
        </nav>
        <nav>
            ' . ($user->hasPermissions("reportedposts") ? '<a href="/mod/index.php?action=reportedposts">' . _('Reported posts') . ' (' . $user->unread_reports . ')</a>' : '') . '
            ' . ($user->hasPermissions("banappeals") ? '<a href="/mod/index.php?action=banappeals">' . _('Ban appeals') . ' (' . $user->unread_ban_appeals . ')</a>' : '') . '
            ' . ($user->hasPermissions("addban") ? '<a href="/mod/index.php?action=addban">' . _('Ban a user') . '</a>' : '') . '
            ' . ($user->hasPermissions("managebans") ? '<a href="/mod/index.php?action=managebans">' . _('Manage bans') . '</a>' : '') . '
        </nav>
        <nav>
            ' . ($user->hasPermissions("admins") ? '<a href="/mod/index.php?action=admins">' . _('Moderator list') . '</a>' : '') . '
            ' . ($user->hasPermissions("modlog") ? '<a href="/mod/index.php?action=modlog">' . _('Moderation log') . '</a>' : '') . '
            ' . ($user->hasPermissions("postcounts") ? '<a href="/mod/index.php?action=postcounts">' . _('Message amounts') . '</a>' : '') . '
        </nav>
        <nav>
            ' . ($user->hasPermissions("managegold") ? '<a href="/mod/index.php?action=managegold">' . _('Manage Gold accounts') . '</a>' : '') . '
        </nav>
        <nav>
            ' . ($user->hasPermissions("manageboards") ? '<a href="/mod/index.php?action=manageboards">' . _('Manage boards') . '</a>' : '') . '
            ' . ($user->hasPermissions("autobans") ? '<a href="/mod/index.php?action=autobans">' . _('Blacklisted words') . '</a>' : '') . '
            ' . ($user->hasPermissions("searchfile") ? '<a href="/mod/index.php?action=searchfile">' . _('File search') . '</a>' : '') . '
            ' . ($user->hasPermissions("deletedposts") ? '<a href="/mod/index.php?action=deletedposts">' . _('Deleted posts') . '</a>' : '') . '
            ' . ($user->hasPermissions("deletepost") ? '<a href="/mod/index.php?action=deletepost">' . _('Delete a thread or a post') . '</a>' : '') . '
            ' . ($user->hasPermissions("updatethread") ? '<a href="/mod/index.php?action=updatethread">' . _('Update thread options') . '</a>' : '') . '
            ' . ($user->hasPermissions("poststream") ? '<a href="/mod/index.php?action=poststream">' . _('Post stream') . '</a>' : '') . '
            ' . ($user->hasPermissions("multipleposts") ? '<a href="/mod/index.php?action=multipleposts">' . _('Search multiple posts') . '</a>' : '') . '
            ' . ($user->hasPermissions("userposts") ? '<a href="/mod/index.php?action=userposts">' . _('Search user posts') . '</a>' : '') . '
            ' . ($user->hasPermissions("spamguard") ? '<a href="/mod/index.php?action=spamguard">' . _('New users') . '</a>' : '') . '
            ' . ($user->hasPermissions("spamguard") ? '<a href="/mod/index.php?action=lockedusers">' . _('Locked users') . '</a>' : '') . '
        </nav>
    </div>';
    }
}
