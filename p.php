<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set("display_errors", 1);

$zb = microtime(true);
spl_autoload_register(function ($class) {
	$class = __DIR__ . DIRECTORY_SEPARATOR . "src/" . (str_replace('\\', '/', $class)) . '.php';
	if (is_file($class)) {
		include_once($class);
	}
});




use System\App;

$app = new App(__DIR__, "cpanel3.settings.xml", false);

$performance = new System\Log\Performance($app->root . "admin/performance.log");
$app->database_connect($app->settings->database['host'], $app->settings->database['username'], $app->settings->database['password'], $app->settings->database['name']);
$app->get_base_permission();
$app->set_timezone($app->settings->site['timezone']);
$app->initializePermissions();
$app->initializeSystemCurrency();
$app->register($_SERVER['REQUEST_URI']);
$access_error = $app->user_init();
$fs = new System\FileSystem\Page($app);
$dir = $fs->dir($app->resolve());
if (!$dir) {
	$app->responseStatus->NotFound->response();
}
$fs->setUse($dir->id);
if ($fs()->enabled == false) {
	$app->responseStatus->Forbidden->response();
}

include("admin/methods.php");

/* Deny access if pagefile request a permission & display the login form */
if ($fs()->permission->deny == true && $fs()->id == $app::PERMA_ID['index']) {
	if (@!require_once($app->root . "/admin/forms/upper.php")) {
		$app->responseStatus->NotFound->response();
	}
	if (is_file($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php"))
		if (@!require_once($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php")) {
			$app->responseStatus->NotFound->response();
		}
	if (@!require_once($app->root . "/admin/forms/lower.php")) {
		$app->responseStatus->NotFound->response();
	}
	exit;
} elseif ($fs()->permission->deny == true && $fs()->id == $app::PERMA_ID['login']) {
} elseif ($fs()->permission->deny == true) {
	$app->responseStatus->Forbidden->response();
	$access_error = 403;
	if (@!require_once($app->root . "/admin/forms/upper.php")) {
		$app->responseStatus->NotFound->response();
	}
	if (is_file($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php"))
		if (@!require_once($app->root . "website-contents/" . $app::PERMA_ID['login'] . ".php")) {
			$app->responseStatus->NotFound->response();
		}
	if (@!require_once($app->root . "/admin/forms/lower.php")) {
		$app->responseStatus->NotFound->response();
	}
	exit;
}

$fs->details($fs()->id);


/* Forward */
if ($fs()->headers['contents'] == 4) {
	if ($fs($fs()->forward)) {
		header("location:" . $app->http_root . $fs($fs()->forward)->dir);
		exit;
	} else {
		$app->responseStatus->NotFound->response();
		exit;
	}
}

/* SLO Page */
if ($fs()->id == $app::PERMA_ID['slo']) {
	include_once $app->root . "website-contents/3.php";
	exit;
}


$frequent_visit = new System\Personalization\FrequentVisit($app);
$frequent_visit->registerVisit($fs()->id);
$app->build_prefix_list();


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
	$r = $app->db->query("SELECT comp_name,comp_id FROM companies JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id=" . $app->user->info->id . ";");
	if ($r) {
		require_once($app->root . "/admin/forms/upper.php");
		require_once($app->root . "website-contents/207.php");
		require_once($app->root . "/admin/forms/lower.php");
		exit;
	}
}
/* Account selection page */
if ($app->user->info && isset($_GET['--sys_sel-change']) && $_GET['--sys_sel-change'] == "account" && $fs()->id != 3) {
	$r = $app->db->query("SELECT prt_name,prt_id,cur_symbol,cur_name,cur_id,cur_shortname FROM `acc_accounts` LEFT JOIN currencies ON cur_id=prt_currency JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . $app->user->info->id . ";");
	if ($r) {
		require_once($app->root . "/admin/forms/upper.php");
		require_once($app->root . "website-contents/33.php");
		require_once($app->root . "/admin/forms/lower.php");
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

$performance->fullReport($fs()->dir, $app->user->info->id);