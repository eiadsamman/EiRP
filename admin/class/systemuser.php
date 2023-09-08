<?php

namespace System\Person;

include_once("admin/class/person.php");
include_once("admin/class/accounting.php");

use Exception;
use System\System;
use System\Company;
use Finance\Account;
use Finance\AccountRole;


class InvalidLoginDetailsException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class InactiveAccountException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class Currency
{
	public $id;
	public $name;
	public $symbol;
	public $shortname;
}

class CompanyReisteringException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}

//$row['usr_password']==hash("sha256",$_POST['log_password'])



class User extends Person
{
	public $logged = false;
	public $company;
	public $account;

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
		$rcomp = System::$sql->query("
			SELECT comp_id,comp_name,up_id
			FROM 
				companies 
					JOIN user_company ON urc_usr_id=" . static::$_user->info->id . " AND urc_usr_comp_id=comp_id
					JOIN user_settings ON usrset_usr_id=" . static::$_user->info->id . " AND usrset_name='system_working_company' AND usrset_usr_defind_name='UNIQUE' AND usrset_value=comp_id
					LEFT JOIN uploads ON up_rel=comp_id AND up_pagefile=" . System::FILE['Company']['Logo'] . "
			GROUP BY
				comp_id
			;");
		if ($rcomp && $rowcomp = System::$sql->fetch_assoc($rcomp)) {
			$this->company = new Company();
			$this->company->id = (int)$rowcomp['comp_id'];
			$this->company->name = $rowcomp['comp_name'];
			$this->company->logo = (int)$rowcomp['up_id'];
		}
		if ($this->company) {
			if ($racc = System::$sql->query("
					SELECT 
						prt_id,prt_name,cur_symbol,cur_name,cur_id,cur_shortname,upr_prt_inbound,upr_prt_outbound,upr_prt_fetch,upr_prt_view
					FROM 
						`acc_accounts` 
							JOIN currencies ON cur_id = prt_currency
							JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . static::$_user->info->id . " AND upr_prt_fetch=1
							JOIN user_settings ON usrset_usr_id = " . static::$_user->info->id . " AND usrset_name='system_working_account' AND usrset_usr_defind_name={$this->company->id} AND usrset_value=prt_id 
					WHERE
						prt_company_id=" . static::$_user->company->id . ";")) {

				if ($rowacc = System::$sql->fetch_assoc($racc)) {

					$this->account = new Account();
					$this->account->currency = new Currency();
					$this->account->role = new AccountRole();

					$this->account->id = (int)$rowacc['prt_id'];
					$this->account->name = $rowacc['prt_name'];

					$this->account->currency->id = (int)$rowacc['cur_id'];
					$this->account->currency->name = $rowacc['cur_name'];
					$this->account->currency->symbol = $rowacc['cur_symbol'];
					$this->account->currency->shortname = $rowacc['cur_shortname'];

					$this->account->role->inbound = isset($rowacc['upr_prt_inbound']) && (int)$rowacc['upr_prt_inbound'] == 1 ? true : false;
					$this->account->role->outbound = isset($rowacc['upr_prt_outbound']) && (int)$rowacc['upr_prt_outbound'] == 1 ? true : false;
					$this->account->role->fetch = isset($rowacc['upr_prt_fetch']) && (int)$rowacc['upr_prt_fetch'] == 1 ? true : false;
					$this->account->role->view = isset($rowacc['upr_prt_view']) && (int)$rowacc['upr_prt_view'] == 1 ? true : false;
				}
			}
		}
	}

	public function register_company(int $company_id): bool
	{
		$r = static::$sql->query("
					SELECT  comp_id,comp_name FROM companies
						JOIN user_company ON comp_id=urc_usr_comp_id AND urc_usr_id=" . static::$_user->info->id . " AND comp_id={$company_id};");
		if (static::$sql->num_rows($r) > 0) {
			if ($row = static::$sql->fetch_assoc($r)) {
				$r = static::$sql->query("INSERT INTO user_settings (usrset_usr_id,usrset_name,usrset_usr_defind_name,usrset_value,usrset_time) 
									VALUES (" . static::$_user->info->id . ",	'system_working_company','UNIQUE','{$row['comp_id']}',NOW()	) 
										ON DUPLICATE KEY UPDATE usrset_value='{$row['comp_id']}';");

				static::$sql->query("INSERT INTO user_settings (usrset_usr_id,usrset_name,usrset_usr_defind_name,usrset_value,usrset_time) 
									VALUES (" . static::$_user->info->id . ",'system_count_company_selection','{$row['comp_id']}','1',NOW()) 
										ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");
				if ($r) {
					return true;
				} else {
					throw new CompanyReisteringException("Company registering failed", 3);
				}
			}
		}

		return false;
	}

	public function register_account(int $account_id): bool
	{
		$r = static::$sql->query("
					SELECT prt_id,prt_name,cur_symbol,cur_name,cur_id,cur_shortname ,comp_id, upr_prt_inbound, upr_prt_outbound, upr_prt_fetch, upr_prt_view FROM 
						`acc_accounts`
							JOIN companies ON comp_id = prt_company_id
							LEFT JOIN currencies ON cur_id = prt_currency
							JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . static::$_user->info->id . " AND upr_prt_id={$account_id} AND upr_prt_fetch=1 ;");
		if (static::$sql->num_rows($r) > 0) {
			if ($row = static::$sql->fetch_assoc($r)) {
				$r = static::$sql->query("INSERT INTO user_settings (usrset_usr_id,usrset_name,usrset_usr_defind_name,usrset_value,usrset_time) 
									VALUES (" . static::$_user->info->id . ",'system_working_account','{$row['comp_id']}','{$row['prt_id']}',NOW()) ON DUPLICATE KEY UPDATE usrset_value='{$row['prt_id']}';");

				static::$sql->query("INSERT INTO user_settings (usrset_usr_id,usrset_name,usrset_usr_defind_name,usrset_value,usrset_time) 
								VALUES (" . static::$_user->info->id . ",'system_count_account_selection','{$row['prt_id']}','1',NOW()) ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");
				if ($r) {
					return true;
				} else {
					throw new CompanyReisteringException("Registering company failed", 3);
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
		$_SESSION["sur"] = $sessionid;
		System::$sql->query(
			sprintf(
				"INSERT INTO users_sessions SET usrses_session_id='%s', usrses_usr_id={$userid} ON DUPLICATE KEY UPDATE usrses_usr_id=usrses_usr_id;",
				$sessionid
			)
		);
	}

	public function login(string $username, string $password, bool $rememberuser = false): bool
	{
		$r = System::$sql->query(sprintf("SELECT usr_id,usr_username,usr_password,usr_activate FROM users WHERE usr_username='%s';", $username));

		if ($r && $row = System::$sql->fetch_assoc($r)) {
			if ($row['usr_password'] == $password) {
				$this->load((int) $row['usr_id']);
				if ($row['usr_activate'] == '1') {

					$this->set_login_session(md5(uniqid()), $row['usr_id']);


					if ($rememberuser) {
						$uni = md5(uniqid());
						$cookieage = time() + System::$rememberloginage;

						setcookie("cur", $uni, $cookieage, "/" . (System::$subdomain ? System::$subdomain . "/" : ""));
						System::$sql->query("INSERT INTO cookies SET id='$uni', access='" . time() . "', expires='$cookieage', data='{$row['usr_id']}' ON DUPLICATE KEY UPDATE 
							access='" . time() . "', expires='$cookieage', data='{$row['usr_id']}';");
					}

					$this->logged = true;
					return true;
				} else {
					throw new InactiveAccountException("Inactive account", 2);
				}
			} else {
				throw new InvalidLoginDetailsException("Invalid login details", 1);
			}
		} else {
			throw new InvalidLoginDetailsException("Invalid login details", 1);
		}
	}

	public function cookies_handler(string $cookie): bool
	{
		$r = System::$sql->query(sprintf("SELECT access,expires,data FROM cookies WHERE expires >= '" . time() . "' AND id='%s';", $cookie));

		if ($r && $row = System::$sql->fetch_assoc($r)) {

			$this->set_login_session(md5(uniqid()), $row['data']);
			$this->load($row['data']);
			return true;
		}
		return false;
	}

	public function logout(): bool
	{
		if ($this->info) {
			if ($this->info && isset($_COOKIE) && sizeof($_COOKIE) > 0 && isset($_COOKIE['cur'])) {
				System::$sql->query("DELETE FROM cookies WHERE id='{$_COOKIE['cur']}' AND data='" . static::$_user->info->id . "';");
			}
			if (isset($_SESSION["sur"])) {
				$uni = System::$sql->escape($_SESSION["sur"]);
				System::$sql->query("DELETE FROM users_sessions WHERE usrses_session_id='$uni';");
				unset($uni);
			}
			//setcookie("cur", "", time() - 3600);
			$this->info = null;
			$this->company = null;
			$this->account = null;
			$this->logged = false;
		}
		return true;
	}
}
