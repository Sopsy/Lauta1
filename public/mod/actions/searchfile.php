<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '<h1>Viestihaku</h1>';

echo '<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
<fieldset>
<label for="file">Tiedoston osoite</label>
<input type="text" class="wide-input" id="file" name="file" value="' . (!empty($_POST['file']) ? $_POST['file'] : (!empty($_GET['file']) ? $_GET['file'] : '')) . '" /><br />
<input type="submit" value="Etsi" />
</fieldset>
</form>';

if (!empty($_POST['file'])) {
    $file = explode('/', $_POST['file']);
    $filename = array_pop($file);
    $filename = explode('.', $filename, 2)[0];
    $filename = $db->escape($filename);
    $filename = preg_replace('/([a-z0-9A-Z])(\..*)?/', '$1', $filename);

    $boards = $db->q("SELECT `name`, `id` AS boardid FROM `board`");
    $boards = $db->fetchAll($boards);
    $boardNames = [];
    foreach ($boards AS $thisBoard) {
        $boardNames[$thisBoard['boardid']] = $thisBoard['name'];
    }

    $q = $db->q("SELECT `post_id` FROM `post_file` WHERE `file_id` = " . (int)base_convert($filename, 36, 10));
    $foundPosts = $q->fetch_all(MYSQLI_NUM);
    $foundPosts = array_map('current', $foundPosts);

    if (empty($foundPosts)) {
        die('<p>Ei viestejä</p>');
    }

    echo '
	<table class="list">
		<tr><th>Lauta</th><th>Aika</th><th>Viesti</th></tr>';

    foreach ($foundPosts AS $post) {
        $post = $posts->getPost($post, true);

        if (empty($boardNames[$post['board']])) {
            $boardNames[$post['board']] = '(tuntematon)';
        }

        echo '
		<tr>
		<td>' . $boardNames[$post['board']] . '</td>
		<td>' . date('d.m.Y H:i:s', $post['time']) . '</a></td>
		<td><a href="' . $engine->cfg->siteUrl . '/scripts/redirect.php?id=' . $post['id'] . '" class="ref" data-id="' . $post['id'] . '">&gt;&gt;' . $post['id'] . '</a></td>
		</tr>';
    }
    echo '
	</table>';
}