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
		if ($r = $this->app->db->query("SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_name, cur_shortname
			FROM
				`view_financial_accounts`
			WHERE
				1 " . ($company_filter != null ? " AND comp_id = {$company_filter}" : "") . "
			")) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['prt_id'], "[{$row['cur_shortname']}] {$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}", "");
			}
		}

		return $output;
	}

	public function financial_accounts(bool|null $inbound = null, bool|null $outbound = null, bool|null $accessible = null, bool|null $viewable = null): string
	{
		$output = "";
		$condition_modifiers = "";
		if ($inbound != null) {
			$condition_modifiers .= " AND upr_prt_inbound = 1";
		}
		if ($outbound != null) {
			$condition_modifiers .= " AND upr_prt_outbound = 1";
		}
		if ($accessible != null) {
			$condition_modifiers .= " AND upr_prt_fetch=1";
		}

		if ($r = $this->app->db->query("SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_name, cur_shortname, usrset_value
			FROM
				`view_financial_accounts`
				JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id=" . $this->app->user->info->id . " $condition_modifiers
				LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id=" . $this->app->user->info->id . " AND usrset_name = 'system_count_account_selection'
			ORDER BY
				(usrset_value + 0) DESC
			")) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['prt_id'], "[{$row['cur_shortname']}] {$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}", "");
			}
		}

		return $output;
	}

	public function financial_company_accounts(bool|null $inbound = null, bool|null $outbound = null, bool|null $accessible = null, bool|null $viewable = null): string
	{
		$output = "";
		$condition_modifiers = "";
		if ($inbound != null) {
			$condition_modifiers .= " AND upr_prt_inbound = 1";
		}
		if ($outbound != null) {
			$condition_modifiers .= " AND upr_prt_outbound = 1";
		}
		if ($accessible != null) {
			$condition_modifiers .= " AND upr_prt_fetch=1";
		}

		if ($r = $this->app->db->query("SELECT 
				prt_id, comp_name, ptp_name, prt_name, cur_name, cur_shortname, usrset_value
			FROM
				`view_financial_accounts`
				JOIN user_partition ON prt_id = upr_prt_id AND upr_usr_id=" . $this->app->user->info->id . " $condition_modifiers
				LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id=" . $this->app->user->info->id . " AND usrset_name = 'system_count_account_selection'
			WHERE
				comp_id = " . $this->app->user->company->id . "
			ORDER BY
				(usrset_value + 0) DESC
			")) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['prt_id'], "[{$row['cur_shortname']}] {$row['ptp_name']}: {$row['prt_name']}", "");
			}
		}

		return $output;
	}

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
	public function hr_person(int $company_filter = null): string
	{
		$output = "";
		if ($r = $this->app->db->query(
			"SELECT usr_id, usr_firstname, usr_lastname
			FROM users JOIN labour ON lbr_id=usr_id
			WHERE (usr_attrib_i2 = 0 OR usr_attrib_i2 = 1) " . ($company_filter != null ? " AND lbr_company=" . $company_filter : "") . ";"
		)) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['usr_id'], $row['usr_firstname'] . " " . $row['usr_lastname']);
			}
		}
		return $output;
	}

	public function financial_beneficiary(): string
	{
		$output = "";
		if ($r = $this->app->db->query("SELECT acm_beneficial, count(acm_beneficial) as trend FROM acc_main GROUP BY acm_beneficial ORDER BY trend DESC")) {
			while ($row = $r->fetch_assoc()) {
				$output .= $this->template($row['acm_beneficial'], $row['acm_beneficial']);
			}
		}
		return $output;
	}
	public function financial_accounts_inbound(): string
	{
		return $this->financial_accounts(true, null);
	}
	public function financial_accounts_outbound(): string
	{
		return $this->financial_accounts(null, true);
	}
}
