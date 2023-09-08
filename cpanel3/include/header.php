<?php 
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	$__special = get_html_translation_table(HTML_SPECIALCHARS,ENT_QUOTES);
	$__special_flip = array_flip($__special);
	$_SERVER['FILE_SYSTEM_ROOT']=dirname(dirname(__dir__)).DIRECTORY_SEPARATOR;
	set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['FILE_SYSTEM_ROOT']);
	include_once "../classes/call.php";
	include_once "../include/fix-arabic.php";

//Collect settings array
	$settings= new Settings();
	$settings->settings_setfile("{$_SERVER['FILE_SYSTEM_ROOT']}cpanel3.settings.xml");
	if(!$settings->settings_fetch()){
		ob_end_clean();
		header('HTTP/1.0 503 Service Unavailable');exit;
	}
	$c__settings= $settings->settings_read();
	
//URI Root
	$_SERVER['HTTP_SYSTEM_ROOT']=(isset($c__settings['site']['forcehttps']) && $c__settings['site']['forcehttps']===true?"https":"http")."://{$_SERVER['SERVER_NAME']}/".(isset($c__settings['site']['subdomain']) && trim($c__settings['site']['subdomain'])!=""?$c__settings['site']['subdomain']."/":"");
//Connect to database
	$sql= new SQL();
	if(!$sql){
		ob_end_clean();
		header('HTTP/1.0 503 Service Unavailable');exit;
	}
	$sql->set_charset('utf8');
		
//Start session manager
	$session= new MySession();
	if(!$session->mysession_start("CPANEL3")){
		ob_end_clean();
		header('HTTP/1.0 454 Session Not Found (RTSP)');exit;
	}
//Set timezone from settings file
	$timezone= new CustomDateTimeZone();
	$timezone->SetDateTimeZone();
//Handle errors
	$error	= new ErrorHandler(strtolower($c__settings['site']['errorlog'])=='true'?true:false,$_SERVER['FILE_SYSTEM_ROOT'].$c__settings['site']['errorlogfile']);


include_once "../include/login.check.php";

?>