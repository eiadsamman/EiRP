<?php

declare(strict_types=1);

namespace System\Core\Log;

class ErrorHandler
{
	private string $_log_error_file;
	public function __construct(string $logfile)
	{
		$this->_log_error_file = $logfile;
		set_error_handler(array(&$this, "userErrorHandler"), E_ALL);
	}


	public function customError(string $e)
	{
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		error_log("[" . date("Y-m-d H:i:s (T)") . "] Custom: {$e} in {$caller['file']} on line {$caller['line']}\r\n", 3, $this->_log_error_file);
	}
	public function logError(\Throwable $e)
	{
		error_log("[" . date("Y-m-d H:i:s (T)") . "] {$e->getCode()}: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\r\n", 3, $this->_log_error_file);
	}
	public final function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars = array())
	{
		$errortype = array(1 => "Error", 2 => "Warning", 4 => "Parsing Error", 8 => "Notice", 16 => "Core Error", 32 => "Core Warning", 64 => "Compile Error", 128 => "Compile Warning", 256 => "User Error", 512 => "User Warning", 1024 => "User Notice");

		if (!isset($errortype[$errno])) {
			$errortype[$errno] = "Unknow";
		}
		error_log("[" . date("Y-m-d H:i:s (T)") . "] {$errortype[$errno]}: {$errmsg} in {$filename} on line {$linenum}" . PHP_EOL, 3, $this->_log_error_file);
	}
}