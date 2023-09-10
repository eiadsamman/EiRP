<?php
$zb = microtime(true);
spl_autoload_register(function ($class) {
	$class = __DIR__ . DIRECTORY_SEPARATOR . "src/" . (str_replace('\\', '/', $class)) . '.php';
	if (is_file($class)) {
		include_once($class);
	}
});

use System\App;

$app = new App(__DIR__, "cpanel3.settings.xml");

error_reporting(E_ALL);
ini_set("display_errors", 1);
set_include_path(get_include_path() . PATH_SEPARATOR . $app->root);
header('Content-Type: text/html; charset=utf-8', true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT'); //+ (604800)
header("Cache-Control: no-cache, no-store, must-revalidate"); //public
header("Pragma: no-cache"); //public

new System\ErrorHandler(strtolower($app->settings->site['errorlog']) == true ? true : false, $app->root . $app->settings->site['errorlogfile']);


$app->database_connect($app->settings->database['host'], $app->settings->database['username'], $app->settings->database['password'], $app->settings->database['name']);
$app->get_base_permission();
$app->set_timezone($app->settings->site['timezone']);
$app->subdomain = isset($app->settings->site['subdomain']) && trim($app->settings->site['subdomain']) != "" ? trim($app->settings->site['subdomain']) : false;
$app->initialize_permissions();
$request = $app->process_request(str_replace("\\", "/", trim($_GET['___REQUESTURI'], "/")) . "/", $app->settings->site['index']);
$access_error = $app->user_init();;
$fs = new System\FileSystem\File($app);
$dir = $fs->dir($request);
$fs->setUse($dir->id);
if ($fs()->enabled == false) {
	$app->responseStatus->NotFound;
}
$app->build_prefix_list();


include("admin/methods.php");


/* Deny access if pagefile request a permission & display the login form */
if ($fs()->permission->deny == true && $fs()->id == $app::PERMA_ID['index']) {
	if (@!require_once($app->root . "/admin/forms/upper.php")) {
		$app->responseStatus->NotFound;
	}
	if (is_file($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php"))
		if (@!require_once($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php")) {
			$app->responseStatus->NotFound;
		}
	if (@!require_once($app->root . "/admin/forms/lower.php")) {
		$app->responseStatus->NotFound;
	}
	exit;
} elseif ($fs()->permission->deny == true && $fs()->id == $app::PERMA_ID['login']) {
} elseif ($fs()->permission->deny == true) {
	$app->responseStatus->Forbidden;
	$access_error = 403;
	if (@!require_once($app->root . "/admin/forms/upper.php")) {
		$app->responseStatus->NotFound;
	}
	if (is_file($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php"))
		if (@!require_once($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php")) {
			$app->responseStatus->NotFound;
		}
	if (@!require_once($app->root . "/admin/forms/lower.php")) {
		$app->responseStatus->NotFound;
	}
	exit;
}

$fs->full($fs()->id);
$fs->increment_visit();


/* Forward */
if ($fs()->headers['contents'] == 40) {
	if ($fs($fs()->forward)) {
		header("location:" . $app->http_root . $fs($fs()->forward)->dir);
		exit;
	} else {
		$app->responseStatus->NotFound;
		exit;
	}
}


/* SLO Page */
if ($fs()->id == $app::PERMA_ID['slo']) {
	include_once $app->root . "website-contents/3.php";
	exit;
}


/* SECTOR REGISTER */
if ($app->user->info && isset($_GET['--sys_sel-change'], $_GET['i']) && $_GET['--sys_sel-change'] == 'account_commit') {
	if ($app->user->register_account((int) $_GET['i'])) {
		header("Location: " . $app->http_root . $fs()->dir . "/");
	}
}


/* COMPANY REGISTER */
if ($app->user->info && isset($_GET['--sys_sel-change'], $_GET['i']) && $_GET['--sys_sel-change'] == 'company_commit') {
	if ($app->user->register_company((int) $_GET['i'])) {
		header("Location: " . $app->http_root . $fs()->dir . "/");
	}
}

/* Company selection page */
if ($app->user->info && isset($_GET['--sys_sel-change']) && $_GET['--sys_sel-change'] == "company" && $fs()->id != 3) {
	$r = $sql->query("SELECT comp_name,comp_id FROM companies JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id=" . $app->user->info->id . ";");
	if ($r) {
		if (@!require_once($app->root . "/admin/forms/upper.php")) {
			$app->responseStatus->NotFound;
		}
		if (@!require_once($app->root . "website-contents/207.php")) {
			$app->responseStatus->NotFound;
		}
		if (@!require_once($app->root . "/admin/forms/lower.php")) {
			$app->responseStatus->NotFound;
		}
		exit;
	}
}
/* Account selection page */
if ($app->user->info && isset($_GET['--sys_sel-change']) && $_GET['--sys_sel-change'] == "account" && $fs()->id != 3) {
	$r = $sql->query("SELECT prt_name,prt_id,cur_symbol,cur_name,cur_id,cur_shortname FROM `acc_accounts` LEFT JOIN currencies ON cur_id=prt_currency JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . $app->user->info->id . ";");
	if ($r) {
		if (@!require_once($app->root . "/admin/forms/upper.php")) {
			$app->responseStatus->NotFound;
		}
		if (@!require_once($app->root . "website-contents/33.php")) {
			$app->responseStatus->NotFound;
		}
		if (@!require_once($app->root . "/admin/forms/lower.php")) {
			$app->responseStatus->NotFound;
		}
		exit;
	}
}


if ($app->xhttp) {
	include_once $app->root . "website-contents/{$fs()->id}.php";
} else {
	if ($fs()->headers['html-header'] == 0) {
		include_once $app->root . "/admin/forms/upper.php";
	}
	switch ($fs()->headers['contents']) {
		case 1:
			include_once $app->root . "website-contents/{$fs()->id}.php";
			break;
		case 2:
			include_once $app->root . "website-contents/0.php";
			break;
		case 3:
			include_once $app->root . "website-contents/{$fs()->loader}.php";
			break;
	}
	if ($fs()->headers['html-header'] == 0) {
		include_once $app->root . "/admin/forms/lower.php";
	}
}



unset($request_uri);
