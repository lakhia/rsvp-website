<?php

//error_reporting(E_ALL);
// report all warnings, errors as errors
date_default_timezone_set('America/Los_Angeles');

class DB {

	private $mysqli;
	private $thread_id;
	private $logfile;

	private $dbhost = 		"127.0.0.1"; // for some reason, mysql gives a socket problem if using localhost instead of 127.0.0.1
	private $dbusername = 	"sffaiz"; 
	private $dbpassword = 	"sffaiz-pass"; 
	private $dbname = 		"sffaiz";

	public $errno;
	public $error;
	public $exception_message; 

	public function __construct() {

		// convention is to use $db or $mysqli for the db handle
		$this->mysqli = new mysqli($this->dbhost, $this->dbusername, $this->dbpassword, $this->dbname);

		// tz issue fix, might not be necessary
		$this->mysqli->query("SET time_zone = '-8:00'");

		// used to explicitly kill thread in case of too many connections (destructor)
		$this->thread_id = $this->mysqli->thread_id;

		$this->logfile = fopen("rsvp.log", "a+");
	}

	public function query($query_string, $exception_message = "") {

		$result = $this->mysqli->query($query_string);
		
		if( $this->mysqli->errno == 0 ) // if the query succeeded
		{
			return $result; // can potentially log all queries as well
		} 
		else // the query failed.
		{
			$this->errno = $this->mysqli->errno; 
			$this->error = $this->mysqli->error; 
			$this->exception_message = (!$exception_message) ? $this->mysqli->error : $exception_message;
			
			$this->log_error($query_string);
	
			throw new Exception($exception_message);	
			// This should be the lowest level Exception that can be thrown.
		}
	}

	public function log_error($query = "", $exception_message = "") {
		$date = date("Y-m-d g:i:s a"); 
		/* sets the date in the format 10/31/12 10:45:43 am */

		if ( !empty($exception_message) )
		{
			$this->exception_message = $exception_message; // for debug purposes to call log_error directly
		}

		$backtrace = debug_backtrace();
		// $backtrace[0] is the caller to log_error()
		// $backtrace[1] is the caller to caller of ::log_error(), so the caller to ::query() in 90% of cases

		$offset = isset($backtrace[1]) ? 1 : 0; // if log_error called directly, set offset to 0 else to caller of caller of log_error

			$class = $backtrace[$offset]['class'];
			$function = $backtrace[$offset]['function'];
			$line = $backtrace[$offset]['line'];
			$file = $backtrace[$offset]['file'];
		
		$text = <<< TEXT
{$date} [{$this->errno}] {$this->error} ({$this->exception_message})
{$query}
{$file}: {$class}->{$function}[{$line}]
\n
TEXT;
// not a very good layout at the moment

		fwrite($this->logfile, $text); 

	}

	public function __destruct() {

		fclose($this->logfile);

		$this->mysqli->kill($this->thread_id); 
		// explicitly kill thread to prevent connection pool problems (sometimes an issue)
		$this->mysqli->close();
		// redundancy for above
		return true;
	}
}


?>