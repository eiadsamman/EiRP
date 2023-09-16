<?php

declare(strict_types=1);

namespace System;

class SmartListObject
{
	protected App $app;
	function __construct(App &$app, int $language = 1)
	{
		$this->app = $app;
	}
	private function template(string $id, string $value, string $keywords = null): string
	{
		$output = "<option";
		$output .= " data-id=\"" . htmlentities($id, ENT_QUOTES, "UTF-8", false) . "\"";
		$output .= !is_null($keywords) ? " data-keywords=\"" . htmlentities($keywords, ENT_QUOTES, "UTF-8", false) . "\"" : "";
		$output .= ">";
		$output .= htmlentities($value, ENT_QUOTES, "UTF-8", false);
		$output .= "</option>";
		return  $output;
	}

	public function system_accounts(int $company_filter = null): string
	{
		$output = "";
		try {
			if ($r = $this->app->db->query("SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_name, cur_shortname
			FROM
				view_financial_accounts
			WHERE
				1 " . ($company_filter != null ? " AND comp_id = {$company_filter}" : "") . "
			")) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template($row['prt_id'], "[{$row['cur_shortname']}] {$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}", "");
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}
		return $output;
	}




	public function user_accounts(?\System\Finance\AccountRole &$role = null, ?int $company_id = null): string
	{
		$output = "";
		try {
			if ($r = $this->app->db->query(
				"SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_name, cur_shortname, usrset_value
			FROM
				view_financial_accounts
				JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id=" . $this->app->user->info->id . (!is_null($role) ? " AND " . $role->sqlClause() : "") . "
					LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id=" . $this->app->user->info->id . " AND usrset_name = 'system_count_account_selection'
				" .
					(!is_null($company_id) ? "WHERE comp_id = " . $this->app->user->company->id . " " : "")
					. "ORDER BY(usrset_value + 0) DESC"
			)) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template(
						$row['prt_id'],
						"[" . $row['cur_shortname'] . "] " . ($company_id != null ? "" : $row['comp_name'] . ": ") . $row['ptp_name'] . ": " . $row['prt_name'],
						""
					);
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}

		return $output;
	}
	/**
	 * Returns SmartList Accounts List for logged user and user selected company
	 * Filter accounts by (in bound, out bound, accessiblity, and balance viewable)
	 *
	 * @param bool $inbound Specifiy if the account can collect money `inbound` term
	 * @param bool $outbound Specifiy if the account can pay money `outbound` term
	 * @param bool $accessible Specifiy if the account is accessible by the user
	 * @param bool $viewable Specifiy if the account is viewable by the user and if account balance is viewable
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */



	public function user_accounts_inbound(): string
	{
		$role = new \System\Finance\AccountRole();
		$role->inbound = true;
		return $this->user_accounts($role);
	}
	public function user_accounts_outbound(): string
	{
		$role = new \System\Finance\AccountRole();
		$role->outbound = true;
		return $this->user_accounts($role);
	}



	/**
	 * Returns SmartList Accounts List for finanace categories\sub categories
	 *
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */
	public function financial_categories(): string
	{
		$output = "";
		if ($r = $this->app->db->query("SELECT 
				 acccat_id,CONCAT(accgrp_name,\": \",acccat_name) AS category_name, acccat_name, accgrp_name
						FROM acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group
			")) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['acccat_id'], "{$row['acccat_name']}: {$row['accgrp_name']}", $row['category_name']);
			}
		}

		return $output;
	}

	/**
	 * Returns SmartList Accounts List for system employee\client\vendor
	 *
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */
	public function system_individual(int $company_filter = null): string
	{
		$output = "";
		try {
			if ($r = $this->app->db->query(
				"SELECT 
				usr_id, 
				usr_firstname, 
				usr_lastname
			FROM 
				users 
					JOIN labour ON lbr_id=usr_id
			WHERE 
				1 " . ($company_filter != null ? " AND lbr_company=" . $company_filter : "") . ";"
			)) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template($row['usr_id'], $row['usr_firstname'] . (!is_null($row['usr_lastname']) ? " " . $row['usr_lastname'] : ""));
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}
		return $output;
	}

	/**
	 * Returns SmartList Accounts List for fianance general beneficiary list from acc_main table
	 *
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */
	public function financial_beneficiary(): string
	{
		$output = "";
		try {
			if ($r = $this->app->db->query("SELECT acm_beneficial, count(acm_beneficial) as trend FROM acc_main GROUP BY acm_beneficial ORDER BY trend DESC")) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template($row['acm_beneficial'], $row['acm_beneficial']);
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}
		return $output;
	}
}
