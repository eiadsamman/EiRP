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
			"SELECT COUNT(1) AS company_count, usr_entity
			FROM 
				users
				JOIN labour_track ON usr_id = ltr_usr_id 
				JOIN user_company ON usr_entity = urc_usr_comp_id AND urc_usr_id = {$this->app->user->info->id}
			WHERE 
				ltr_otime IS NULL
				" . (is_null($company_id) ? "" : " AND usr_entity = {$company_id} ") . " AND 1
			GROUP BY 
				usr_entity
			"
		);

		if ($r) {
			if (is_null($company_id)) {
				$output = array();
				while ($row = $r->fetch_assoc()) {
					$output[$row['usr_entity']] = empty($row['company_count']) ? 0 : (int) $row['company_count'];
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