<?php
$_SERVER['FILE_SYSTEM_ROOT'] = __DIR__ . DIRECTORY_SEPARATOR;
$__pagevisitcountexclude = array(20, 19, 33, 207, 27, 3, 35, 191, 186, 187, 180);

error_reporting(E_ALL);
ini_set("display_errors", 1);

set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER['FILE_SYSTEM_ROOT']);

define("DOMAIN", $_SERVER['SERVER_NAME']);
define("SESSIONNAME", "sur");
define("HTTP503", 'HTTP/1.0 503 Service Unavailable');
define("HTTP403", 'HTTP/1.0 403 Forbidden');
define("HTTP404", 'HTTP/1.0 404 Not Found');
define("HTTP454", 'HTTP/1.0 454 Session Not Found (RTSP)');

header('Content-Type: text/html; charset=utf-8', true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (604800)) . ' GMT');
header("Cache-Control: public");
header("Pragma: public");




if (!isset($_SERVER['QUERY_STRING'], $_GET['___REQUESTURI'])) {
	header(HTTP503);
	echo "<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable";
	exit;
}

$h__requested_with_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false;


$_GET['___REQUESTURI'] = str_replace("\\", "/", trim($_GET['___REQUESTURI'], "/")) . "/";



if (@!include_once("cpanel3/classes/call.php")) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable");
}
if (@!include_once("admin/class/system.php")) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable");
}
if (@!include_once("admin/class/filesystem.php")) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable");
}
if (@!include_once("admin/class/systemuser.php")) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable");
}

use System\Pool;
use System\Person\User;
use System\FileSystem;

use System\Person\PersonNotFoundException;
use System\Person\InvalidLoginDetailsException;
use System\Person\InactiveAccountException;






/* Settings */

$settings = new Settings();
$settings->settings_setfile("{$_SERVER['FILE_SYSTEM_ROOT']}cpanel3.settings.xml");
if (!$settings->settings_fetch()) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable");
}
$c__settings = $settings->settings_read();

/* URI Root */
$_SERVER['HTTP_SYSTEM_ROOT'] = (isset($c__settings['site']['forcehttps']) && $c__settings['site']['forcehttps'] === true ? "https" : "http") . "://{$_SERVER['SERVER_NAME']}/" . (isset($c__settings['site']['subdomain']) && trim($c__settings['site']['subdomain']) != "" ? $c__settings['site']['subdomain'] . "/" : "");

/* ODB */
try {
	$sql = new SQL();
	if (!$sql) {
		header(HTTP503);
		die("<h1>503 - Service Unavailable</h1> Connection to database failed, try again later or contact system administrator");
	}
} catch (mysqli_sql_exception $e) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Connection to database failed, try again later or contact system administrator");
}
$sql->set_charset('utf8');
Pool::$sql = $sql;


/* System Classes */
if (!Pool::getBasePermission()) {
	header(HTTP503);
	die("<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable");
}



/* Session */
$session = new MySession();
if (!$session->mysession_start(SESSIONNAME)) {
	header(HTTP454);
	echo "<h1>454 - Session Not Found (RTSP)</h1>Session not found, session initilazing failed";
	exit;
}


/* Timezone */
$timezone = new CustomDateTimeZone();
$timezone->SetDateTimeZone();

/* Error handler */
$error = new ErrorHandler(strtolower($c__settings['site']['errorlog']) == true ? true : false, $_SERVER['FILE_SYSTEM_ROOT'] . $c__settings['site']['errorlogfile']);


Pool::$subdomain = isset($c__settings['site']['subdomain']) && trim($c__settings['site']['subdomain']) != "" ? trim($c__settings['site']['subdomain']) : false;
$Languages = new Languages();
$tables = new Tables($sql, $Languages);
$pageinfo = false;
$request_language = null;
$request_uri = null;
$_uri = $_GET['___REQUESTURI'];



if (preg_match("#^(.*?)$#i", $_uri, $_chunk)) {
	$request_uri = trim(rtrim($_chunk[1], "\\/"));
	unset($_chunk, $_uri);
} else {
	header(HTTP503);
	echo "<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable";
	exit;
}

/*Languages management*/
if (!$Languages->set_current_by_code($request_language)) {
	if (false === $Languages->default_key_id() || false === $Languages->set_current_by_id($Languages->default_key_id())) {
		header(HTTP503);
		echo "<h1>503 - Service Unavailable</h1> Requested page does not exists on this server or current services are unavailable";
		exit;
	}
}
if ($request_uri == "") {
	$request_uri = $c__settings['site']['index'];
}


/*System Reset Command*/
if (file_exists($_SERVER['FILE_SYSTEM_ROOT'] . "c1bae6f4c382383fae88337e743088cf")) {
	include_once("website-contents/c1bae6f4c382383fae88337e743088cf/index.php");
	exit;
}


/*Pagefile details*/
$pageinfo = $tables->PageFromURI($request_uri);
if (!$pageinfo) {
	header(HTTP404);
	die("<h1>404 - Not found</h1> `{$_SERVER['HTTP_SYSTEM_ROOT']}$request_uri/` does not exists on this server");
}
if ($pageinfo['enable'] == 0) {
	header(HTTP403);
	exit;
}

unset($request_uri);


$USER = new User();
Pool::$_user = $USER;
$access_error = false;




/* Session handler */
if (isset($_SESSION["sur"])) {
	$uni = $sql->escape($_SESSION["sur"]);
	$r = $sql->query("
				SELECT 
					usr_id,usr_username,usr_firstname,usr_lastname,usr_privileges ,per_order
				FROM 
					users 
						JOIN users_sessions ON usrses_usr_id=usr_id AND usrses_session_id='$uni'
						JOIN permissions ON per_id = usr_privileges;");

	if ($r && $row = $sql->fetch_assoc($r)) {
		try {
			Pool::$_user->load($row['usr_id']);
			Pool::$_user->logged = true;
		} catch (PersonNotFoundException $e) {
		}
	}
	unset($uni);
}

/* Users Login */
if (isset($_POST['login'], $_POST['log_username'], $_POST['log_password'])) {
	try {
		if (Pool::$_user->login($_POST['log_username'], $_POST['log_password'], isset($_POST['remember']))) {
			if (isset($_POST['refer'])) {
				header("Location: " . $_SERVER['HTTP_SYSTEM_ROOT'] . urldecode($_POST['refer']));
			} else {
				header("Location: " . $_SERVER['HTTP_SYSTEM_ROOT']);
			}
		}
	} catch (InvalidLoginDetailsException $e) {
		$access_error = 2;
	} catch (InactiveAccountException $e) {
		$access_error = 3;
	}
}

/* Cookies Login */
if (!Pool::$_user->logged && isset($_COOKIE) && is_array($_COOKIE) && isset($_COOKIE['cur'])) {
	if (Pool::$_user->cookies_handler($_COOKIE['cur'])) {
		header("Location: " . $_SERVER['HTTP_SYSTEM_ROOT']);
		exit;
	}
}

/* Logout */
if (isset($_GET['logout'])) {
	if (Pool::$_user->logout()) {
		header("Location: " . $_SERVER['HTTP_SYSTEM_ROOT']);
		exit;
	}
}

$c__actions = new AllowedActions(Pool::$_user->info->permissions ? Pool::$_user->info->permissions : Pool::$base_permission, $pageinfo['permissions']);
if ($c__actions == false) {
	echo "Internal System Error!";
	header(HTTP503);
	exit;
}




$fs = new FileSystem();
$fs->setUse($pageinfo['id']);
Pool::buildPrefixList();

include("admin/methods.php");

/* Deny access if pagefile request a permission & display the login form */
if ($c__actions->deny == true && $fs->use()->id == Pool::$operational_page['index']) {
	if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/upper.php")) {
		header(HTTP503);
		exit;
	}
	if (is_file($_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/" . Pool::$operational_page['login'] . ".php"))
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/" . Pool::$operational_page['login'] . ".php")) {
			header(HTTP503);
			exit;
		}
	if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/lower.php")) {
		header(HTTP503);
		exit;
	}
	exit;
} elseif ($c__actions->deny == true && $fs->use()->id == Pool::$operational_page['login']) {
} elseif ($c__actions->deny == true) {
	header(HTTP403);
	$access_error = 403;
	if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/upper.php")) {
		header(HTTP503);
		exit;
	}
	if (is_file($_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/" . Pool::$operational_page['login'] . ".php"))
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/" . Pool::$operational_page['login'] . ".php")) {
			header(HTTP503);
			exit;
		}
	if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/lower.php")) {
		header(HTTP503);
		exit;
	}
	exit;
}
/* Update page visit count */
if (!in_array($fs->use()->id, $__pagevisitcountexclude)) {
	$sql->query("INSERT INTO user_settings (usrset_usr_id,usrset_name,usrset_usr_defind_name,usrset_value,usrset_time) 
					VALUES (" . Pool::$_user->info->id . ",'system_count_page_visit',{$fs->use()->id},'1',NOW()) ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");
}

/* Forward */
if ($pageinfo['header']['contents'] == 40) {
	if (is_null($pageinfo['forward'])) {
		header(HTTP404);
		exit;
	} else {
		header("location:" . $_SERVER['HTTP_SYSTEM_ROOT'] . $pageinfo['forward']);
		exit;
	}
}


/* SLO Page */
if ($fs->use()->id == 3) {
	include_once $_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/3.php";
	exit;
}


/* SECTOR REGISTER */
if (Pool::$_user->info && isset($_GET['--sys_sel-change'], $_GET['i']) && $_GET['--sys_sel-change'] == 'account_commit') {
	if (Pool::$_user->register_account((int) $_GET['i'])) {
		header("Location: " . $_SERVER['HTTP_SYSTEM_ROOT'] . $fs->use()->dir . "/");
	}
}


/* COMPANY REGISTER */
if (Pool::$_user->info && isset($_GET['--sys_sel-change'], $_GET['i']) && $_GET['--sys_sel-change'] == 'company_commit') {
	if (Pool::$_user->register_company((int) $_GET['i'])) {
		header("Location: " . $_SERVER['HTTP_SYSTEM_ROOT'] . $fs->use()->dir . "/");
	}
}







/* Build pagefile */

/* Company selection page */
if (Pool::$_user->info && isset($_GET['--sys_sel-change']) && $_GET['--sys_sel-change'] == "company" && $fs->use()->id != 3) {
	$r = $sql->query("SELECT comp_name,comp_id FROM companies JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id=" . Pool::$_user->info->id . ";");
	if ($r) {
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/upper.php")) {
			header(HTTP503);
			exit;
		}
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/207.php")) {
			header(HTTP503);
			exit;
		}
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/lower.php")) {
			header(HTTP503);
			exit;
		}
		exit;
	}
}
/* Account selection page */
if (Pool::$_user->info && isset($_GET['--sys_sel-change']) && $_GET['--sys_sel-change'] == "account" && $fs->use()->id != 3) {
	$r = $sql->query("SELECT prt_name,prt_id,cur_symbol,cur_name,cur_id,cur_shortname FROM `acc_accounts` LEFT JOIN currencies ON cur_id=prt_currency JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . Pool::$_user->info->id . ";");
	if ($r) {
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/upper.php")) {
			header(HTTP503);
			exit;
		}
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/33.php")) {
			header(HTTP503);
			exit;
		}
		if (@!require_once($_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/lower.php")) {
			header(HTTP503);
			exit;
		}
		exit;
	}
}



if ($h__requested_with_ajax) {
	include_once $_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/{$fs->use()->id}.php";
} else {
	if ($pageinfo['header']['html-header'] == 0) {
		include_once $_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/upper.php";
	}
	switch ($pageinfo['header']['contents']) {
		case 10:
			include_once $_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/{$fs->use()->id}.php";
			break;
		case 20: /*Content Editor*/
			include_once $_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/0.php";
			break;
		case 30:
			include_once $_SERVER['FILE_SYSTEM_ROOT'] . "website-contents/{$pageinfo['loader']}";
			break;
	}
	if ($pageinfo['header']['html-header'] == 0) {
		include_once $_SERVER['FILE_SYSTEM_ROOT'] . "/admin/forms/lower.php";
	}
}
