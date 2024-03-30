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
	private function template(string $id, string $value, string $keywords = null, bool $selected = false, ?string $customFields = ""): string
	{
		$output = "<option" . ($selected ? " selected=\"selected\"" : "") . "";
		$output .= " data-id=\"" . htmlentities($id, ENT_QUOTES, "UTF-8", false) . "\"";
		$output .= !is_null($keywords) ? " data-keywords=\"" . htmlentities($keywords, ENT_QUOTES, "UTF-8", false) . "\"" : "";
		$output .= $customFields;
		$output .= ">";
		$output .= htmlentities($value, ENT_QUOTES, "UTF-8", false);
		$output .= "</option>";
		return $output;
	}



	public function systemAccounts(int $company_filter = null): string
	{
		$output = "";
		try {
			if (
				$r = $this->app->db->query("SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_name, cur_shortname
			FROM
				view_financial_accounts
			WHERE
				1 " . ($company_filter != null ? " AND comp_id = {$company_filter}" : "") . "
			")
			) {
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




	public function userAccounts(
		?\System\Finance\AccountRole &$role = null,
		?int $company_id = null,
		mixed $select = null,
		?array $exclude = null,
		?int $identity = null
	): string {
		$output = "";
		if ($identity == null) {
			$identity = \System\Personalization\Identifiers::SystemCountAccountSelection->value;
		}
		try {
			if (
				$r = $this->app->db->query(
					"SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_id, cur_shortname, usrset_value
			FROM
				view_financial_accounts
				JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id=" . $this->app->user->info->id . (!is_null($role) ? " AND " . $role->sqlClause() : "") . "
					LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id=" . $this->app->user->info->id . " AND usrset_type = " . $identity . "
				" .
					(!is_null($company_id) ? "WHERE comp_id = " . $this->app->user->company->id . " " : "")
					. "ORDER BY(usrset_value + 0) DESC, prt_id "
				)
			) {
				while ($row = $r->fetch_assoc()) {
					if ($exclude != null && in_array((int) $row['prt_id'], $exclude)) {
						continue;
					}
					$output .= $this->template(
						$row['prt_id'],
						"[" . $row['cur_shortname'] . "] " . ($company_id != null ? "" : $row['comp_name'] . ": ") . $row['ptp_name'] . ": " . $row['prt_name'],
						"",
						$select == $row['prt_id'],
						" data-curId=\"{$row['cur_id']}\" "
					);
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}

		return $output;
	}


	public function userCompanies(
		mixed $select = null,
		?array $exclude = null,
		?int $identity = null
	): string {
		$output = "";
		if ($identity == null) {
			$identity = \System\Personalization\Identifiers::SystemCountCompanySelection->value;
		}
		try {
			if (
				$r = $this->app->db->query(
					"SELECT 
						comp_id, comp_name
					FROM
						companies
							JOIN user_company ON comp_id = urc_usr_comp_id AND urc_usr_id = {$this->app->user->info->id} 
							LEFT JOIN user_settings ON usrset_usr_defind_name = comp_id AND usrset_usr_id = {$this->app->user->info->id} AND usrset_type = {$identity}
					ORDER BY 
						(usrset_value + 0) DESC, comp_id "
				)
			) {
				while ($row = $r->fetch_assoc()) {
					if ($exclude != null && in_array((int) $row['comp_id'], $exclude)) {
						continue;
					}
					$output .= $this->template(
						$row['comp_id'],
						$row['comp_name'],
						"",
						$select == $row['comp_id']
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

	public function userAccountsInbound(mixed $select = null, ?array $exclude = null, ?int $identity = null): string
	{
		$role          = new \System\Finance\AccountRole();
		$role->inbound = true;
		return $this->userAccounts(role: $role, select: $select, exclude: $exclude, identity: $identity);
	}
	public function userAccountsOutbound(mixed $select = null, ?array $exclude = null, ?int $identity = null): string
	{
		$role           = new \System\Finance\AccountRole();
		$role->outbound = true;
		return $this->userAccounts(role: $role, select: $select, exclude: $exclude, identity: $identity);
	}


	/**
	 * Returns SmartList Accounts List for system employee\client\vendor
	 *
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */
	public function systemIndividual(int $company_filter = null, mixed $select = null): string
	{
		$output = "";
		try {
			if (
				$r = $this->app->db->query(
					"SELECT 
				usr_id, 
				usr_firstname, 
				usr_lastname
			FROM 
				users 
					JOIN labour ON lbr_id=usr_id
			WHERE 
				1 " . ($company_filter != null ? " AND lbr_company=" . $company_filter : "") . ";"
				)
			) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template(
						$row['usr_id'],
						$row['usr_firstname'] . (!is_null($row['usr_lastname']) ? " " . $row['usr_lastname'] : ""),
						null,
						((int) $select == $row['usr_id']) ? true : false

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
	 * Returns SmartList Accounts List for finanace categories\sub categories
	 *
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */
	public function financialCategories(): string
	{
		$output = "";
		if (
			$r = $this->app->db->query("SELECT 
				 acccat_id,CONCAT(accgrp_name,\": \",acccat_name) AS category_name, acccat_name, accgrp_name
						FROM acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group
			")
		) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['acccat_id'], "{$row['acccat_name']}: {$row['accgrp_name']}", $row['category_name']);
			}
		}

		return $output;
	}


	/**
	 * Returns SmartList Accounts List for fianance general beneficiary list from acc_main table
	 *
	 * @return string HTML string `<option />` tags based on `System\SmartListObject\template` function
	 */
	public function financialBeneficiary(mixed $select = null): string
	{
		$output = "";
		try {
			if ($r = $this->app->db->query("SELECT acm_beneficial, count(acm_beneficial) as trend FROM acc_main GROUP BY acm_beneficial ORDER BY trend DESC")) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template(
						$row['acm_beneficial'],
						$row['acm_beneficial'],
						null,
						$select == $row['acm_beneficial']
					);
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}
		return $output;
	}


	public function financialTransactionNature(mixed $select = null): string
	{
		$output = "";
		foreach (\System\Finance\Transaction\Nature::array() as $k => $v) {
			$output .= $this->template((string) $k, $v, null, (int) $k == (int) $select);
		}

		return $output;
	}

	public function hrPaymentMethod(): string
	{
		$output = "";
		try {
			if ($r = $this->app->db->query("SELECT lbr_mth_id, lbr_mth_name FROM labour_method ORDER BY lbr_mth_id")) {
				while ($row = $r->fetch_assoc()) {
					$output .= $this->template($row['lbr_mth_id'], $row['lbr_mth_name']);
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
			return "<option>" . $e->getCode() . " Server error!</option>";
		}
		return $output;
	}




}