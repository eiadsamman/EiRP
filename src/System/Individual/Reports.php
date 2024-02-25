<?php

declare(strict_types=1);

namespace System\Individual;

class Reports
{
	public function __construct(protected \System\App $app)
	{

	}

	public function RegisteredEmployees(?int $company_id = null): int|array
	{

		$r = $this->app->db->query(
			"SELECT COUNT(1) AS company_count, comp_id, comp_name
			FROM 
				companies 
					JOIN labour ON comp_id = lbr_company
					JOIN user_company ON comp_id = urc_usr_comp_id AND urc_usr_id = {$this->app->user->info->id}
			WHERE 
				lbr_resigndate IS NULL
				" . (is_null($company_id) ? "" : " AND comp_id = {$company_id} ") . "
			GROUP BY 
				comp_id;
			"
		);

		if ($r) {
			if (is_null($company_id)) {
				$output = array();
				while ($row = $r->fetch_assoc()) {
					$output[$row['comp_id']] = array($row['comp_name'], empty($row['company_count']) ? 0 : (int) $row['company_count']);
				}
				return $output;
			} else {
				$output = 0;
				if ($row = $r->fetch_assoc()) {
					$output = empty($row['company_count']) ? 0 : (int) $row['company_count'];
				}
				return $output;
			}
		} else {
			throw new \System\Exceptions\Instance\SQLException($this->app->db->error);
		}
	}


}