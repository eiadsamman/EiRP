<?php

declare(strict_types=1);

namespace System\Individual;

use Exception;
use System\Company;
use System\Finance\Account;

class User extends Person
{
	public bool $logged = false;
	public Company|null $company = null;
	public Account|null $account = null;

	private $rememberloginage = (86400 * 7);

	public function load(int $userid): bool
	{
		if (parent::load($userid)) {
			$this->load_user_selections();
			return true;
		}
		return false;
	}

	private function load_user_selections(): void
	{
		$rcomp = $this->app->db->query(
			"SELECT comp_id,comp_name,up_id
			FROM 
				companies 
					JOIN user_company ON urc_usr_id=" . $this->app->user->info->id . " AND urc_usr_comp_id=comp_id
					JOIN user_settings ON usrset_usr_id=" . $this->app->user->info->id . " AND usrset_type = " . \System\Personalization\Identifiers::SystemWorkingCompany->value . " AND usrset_usr_defind_name='UNIQUE' AND usrset_value=comp_id
					LEFT JOIN uploads ON up_rel=comp_id AND up_pagefile=" . $this->app->scope->company->logo . "
			GROUP BY
				comp_id
			;"
		);
		if ($rcomp && $rowcomp = $rcomp->fetch_assoc()) {
			$this->company = new Company();
			$this->company->id = (int) $rowcomp['comp_id'];
			$this->company->name = $rowcomp['comp_name'];
			$this->company->logo = (int) $rowcomp['up_id'];
		}
		if ($this->company) {
			if (
				$racc = $this->app->db->query(
					"SELECT 
						prt_id,prt_name,cur_symbol,cur_name,cur_id,cur_shortname,upr_prt_inbound,upr_prt_outbound,upr_prt_fetch,upr_prt_view
					FROM 
						`acc_accounts` 
							JOIN currencies ON cur_id = prt_currency
							JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . $this->app->user->info->id . " AND upr_prt_fetch=1
							JOIN user_settings ON usrset_usr_id = " . $this->app->user->info->id . " AND usrset_type = " . \System\Personalization\Identifiers::SystemWorkingAccount->value . " AND usrset_usr_defind_name={$this->company->id} AND usrset_value=prt_id 
					WHERE
						prt_company_id=" . $this->app->user->company->id . ";"
				)
			) {

				if ($rowacc = $racc->fetch_assoc()) {

					$this->account = new \System\Finance\Account();
					$this->account->currency = new \System\Finance\Currency();
					$this->account->role = new \System\Finance\AccountRole();

					$this->account->id = (int) $rowacc['prt_id'];
					$this->account->name = $rowacc['prt_name'];

					$this->account->currency->id = (int) $rowacc['cur_id'];
					$this->account->currency->name = $rowacc['cur_name'];
					$this->account->currency->symbol = $rowacc['cur_symbol'];
					$this->account->currency->shortname = $rowacc['cur_shortname'];

					$this->account->role->inbound = isset($rowacc['upr_prt_inbound']) && (int) $rowacc['upr_prt_inbound'] == 1 ? true : false;
					$this->account->role->outbound = isset($rowacc['upr_prt_outbound']) && (int) $rowacc['upr_prt_outbound'] == 1 ? true : false;
					$this->account->role->access = isset($rowacc['upr_prt_fetch']) && (int) $rowacc['upr_prt_fetch'] == 1 ? true : false;
					$this->account->role->view = isset($rowacc['upr_prt_view']) && (int) $rowacc['upr_prt_view'] == 1 ? true : false;
				}
			}
		}
	}

	public function register_company(int $company_id): bool
	{
		$r = $this->app->db->query("
					SELECT  comp_id,comp_name FROM companies
						JOIN user_company ON comp_id=urc_usr_comp_id AND urc_usr_id=" . $this->app->user->info->id . " AND comp_id={$company_id};");
		if ($r->num_rows > 0) {
			if ($row = $r->fetch_assoc()) {
				$r = $this->app->db->query("INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value, usrset_time) 
									VALUES (" . $this->app->user->info->id . ",	" . \System\Personalization\Identifiers::SystemWorkingCompany->value . ",'UNIQUE','{$row['comp_id']}',NOW()	) 
										ON DUPLICATE KEY UPDATE usrset_value='{$row['comp_id']}';");

				$this->app->db->query("INSERT INTO user_settings (usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) 
									VALUES (" . $this->app->user->info->id . "," . \System\Personalization\Identifiers::SystemCountCompanySelection->value . ",'{$row['comp_id']}','1',NOW()) 
										ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");
				if ($r) {
					return true;
				} else {
					throw new \System\Exceptions\HR\CompanyRegisteringException();
				}
			}
		}

		return false;
	}

	public function register_account(int $account_id): bool
	{
		$r = $this->app->db->query("
					SELECT prt_id,prt_name,cur_symbol,cur_name,cur_id,cur_shortname ,comp_id, upr_prt_inbound, upr_prt_outbound, upr_prt_fetch, upr_prt_view FROM 
						`acc_accounts`
							JOIN companies ON comp_id = prt_company_id
							LEFT JOIN currencies ON cur_id = prt_currency
							JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . $this->app->user->info->id . " AND upr_prt_id={$account_id} AND upr_prt_fetch=1 ;");
		if ($r->num_rows > 0) {
			if ($row = $r->fetch_assoc()) {
				$r = $this->app->db->query("INSERT INTO user_settings (usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) 
									VALUES (" . $this->app->user->info->id . ", " . \System\Personalization\Identifiers::SystemWorkingAccount->value . ",'{$row['comp_id']}','{$row['prt_id']}',NOW()) ON DUPLICATE KEY UPDATE usrset_value='{$row['prt_id']}';");

				$this->app->db->query("INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value, usrset_time) 
								VALUES (" . $this->app->user->info->id . "," . \System\Personalization\Identifiers::SystemCountAccountSelection->value . ",'{$row['prt_id']}','1',NOW()) ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");
				if ($r) {
					return true;
				} else {
					throw new \System\Exceptions\HR\CompanyRegisteringException();
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function set_login_session(string $sessionid, int $userid): void
	{
		$stmt = $this->app->db->prepare("INSERT INTO users_sessions SET usrses_session_id=?, usrses_usr_id=? ON DUPLICATE KEY UPDATE usrses_usr_id=usrses_usr_id;");
		$stmt->bind_param('ss', $sessionid, $userid);
		$stmt->execute();
		$_SESSION["sur"] = $sessionid;
	}

	public function login(string $username, string $password, bool $rememberuser = false): bool
	{
		$stmt = $this->app->db->prepare("SELECT usr_id,usr_username,usr_password,usr_activate FROM users WHERE usr_username=?;");
		$stmt->execute([$username]);
		$rec = $stmt->get_result();
		if ($rec && $rec->num_rows == 1 && $row = $rec->fetch_assoc()) {
			if ($row['usr_password'] == $password) {
				$this->load((int) $row['usr_id']);
				if ($row['usr_activate'] == '1') {
					$this->set_login_session(md5(uniqid()), (int) $row['usr_id']);
					if ($rememberuser) {
						$uni = md5(uniqid());
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
					throw new \System\Exceptions\HR\InactiveAccountException();
				}
			} else {
				$stmt->close();
				throw new \System\Exceptions\HR\InvalidLoginException();
			}
		} else {
			$stmt->close();
			throw new \System\Exceptions\HR\InvalidLoginException();
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
			if (isset($_COOKIE) && sizeof($_COOKIE) > 0 && isset($_COOKIE['cur'])) {
				$this->app->db->query("DELETE FROM cookies WHERE id='{$_COOKIE['cur']}' AND data='" . $this->app->user->info->id . "';");
			}
			if (isset($_SESSION["sur"])) {
				$uni = ($_SESSION["sur"]);
				$this->app->db->query("DELETE FROM users_sessions WHERE usrses_session_id='$uni';");
				unset($uni);
			}
			//setcookie("cur", "", time() - 3600);
			$this->info = new PersonData();
			$this->company = null;
			$this->account = new Account();
			$this->logged = false;
		}
		return true;
	}
}