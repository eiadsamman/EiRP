<?php

declare(strict_types=1);

namespace System\Log;

class ErrorHandler
{
	private bool $_log_error;
	private string $_log_error_file;
	public function __construct()
	{
		$this->_log_error = true;
		if (func_num_args() == 2) {
			if (is_bool(func_get_arg(0)) && is_string(func_get_arg(1))) {
				$this->_log_error = func_get_arg(0);
				$this->_log_error_file = (func_get_arg(1));
			}
		}
		set_error_handler(array(&$this, "userErrorHandler"));
	}

	public function logError(\Exception $e)
	{
		error_log("[" . date("Y-m-d H:i:s (T)") . "] {$e->getCode()}: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\r\n", 3, $this->_log_error_file);
	}
	public final function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars = array())
	{
		$errortype = array(1   =>  "Error", 2   =>  "Warning", 4   =>  "Parsing Error", 8   =>  "Notice", 16  =>  "Core Error", 32  =>  "Core Warning", 64  =>  "Compile Error", 128 =>  "Compile Warning", 256 =>  "User Error", 512 =>  "User Warning", 1024 =>  "User Notice");

		if ($this->_log_error) {
			if (!isset($errortype[$errno])) {
				$errortype[$errno] = "Unknow";
			}
			error_log("[" . date("Y-m-d H:i:s (T)") . "] {$errortype[$errno]}: {$errmsg} in {$filename} on line {$linenum}".PHP_EOL, 3, $this->_log_error_file);
		}
	}
}
