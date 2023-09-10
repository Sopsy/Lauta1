<?php
if (!defined('ALLOWLOAD')) {
    die();
}

echo '
<h1>Hallitse lautoja</h1>';

if (empty($_POST)) {
    $qb = $db->q("SELECT `id`, `url`, `name` FROM `board` ORDER BY `name` ASC");
    $boards = $db->fetchAll($qb);

    echo '
	<h2>Luo uusi lauta</h2>
	<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
	<fieldset>
		<input type="hidden" name="csrf_token" value="' . $user->csrf_token . '" />
		<input type="hidden" name="action" value="create" />
		<label for="url" class="wide">Hakemisto</label>
		<input type="text" name="url" id="url" />
		<p>Sallitut merkit: a-z 0-9</p><br />
		<label for="boardname" class="wide">Nimi</label>
		<input type="text" name="boardname" id="boardname" /><br />
		<label for="description" class="wide">Kuvaus</label>
		<input type="text" name="description" id="description" /><br />
		<label for="ishidden" class="wide">Piilolauta</label>
		<input type="checkbox" name="ishidden" id="ishidden" /><br />
		<label for="locked" class="wide">Lukittu</label>
		<input type="checkbox" name="locked" id="locked" /><br />
		<label for="delete_hours" class="wide">Tunteja, kunnes inaktiivinen lanka poistetaan</label>
		<input type="text" name="delete_hours" id="delete_hours" value="730" /><br />
		<label for="showflags" class="wide">Näytä liput</label>
		<input type="checkbox" name="showflags" id="showflags" /><br />
		<input type="submit" value="Luo lauta" />
	</fieldset>
	</form>


	<h2>Muokkaa lautaa</h2>
	<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
	<fieldset>
		<input type="hidden" name="action" value="edit" />
		<label for="board" class="wide">Valitse lauta</label>
		<select name="board" id="board">
		';

    foreach ($boards AS $singleBoard) {
        echo '
			<option value="' . $singleBoard['id'] . '">' . $singleBoard['name'] . '</option>';
    }

    echo '
		</select><br />
		<input type="submit" value="Jatka" />
	</fieldset>
	</form>

	<h2>Poista lauta</h2>
	<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
	<fieldset>
		<input type="hidden" name="action" value="delete" />
		<label for="board" class="wide">Valitse lauta</label>
		<select name="board" id="board">
		';

    foreach ($boards AS $singleBoard) {
        echo '
			<option value="' . $singleBoard['id'] . '">' . $singleBoard['name'] . '</option>';
    }

    echo '
		</select><br />
		<input type="submit" value="Poista" />
	</fieldset>
	</form>';
} elseif (!empty($_POST['action']) AND $_POST['action'] == 'create') {
    if (empty($_POST['csrf_token']) OR !hash_equals($user->csrf_token, $_POST['csrf_token'])) {
        die('<h2>Virheellinen tarkiste</h2>');
    }

    if (empty($_POST['url']) OR empty($_POST['boardname'])) {
        die('<h2>Laudan nimi puuttuu</h2>');
    } else {
        $boardurl = preg_replace('/[^a-z0-9]/', '', strtolower($_POST['url']));
        $boardurl = $db->escape($boardurl);
        $boardname = $db->escape(htmlspecialchars($_POST['boardname']));
    }

    $q = $db->q("SELECT `id` FROM `board` WHERE `url` = '" . $boardurl . "' LIMIT 1");
    if ($q->num_rows == 1) {
        die('<h2>Hakemisto ' . $boardurl . ' on jo olemassa</h2>');
    }

    if (empty($_POST['description'])) {
        $description = '';
    } else {
        $description = $db->escape(htmlspecialchars($_POST['description']));
    }

    if (!empty($_POST['ishidden']) AND $_POST['ishidden'] == 'on') {
        $ishidden = 1;
    } else {
        $ishidden = 0;
    }

    if (!empty($_POST['locked']) AND $_POST['locked'] == 'on') {
        $locked = 1;
    } else {
        $locked = 0;
    }

    if (!isset($_POST['delete_hours']) OR !is_numeric($_POST['delete_hours']) OR $_POST['delete_hours'] < 0) {
        $delete_hours = 'NULL';
    } else {
        $delete_hours = (int)$_POST['delete_hours'];
    }

    if (!empty($_POST['showflags']) AND $_POST['showflags'] == 'on') {
        $showflags = 1;
    } else {
        $showflags = 0;
    }

    $q = $db->q("
		INSERT INTO `board`
			(`url`, `name`, `description`, `is_hidden`, `inactive_hours_delete`, `is_locked`, `show_flags`)
		VALUES
			('" . $boardurl . "', '" . $boardname . "', '" . $description . "', " . $ishidden . ",
			" . $delete_hours . ", " . $locked . ", " . $showflags . ")
	");

    if ($q) {
        echo '<h2>Lauta lisätty</h2><p><a href="' . $_SERVER['REQUEST_URI'] . '">Palaa takaisin</a></p>';
    } else {
        echo '<h2>Tietokantavirhe</h2>';
    }
} elseif (!empty($_POST['action']) AND $_POST['action'] == 'edit' AND !empty($_POST['board']) AND is_numeric($_POST['board'])) {

    $q = $db->q("SELECT * FROM `board` WHERE `id` = '" . $db->escape($_POST['board']) . "' LIMIT 1");
    if ($q->num_rows == 1) {
        $boardinfo = $q->fetch_assoc();

        if (empty($_POST['csrf_token'])) {
            echo '
			<h2>Muokkaa lautaa</h2>
			<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			<fieldset>
				<input type="hidden" name="csrf_token" value="' . $user->csrf_token . '" />
				<input type="hidden" name="board" value="' . $boardinfo['id'] . '" />
				<input type="hidden" name="action" value="edit" />
				<label for="url" class="wide">Hakemisto</label>
				<input type="text" name="url" id="url" value="' . $boardinfo['url'] . '" />
				<p>Sallitut merkit: a-z 0-9</p><br />
				<label for="boardname" class="wide">Nimi</label>
				<input type="text" name="boardname" id="boardname" value="' . $boardinfo['name'] . '" /><br />
				<label for="description" class="wide">Kuvaus</label>
				<input type="text" name="description" id="description" value="' . $boardinfo['description'] . '" /><br />
				<label for="ishidden" class="wide">Piilolauta</label>
				<input type="checkbox" name="ishidden" id="ishidden"' . ($boardinfo['is_hidden'] ? ' checked="checked"' : '') . ' /><br />
				<label for="locked" class="wide">Lukittu</label>
				<input type="checkbox" name="locked" id="locked"' . ($boardinfo['is_locked'] ? ' checked="checked"' : '') . ' /><br />
				<label for="delete_hours" class="wide">Tunteja, kunnes inaktiivinen lanka poistetaan</label>
				<input type="text" name="delete_hours" id="delete_hours" value="' . $boardinfo['inactive_hours_delete'] . '" /><br />
				<label for="showflags" class="wide">Näytä liput</label>
				<input type="checkbox" name="showflags" id="showflags"' . ($boardinfo['show_flags'] ? ' checked="checked"' : '') . ' /><br />
				<input type="submit" value="Tallenna" />
			</fieldset>
			</form>
			<p><a href="' . $_SERVER['REQUEST_URI'] . '">Palaa takaisin</a></p>';
        } else {
            if (empty($_POST['csrf_token']) OR !hash_equals($user->csrf_token, $_POST['csrf_token'])) {
                die('<h2>Virheellinen tarkiste</h2>');
            }

            if (empty($_POST['board']) OR !is_numeric($_POST['board'])) {
                die('<h2>Virheellinen lauta</h2>');
            }

            $board = $db->escape($_POST['board']);

            if (empty($_POST['boardname'])) {
                die('<h2>Laudan nimi puuttuu</h2>');
            } else {
                $boardname = $db->escape(htmlspecialchars($_POST['boardname']));
            }

            if (empty($_POST['url']) OR empty($_POST['boardname'])) {
                die('<h2>Laudan nimi tai lyhytnimi puuttuu</h2>');
            } else {
                $boardurl = preg_replace('/[^a-z0-9]/', '', strtolower($_POST['url']));
                $boardurl = $db->escape($boardurl);
                $boardname = $db->escape(htmlspecialchars($_POST['boardname']));
            }

            $q = $db->q("SELECT `id` FROM `board` WHERE `url` = '" . $boardurl . "' AND id != '" . $board . "' LIMIT 1");
            if ($q->num_rows == 1) {
                die('<h2>Hakemisto ' . $boardurl . ' on jo olemassa</h2>');
            }

            if (empty($_POST['description'])) {
                $description = '';
            } else {
                $description = $db->escape(htmlspecialchars($_POST['description']));
            }

            if (!empty($_POST['ishidden']) AND $_POST['ishidden'] == 'on') {
                $ishidden = 1;
            } else {
                $ishidden = 0;
            }

            if (!empty($_POST['locked']) AND $_POST['locked'] == 'on') {
                $locked = 1;
            } else {
                $locked = 0;
            }

            if (!isset($_POST['delete_hours']) OR !is_numeric($_POST['delete_hours']) OR $_POST['delete_hours'] < 0) {
                $delete_hours = 'NULL';
            } else {
                $delete_hours = (int)$_POST['delete_hours'];
            }

            if (!empty($_POST['showflags']) AND $_POST['showflags'] == 'on') {
                $showflags = 1;
            } else {
                $showflags = 0;
            }

            $q = $db->q("
				UPDATE `board` SET
				`url` = '" . $boardurl . "',
				`name` = '" . $boardname . "',
				`description` = '" . $description . "',
				`is_hidden` = " . $ishidden . ",
				`inactive_hours_delete` = " . $delete_hours . ",
				`is_locked` = " . $locked . ",
				`show_flags` = " . $showflags . "
				WHERE `id` = '" . $board . "' LIMIT 1");

            if ($q) {
                echo '<h2>Muutokset tallennettu</h2><p><a href="' . $_SERVER['REQUEST_URI'] . '">Palaa takaisin</a></p>';
            } else {
                echo '<h2>Tietokantavirhe</h2>';
            }
        }
    } else {
        echo '<h2>Virheellinen lauta</h2>';
    }

} elseif (!empty($_POST['action']) AND $_POST['action'] == 'delete') {
    if (empty($_POST['board']) OR !is_numeric($_POST['board'])) {
        die('<h2>Virheellinen lauta</h2>');
    }

    if (empty($_POST['confirm'])) {

        $board = $db->escape($_POST['board']);
        $q = $db->q("SELECT `name` AS boardname FROM `board` WHERE `id` = '" . $board . "' LIMIT 1");
        if ($q->num_rows == 0) {
            die('<h2>Lautaa ei ole olemassa</h2>');
        }
        $boardname = $q->fetch_assoc();
        $boardname = $board['boardname'];

        echo '
		<h2>Poista lauta</h2>
		<form class="modform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<input type="hidden" name="csrf_token" value="' . $user->csrf_token . '" />
			<input type="hidden" name="board" value="' . $_POST['board'] . '" />
			<input type="hidden" name="action" value="delete" />
			<input type="hidden" name="confirm" value="true" />
			<p>Haluatko varmasti poistaa laudan ' . $boardname . '?</p><br />
			<input type="submit" value="Poista" />
		</fieldset>
		</form>
		<p><a href="' . $_SERVER['REQUEST_URI'] . '">Palaa takaisin</a></p>';
    } else {
        if (empty($_POST['csrf_token']) OR !hash_equals($user->csrf_token, $_POST['csrf_token'])) {
            die('<h2>Virheellinen tarkiste</h2>');
        }

        $board = $db->escape($_POST['board']);

        $q = $db->q("SELECT `url` FROM `board` WHERE `id` = '" . $board . "' LIMIT 1");
        if ($q->num_rows == 0) {
            die('<h2>Lautaa ei ole olemassa</h2>');
        }
        $boardurl = $q->fetch_assoc();
        $boardurl = $boardurl['url'];

        $q = $db->q("SELECT id FROM thread WHERE board_id = " . (int)$board . " LIMIT 1");
        $threads = $db->escape(implode(',', $db->fetchAll($q, 'id')));

        if (!empty($threads)) {
            $threadsDeleted = $posts->deleteThreads($threads);
        } else {
            $threadsDeleted = true;
        }

        if ($threadsDeleted) {
            $q = $db->q("DELETE FROM `board` WHERE `id` = '" . $board . "' LIMIT 1");
            if ($q) {
                echo '<h2>Lauta poistettu</h2><p><a href="' . $_SERVER['REQUEST_URI'] . '">Palaa takaisin</a></p>';
            } else {
                echo '<h2>Tietokantavirhe</h2>';
            }
        }
    }
}
