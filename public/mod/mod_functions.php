<?php

class ModFunctions
{

    public static function timeQuicklinks($elm_name, $times = false)
    {
        if (!$times) {
            $times = [
                3600 => sprintf(_('%dh'), 1),
                10800 => sprintf(_('%dh'), 3),
                21600 => sprintf(_('%dh'), 6),
                43200 => sprintf(_('%dh'), 12),
                86400 => sprintf(_('%dd'), 1),
                259200 => sprintf(_('%dd'), 3),
                604800 => sprintf(_('%dw'), 1),
                1209600 => sprintf(_('%dw'), 2),
                2592000 => sprintf(_('%dm'), 1),
            ];
        }

        $ret = '';
        foreach ($times as $time => $name) {
            $ret .= '<button type="button" class="pure-button pure-u-1-12" data-elm="' . $elm_name . '" data-time="' . $time . '" data-e="timeQuickLink">' . $name . '</button> ';
        }

        return $ret;
    }

    public static function boardSelect($name = 'board', $class = 'pure-input-1', $required = false, $multiple = false)
    {
        global $db;

        $ret = '<select class="' . $class . '" name="' . $name . ($multiple ? '[]' : '') . '" id="' . $name . '"' . ($required ? ' required' : '') . ($multiple ? ' multiple' : '') . '>';
        if (!$multiple) {
            $ret .= '<option value="" disabled selected>' . _('Choose') . '</option>';
        }

        $qb = $db->q("SELECT `id` AS boardid, `url`, `name` AS boardname FROM `board` ORDER BY `name` ASC");
        while ($board = $qb->fetch_assoc()) {
            $ret .= '<option value="' . $board['boardid'] . '">' . $board['boardname'] . '</option>';
        }

        $ret .= '</select>';

        return $ret;
    }

    public static function printInfo($str, $die = false)
    {
        echo '<h2>' . $str . '</h2>';
        if ($die) {
            die();
        }
    }

    public static function printError($str, $die = true)
    {
        echo '<h2 class="error">' . $str . '</h2>';
        if ($die) {
            die();
        }
    }
}