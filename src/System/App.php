<?php

declare(strict_types=1);

namespace System;

use System\Exceptions\HR\InactiveAccountException;
use System\Exceptions\HR\InvalidLoginException;


//$__pagevisitcountexclude = array(20, 19, 33, 207, 27, 3, 35, 191, 186, 187, 180);
class App
{
	public MySQL $db;
	public Individual\User $user;

	public Finance\Currency|null $currency;
	public $prefixList = array();

	public string $subdomain;
	public $base_permission = 0;
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


	protected array $permissions_array = array();
	private string|null $route = null;

	function __construct(string $root, string $settings_file, ?bool $cache = true)
	{
		/* Set file system root */
		$this->root = $root . DIRECTORY_SEPARATOR;

		/* Create HTTP response status code instance */
		$this->responseStatus = new ResponseStatus();
		$this->errorHandler = new \System\Log\ErrorHandler($this->root . "/admin/error.log");


		/* Get System settings */
		$this->settings = new Settings($this->root . $settings_file);
		if (!$this->settings->read()) {
			$this->errorHandler->customError("Reading setting file failed");
			$this->responseStatus->InternalServerError->response();
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

		/* Application session User */
		$this->user = new Individual\User($this);

		/* Page requested with XHTTP  */

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_SERVER['HTTP_APPLICATION_FROM'])) {
			$this->xhttp = true;
		} else {
			$this->xhttp = false;
		}
		$this->permissions_array = array();


		/* Handle cache */
		header('Content-Type: text/html; charset=utf-8', true);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + ($cache ? 604800 : 0)) . ' GMT');
		header("Cache-Control: " . ($cache ? "public" : "no-cache, no-store, must-revalidate"));
		header("Pragma: " . ($cache ? "public" : "no-cache"));

	}


	private function prepareURI(string $uri): string
	{
		$uri = explode("?", $uri)[0];
		$uri = ltrim($uri, "/");
		if (substr($uri, 0, strlen($this->subdomain)) == $this->subdomain) {
			$uri = substr($uri, strlen($this->subdomain));
		}
		$uri = trim($uri, "/ ");
		return $uri;
	}

	public function register(string $route): bool
	{
		$route = $this->prepareURI($route);
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

	public function initializeSystemCurrency(): bool
	{
		$stmt = $this->db->prepare("SELECT cur_id,cur_name,cur_shortname,cur_symbol FROM currencies WHERE cur_default=1;");
		if ($stmt->execute() && $rec = $stmt->get_result()) {
			if ($rec->num_rows > 0 && $row = $rec->fetch_assoc()) {
				$this->currency = new Finance\Currency();
				$this->currency->id = (int) $row['cur_id'];
				$this->currency->name = $row['cur_name'];
				$this->currency->shortname = $row['cur_shortname'];
				$this->currency->symbol = $row['cur_symbol'];
			}
		}
		return false;
	}


	public function initializePermissions(): void
	{
		$stmt = $this->db->prepare("SELECT per_id,per_title,per_order FROM permissions");
		if ($stmt->execute() && $rec = $stmt->get_result()) {
			while ($row = $rec->fetch_assoc()) {
				$this->permissions_array[$row['per_id']] = new Permission();
				$this->permissions_array[$row['per_id']]->id = $row['per_id'];
				$this->permissions_array[$row['per_id']]->name = $row['per_title'];
				$this->permissions_array[$row['per_id']]->level = $row['per_order'];
			}
		}
	}
	public function database_connect(string $host, string $user, string $pass, string $database)
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
	public function set_timezone($timezone): void
	{
		try {
			date_default_timezone_set($timezone);

		} catch (\Exception $e) {
			$this->responseStatus->InternalServerError->response();
		}
	}
	public function get_base_permission(): bool
	{
		$lowsetlevel = $this->db->query("SELECT per_id FROM permissions WHERE per_order = (SELECT MIN(per_order) FROM permissions); ");
		if ($lowsetlevel && $rowlowsetlevel = $lowsetlevel->fetch_assoc()) {
			$this->base_permission = (int) $rowlowsetlevel['per_id'];
		}

		if ($this->base_permission == 0) {
			$this->errorHandler->customError("Failed to fetch system base permission");
			$this->responseStatus->NotFound->response();
		} else {
			return true;
		}
		return false;
	}
	public function build_prefix_list(): bool
	{
		$this->prefixList = array();
		$r = $this->db->query("SELECT prx_id, prx_value, prx_placeholder FROM system_prefix;");
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				$this->prefixList[$row['prx_id']] = array($row['prx_value'], (int) $row['prx_placeholder']);
			}
		}
		return true;
	}

	public function translate_prefix(int $type, int $number): string
	{
		if (!is_array($this->prefixList) || sizeof($this->prefixList) == 0) {
			return (string) $number;
		}
		$type = (int) $type;
		if (isset($this->prefixList[$type])) {
			return $this->prefixList[$type][0] . str_pad((string) $number, $this->prefixList[$type][1], "0", STR_PAD_LEFT);
		}
		return (string) $number;
	}
	public function padding_prefix(int $type, int $number): string
	{
		if (!is_array($this->prefixList) || sizeof($this->prefixList) == 0) {
			return (string) $number;
		}
		$type = (int) $type;
		if (isset($this->prefixList[$type])) {
			return str_pad((string) $number, $this->prefixList[$type][1], "0", STR_PAD_LEFT);
		}
		return (string) $number;
	}

	public function formatTime(int $time, ?bool $include_seconds = true): string
	{
		$neg = $time < 0;
		$time = abs($time);
		$output = ($neg ? "(" : "") .
			sprintf('%02d', floor($time / 60 / 60)) .
			":" .
			sprintf('%02d', floor((int) ($time / 60) % 60)) .
			($neg ? ")" : "");

		if ($include_seconds)
			$output .= ":" . str_pad((string) floor((int) ($time) % 60), 2, "0", STR_PAD_LEFT);
		return $output;
	}

	public function date_validate(string $query, ?bool $end_of_day = false): int|bool
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

	public function user_init(): int
	{
		/* Session handler */
		if (isset($_SESSION["sur"])) {
			$stmt = $this->db->prepare(
				"SELECT usr_id
				FROM  users JOIN users_sessions ON usrses_usr_id=usr_id 
				WHERE usrses_session_id=?;"
			);
			if ($stmt) {
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
		return 0;
	}
}