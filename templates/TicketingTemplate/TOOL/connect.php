<?php

// Gestion des erreurs///////////////////////////

class ErrorHandler extends Exception
{
	protected $severity;

	public function __construct($message, $code, $severity, $filename, $lineno)
	{
		$this->message = $message;
		$this->code = $code;
		$this->severity = $severity;
		$this->file = $filename;
		$this->line = $lineno;
	}

	public function getSeverity()
	{
		return $this->severity;
	}
}

function exception_error_handler($errno, $errstr, $errfile, $errline)
{

	if ($errline > 0) {

		$gnl = "<fieldset>";
		$gnl .= 'NO: ' . $errno . '<br>';
		$gnl .= 'STR: ' . $errstr . '<br>';
		$gnl .= 'FILE: ' . $errfile . '<br>';
		$gnl .= 'LINE: ' . $errline . '<br>';
		$gnl .= 'CONTEXT: ' . Tool::context() . '<br>';
		$gnl .= '</fieldset>';
		echo $gnl;
	}
}


set_error_handler("exception_error_handler", E_ALL);

if(session_status()==PHP_SESSION_NONE and headers_sent()==false){
	if (!@\session_start()) return false;
}

////////////////////////////////////////////////
