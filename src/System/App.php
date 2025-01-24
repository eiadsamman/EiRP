<?php
declare(strict_types=1);

namespace System;

use System\Exceptions\HR\InactiveAccountException;
use System\Exceptions\HR\InvalidLoginException;
use System\Finance\Currency;
use System\Models\Branding;
use System\Views\Views;

//$__pagevisitcountexclude = array(20, 19, 33, 207, 27, 3, 35, 191, 186, 187, 180);
class App
{
	public string $id;
	public readonly MySQL $db;
	public readonly Individual\User $user;
	public string $broadcast = "";
	public Currency $currency;
	public ?array $currencies;

	public Branding $branding;
	public string $subdomain;
	public \System\Unit $unit;
	public int $base_permission = 0;
	public ResponseStatus $responseStatus;
	public bool $xhttp = false;
	public \System\Log\ErrorHandler $errorHandler;
	public const PERMA_ID = array(
		"index" => 19,
		"login" => 20,
		"slo" => 3,
		"download" => 187,
	);
	public string $root;
	public string $http_root;
	public Settings $settings;

	public ?Views $view;

	public \System\FileSystem\Page $file;
	protected array $permissions_array = array();
	private string|null $route = null;

	function __construct(string $root, string $settings_file, ?bool $cache = true)
	{

		/* Set file system root */
		$this->root = $root . DIRECTORY_SEPARATOR;

		/* Create HTTP response status code instance */
		$this->responseStatus = new ResponseStatus();
		$this->errorHandler   = new \System\Log\ErrorHandler($this->root . "/admin/error.log");


		/* Get System settings */
		$this->settings = new Settings($this->root . $settings_file);
		if (!$this->settings->read()) {
			$this->errorHandler->customError("Reading setting file failed");
			$this->responseStatus->InternalServerError->response();
		}

		$this->readBroadcast();

		if (!empty($this->settings->site['environment']) && $this->settings->site['environment'] === "development") {
			error_reporting(E_ALL);
			ini_set('display_errors', 'On');
		} else {
			error_reporting(0);
			ini_set('display_errors', 'Off');
		}

		$subdomain = trim($this->settings->site['subdomain']);
		/* Set http root */
		$this->http_root =
			(
				$this->settings->site['forcehttps'] === true ?
				"https" :
				"http"
			) .
			"://{$_SERVER['SERVER_NAME']}/" . ($subdomain == "" ? "" : $subdomain . "/");

		$this->subdomain = ltrim(trim($this->settings->site['subdomain']), "/");

		/* Start session */
		session_start();


		$this->unit=  new \System\Unit();
		$this->id = substr(md5(session_id() . $this->broadcast), 0, 6);

		/* Application session User */
		$this->user = new Individual\User($this);

		/* Page requested with XHTTP  */

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_SERVER['HTTP_APPLICATION_FROM'])) {
			$this->xhttp = true;
		} else {
			$this->xhttp = false;
		}
		$this->unitMeasurment    = new \System\Unit();
		$this->permissions_array = array();
		$this->view              = null;

		/* Handle cache */
		header('Content-Type: text/html; charset=utf-8', true);
		$cache = false;
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + ($cache ? 86400 : 0)) . ' GMT');
		header("Cache-Control: " . ($cache ? "public, immutable, max-age=86400" : "no-cache, no-store, must-revalidate"));
		header("Pragma: " . ($cache ? "cache" : "no-cache"));
	}

	private function readBroadcast(): void
	{
		if (is_file("{$this->root}broadcast")) {
			$this->broadcast = file_get_contents("{$this->root}broadcast");
		} else {
			$this->broadcast = md5("default.broadcast");
		}
	}

	private function prepareURI(string $uri): string
	{
		$uri = explode("?", $uri)[0];
		$uri = ltrim($uri, "/");
		if (substr($uri, 0, strlen($this->subdomain)) == $this->subdomain) {
			$uri = substr($uri, strlen($this->subdomain));
		}
		$uri = trim($uri, "/ ");
		$uri = preg_replace("#[/]+#", "/", $uri);
		return $uri;
	}

	public function resolveArray()
	{
		return explode("/", $this->route);
	}

	public function register(string $route): bool
	{
		$route       = $this->prepareURI($route);
		$this->route = $route == "" ? $this->settings->site['index'] : $route;

		return true;
	}

	public function resolve(): string
	{
		return $this->route;
	}

	public function permission(int $id): Permission|bool
	{
		if (array_key_exists($id, $this->permissions_array)) {
			return $this->permissions_array[$id];
		}
		return false;
	}

	public function build(): bool
	{
		$this->getBasePermission();
		$this->setTimezone($this->settings->site['timezone']);
		$this->initializePermissions();
		$this->currencies = array();
		$stmt             = $this->db->prepare("SELECT cur_id,cur_name,cur_shortname,cur_symbol,cur_default FROM currencies;");
		if ($stmt->execute() && $rec = $stmt->get_result()) {
			if ($rec->num_rows > 0 && $row = $rec->fetch_assoc()) {
				$cur            = new Currency();
				$cur->id        = (int) $row['cur_id'];
				$cur->name      = $row['cur_name'];
				$cur->shortname = $row['cur_shortname'];
				$cur->symbol    = $row['cur_symbol'];
				array_push($this->currencies, $cur);
				if ((int) $row['cur_default'] == 1) {
					$this->currency = $cur;
				}
			}
		}
		return false;
	}

	private function initializePermissions(): void
	{
		$stmt = $this->db->prepare("SELECT per_id,per_title,per_order FROM permissions");
		if ($stmt->execute() && $rec = $stmt->get_result()) {
			while ($row = $rec->fetch_assoc()) {
				$this->permissions_array[$row['per_id']]        = new Permission();
				$this->permissions_array[$row['per_id']]->id    = $row['per_id'];
				$this->permissions_array[$row['per_id']]->name  = $row['per_title'];
				$this->permissions_array[$row['per_id']]->level = $row['per_order'];
			}
		}
	}
	public function databaseConnect(string $host, string $user, string $pass, string $database)
	{
		try {
			$this->db = new MySQL($host, $user, $pass, $database);
			if ($this->db->connect_errno) {
				$this->errorHandler->customError("Connection to the database filed");
				$this->responseStatus->NotFound->response();
			} else {
				$this->db->set_charset('utf8');
			}
		} catch (\TypeError $e) {
			$this->errorHandler->logError($e);
			$this->responseStatus->NotFound->response();
		} catch (\mysqli_sql_exception $e) {
			$this->errorHandler->logError($e);
			$this->responseStatus->NotFound->response();
		}
	}
	public function setTimezone($timezone): void
	{
		try {
			date_default_timezone_set($timezone);

		} catch (\Exception $e) {
			$this->responseStatus->InternalServerError->response();
		}
	}
	public function getBasePermission(): bool
	{
		/* Bypass for performance */
		$this->base_permission = 2;
		return true;
	}




	public function formatTime(float $time, ?bool $include_seconds = true): string
	{
		$neg    = $time < 0;
		$time   = abs($time);
		$output = ($neg ? "(" : "") .
			sprintf('%02d', floor($time / 60 / 60)) .
			":" .
			sprintf('%02d', floor((int) ($time / 60) % 60)) .
			($neg ? ")" : "");

		if ($include_seconds)
			$output .= ":" . str_pad((string) floor((int) ($time) % 60), 2, "0", STR_PAD_LEFT);
		return $output;
	}

	public function dateValidate(string $query, ?bool $end_of_day = false): int|bool
	{
		if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $query, $match)) {
			if (checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
				if ($end_of_day) {
					return mktime(23, 59, 59, (int) $match[2], (int) $match[3], (int) $match[1]);
				} else {
					return mktime(0, 0, 0, (int) $match[2], (int) $match[3], (int) $match[1]);
				}
			}
		}
		return false;
	}

	public function sessionOpen(): int
	{
		/* Session handler */
		if (isset($_SESSION["sur"])) {
			$stmt = $this->db->prepare("SELECT usr_id FROM  users JOIN users_sessions ON usrses_usr_id = usr_id WHERE usrses_session_id = ?;");
			$stmt->bind_param("s", $_SESSION["sur"]);
			try {
				if ($stmt->execute() && $r = $stmt->get_result()) {
					if ($r && $row = $r->fetch_assoc()) {
						$this->user->load($row['usr_id']);
						$this->user->logged = true;
					}
					$stmt->close();
				}
			} catch (\mysqli_sql_exception $e) {
				$this->errorHandler->logError($e);
				return 9;
			} catch (\System\Exceptions\HR\PersonNotFoundException $e) {
				$this->errorHandler->logError($e);
				return 4;
			}
		}

		/* Users Login */
		if (isset($_POST['login'], $_POST['log_username'], $_POST['log_password'])) {
			try {
				if ($this->user->login($_POST['log_username'], $_POST['log_password'], isset($_POST['remember']))) {
					if (isset($_POST['refer'])) {
						header("Location: " . $this->http_root . urldecode($_POST['refer']));
					} else {
						header("Location: " . $this->http_root);
					}
				}
			} catch (InvalidLoginException $e) {
				$this->errorHandler->logError($e);
				return 2;
			} catch (InactiveAccountException $e) {
				$this->errorHandler->logError($e);
				return 3;
			}
		}

		/* Cookies Login */
		if (!$this->user->logged && isset($_COOKIE) && is_array($_COOKIE) && isset($_COOKIE['cur'])) {
			if ($this->user->cookies_handler($_COOKIE['cur'])) {
				header("Location: " . $this->http_root . $this->prepareURI($_SERVER['REQUEST_URI']));
				exit;
			}
		}

		/* Logout */
		if (isset($_GET['logout'])) {
			if ($this->user->logout()) {
				header("Location: " . $this->http_root);
				exit;
			}
		}
		$this->branding = new Branding($this);
		return 0;
	}

	public function viewVendor(string $viewName): bool
	{
		if (class_exists('System\\Views\\' . $viewName)) {
			$className  = 'System\\Views\\' . $viewName;
			$this->view = new $className($this);
			return true;
		} else {
			$this->view = null;
		}
		return false;
	}
}