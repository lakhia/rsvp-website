<?php

date_default_timezone_set('America/Los_Angeles');
error_reporting(E_ALL);

class DB {
    private $mysqli;
    private $thread_id;

    private $dbhost;
    private $dbusername;
    private $dbpassword;
    private $dbname;
    public $connected = false;
    public $error = "";
    public $error_data = null;

    public function __construct() {
        $this->dbhost = getenv('DB_HOST') ?: 'db';
        $this->dbusername = getenv('DB_USERNAME') ?: 'sffaiz';
        $this->dbpassword = getenv('DB_PASSWORD') ?: 'sffaiz-pass';
        $this->dbname = getenv('DB_DATABASE') ?: 'sffaiz';

        // convention is to use $db or $mysqli for the db handle
        $this->mysqli = new mysqli($this->dbhost, $this->dbusername,
                                 $this->dbpassword, $this->dbname);

        if ($this->mysqli->connect_errno) {
            $this->log_error($this->mysqli->connect_error);
            throw new Exception($this->mysqli->connect_error);
        } else {
            $this->connected = true;
        }
    }

    public function prepare($query_string) {
        return $this->mysqli->prepare($query_string);
    }

    public function query($query_string) {
        if (!$this->connected) {
            return false;
        }

        $result = $this->mysqli->query($query_string);

        if ($this->mysqli->errno == 0) {
            // can potentially log all queries as well
            return $result;
        } else {
            // the query failed, log it
            $this->log_error("{$this->mysqli->errno}:{$this->mysqli->error}",
                           $query_string);
            return false;
        }
    }

    public function log_error($error, $query = "") {
        // Determine if called by query() or directly to determine file and
        // line number
        $backtrace = debug_backtrace();

        $offset = 0;
        if ($backtrace[1]['function'] == "query" && isset($backtrace[1])) {
            $offset = 1;
        }

        $line = $backtrace[$offset]['line'];
        $file = $backtrace[$offset]['file'];

        // date not necessary because error_log already does it

        $error_data = array($error, $query, $file, $line);
        $text = implode(":", $error_data);

        // Keep last error for caller
        $this->error = $error;
        $this->error_data = $error_data;

        error_log($text);
    }

    public function __destruct() {
        $this->mysqli->close();
    }
}
?>