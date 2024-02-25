<?php

declare(strict_types=1);

namespace System\Individual\Attendance;

class Reports
{
	public function __construct(protected \System\App $app)
	{

	}

	public function OngoingAttendance(?int $company_id = null): int|array
	{

		$r = $this->app->db->query(
			"SELECT COUNT(1) AS company_count, lbr_company
			FROM 
				labour 
				JOIN labour_track ON lbr_id = ltr_usr_id 
				JOIN user_company ON lbr_company = urc_usr_comp_id AND urc_usr_id = {$this->app->user->info->id}
			WHERE 
				ltr_otime IS NULL
				" . (is_null($company_id) ? "" : " AND lbr_company = {$company_id} ") . "
			GROUP BY 
				lbr_company
			"
		);

		if ($r) {
			if (is_null($company_id)) {
				$output = array();
				while ($row = $r->fetch_assoc()) {
					$output[$row['lbr_company']] = empty($row['company_count']) ? 0 : (int) $row['company_count'];
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