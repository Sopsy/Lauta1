<?php

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'posts' => '',
    'user' => false,
    'board' => [false, false, false],
];
include '../../inc/engine.class.php';
new Engine($loadClasses);

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Bad request'));
}

if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(401);
    die(_('Your session has expired. Please refresh the page and try again.'));
}

$post = $posts->getPost($_POST['id'], true);
if (!$post) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    die(_('Post does not exist'));
}

?>
<form name="reportform" class="reportform async-form" action="/scripts/ajax/report.php" method="post">
    <input type="hidden" name="postId" value="<?= (int)$_POST['id'] ?>" />
    <label for="reason"><?= _('How is this post violating the rules?') ?></label>
    <select name="reason" id="reason" required>
        <option disabled selected hidden value=""><?= _('Please select') ?></option>
        <?php
        foreach ($engine->cfg->ruleOptionsB AS $ruleKey => $ruleOption) {
            if (!is_array($ruleOption)) {
                echo '<option>' . htmlspecialchars($ruleOption) . '</option>';
            } else {
                if (!empty($ruleOption['boards']) && is_array($ruleOption['boards'])) {
                    if (!empty($post)) {
                        if (in_array('!' . $post['url'], $ruleOption['boards']) || (!in_array('*',
                                    $ruleOption['boards']) && (!in_array($post['url'],
                                        $ruleOption['boards'])))
                        ) {
                            continue;
                        }
                    }
                }
                echo '<optgroup label="' . htmlspecialchars($ruleKey) . '">';
                foreach ($ruleOption AS $optionKey => $optionValue) {
                    if ($optionKey === 'boards') {
                        continue;
                    }
                    if (!empty($optionValue['skipReportForm']) && $optionValue['skipReportForm']) {
                        continue;
                    }
                    echo '<option';
                    if (is_array($optionValue)) {
                        $optionValue = $optionKey;
                    }
                    echo '>' . htmlspecialchars($optionValue) . '</option>';
                }
                echo '</optgroup>';
            }
        }
        ?>
    </select>
    <label for="reasonadd"><?= _('Additional details') ?></label>
    <input type="text" name="reasonadd" id="reasonadd" placeholder="<?= _('Optional') ?>"  maxlength="160" />
    <div class="buttons">
        <button class="linkbutton" type="submit"><?= _('Submit') ?></button>
    </div><?php
        if($user->useCaptcha()) {
            echo '<p class="protectedbyrecaptcha">' . $engine->getCaptchaText() . '</p>';
        }
    ?>
</form>
