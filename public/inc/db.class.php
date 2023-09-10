<?php

class db extends mysqli
{

    public function __construct()
    {
        global $engine;

        // I don't think we will ever need more than one server, but it's always
        // good to have support!
        $err_rep = error_reporting(0);
        $this->mysql0 = new Mysqli($engine->cfg->dbHost, $engine->cfg->dbUser, $engine->cfg->dbPass,
            $engine->cfg->dbName);

        // If connection failed
        if ($this->mysql0->connect_errno) {
            error_log($this->mysql0->connect_error);
            die('<html><style nonce="' . $_SERVER['REQUEST_ID'] . '">html{padding:0;height:100%}body{background:#fff url(\'' . $engine->cfg->staticUrl . '/img/pekonia.jpg\') no-repeat 50% 50%;background-size:cover}p{font-size:0;color:transparent}</style><body><p>Lauta meni rikki. Korjataan. Ota sillä välin pekonia.</p></body></html>');
        }

        error_reporting($err_rep);
        $this->mysql0->set_charset('utf8mb4');
    }

    public function fetchAll($q, $col = false, $key = false)
    {
        $array = [];
        while (($row = $q->fetch_assoc()) !== null) {
            if (is_array($key)) {
                $key = $key[0];
            }
            if ($key && $col) {
                $array[$row[$key]] = $row[$col];
            } elseif ($col) {
                $array[] = $row[$col];
            } elseif ($key) {
                $array[$row[$key]] = $row;
            } else {
                $array[] = $row;
            }
        }

        return $array;
    }

    public function insertedId($db = 'mysql0')
    {
        return $this->$db->insert_id;
    }

    public function get_mv($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $in = '';
        $i = 0;
        foreach ($keys AS $key) {
            if ($i != 0) {
                $in .= ',';
            }
            $in .= "'" . $this->escape($key) . "'";
            ++$i;
        }
        if (empty($in)) {
            return false;
        }

        $q = $this->q("SELECT * FROM materialized_view WHERE view_key IN (" . $in . ")");

        if (!$q) {
            return false;
        }

        $mv = new StdClass;
        while ($row = $q->fetch_object()) {
            $key = $row->view_key;
            $mv->$key = $row->view_value;
        }

        return $mv;
    }

    public function escape($str, $db = 'mysql0')
    {
        return $this->$db->real_escape_string($str);
    }

    public function q($query, $db = 'mysql0')
    {
        global $engine;

        $debug = false;
        if (isset($engine->cfg->debug) && $engine->cfg->debug) {
            $debug = true;
        }

        $query = "/* IP: " . $this->escape($_SERVER['REMOTE_ADDR']) . " */ " . $query;
        if ($debug) {
            ++$engine->queryCount;
            //$query = str_replace( "SELECT ", "SELECT SQL_NO_CACHE ", $query );
            //$query = str_replace( "(SELECT SQL_NO_CACHE ", "(SELECT ", $query );
            $startTime = microtime(true);
        }
        $q = $this->$db->query($query);
        //file_put_contents('/tmp/log', $query ."\n", FILE_APPEND);
        if ($debug) {
            $endTime = microtime(true);
        }

        if ($this->$db->error) {
            error_log('DB Error: ' . $this->$db->error . ' (Query: ' . $query . ')');
        }

        if ($debug) {
            $queryTime = number_format(($endTime - $startTime) * 1000, 5);
            $engine->queryTime += $queryTime;

            if (!isset($this->lastBytesSent)) {
                $this->lastBytesSent = 0;
            }
            if (!isset($this->lastBytesReceived)) {
                $this->lastBytesReceived = 0;
            }

            $res = $this->$db->query('SHOW SESSION STATUS', MYSQLI_USE_RESULT);
            $stats = [];
            while ($row = $res->fetch_assoc()) {
                $stats[$row['Variable_name']] = $row['Value'];
            }

            $engine->executedQueries[] = '( ' . $queryTime . ' ms ) ' . $query . " (KB in/out: " . round(($stats['Bytes_sent'] - $this->lastBytesSent) / 1024,
                    2) . '/' . round(($stats['Bytes_received'] - $this->lastBytesReceived) / 1024, 2) . ')';

            $this->lastBytesSent = $stats['Bytes_sent'];
            $this->lastBytesReceived = $stats['Bytes_received'];

        }

        return $q;
    }
}
