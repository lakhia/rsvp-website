<?php

date_default_timezone_set('America/Los_Angeles');

error_reporting(E_ERROR & E_WARNING);

class DB {

	private $mysqli;
	private $thread_id;

    private $dbhost = 		"127.0.0.1";
	private $dbusername = 	"sffaiz"; 
	private $dbpassword = 	"sffaiz-pass"; 
	private $dbname = 		"sffaiz";

    public  $connected =    false;
    public  $error =        "";

	public function __construct() {

		// convention is to use $db or $mysqli for the db handle
        $this->mysqli = new mysqli($this->dbhost, $this->dbusername,
                                   $this->dbpassword, $this->dbname);

        if ($this->mysqli->connect_errno) {
            // the query failed, log it
            $this->log_error("{$this->mysqli->connect_error}");
        } else {
            $this->connected = true;
        }
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

        $text = date("Y-m-d G.i.s:");
        $text .= "{$error}:";
        $text .= "{$query}:";
        $text .= "{$file}:{$line}";

        // Keep last error for caller
        $this->error = $error;

		error_log($text);
	}

	public function __destruct() {
		$this->mysqli->close();
	}
}


?>