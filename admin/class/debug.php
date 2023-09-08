<?php
class Debug{
	public static $logfile = "/log.txt";
	
	
	function __construct() {
		
	}
	public static function Write($message, $file=null, $line=null){
		file_put_contents($_SERVER['FILE_SYSTEM_ROOT'].Debug::$logfile, $file.PHP_EOL.$line.PHP_EOL.date("Y-m-d H:i:s",time()).PHP_EOL.$message.PHP_EOL.PHP_EOL , FILE_APPEND | LOCK_EX);
	}
	
	
	
}
?>