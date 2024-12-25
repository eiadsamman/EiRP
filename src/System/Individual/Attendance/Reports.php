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

		$date_start_shift   = new \DateTime();
		$date_start_shift->modify("-2 month");
		$r="SELECT
    			COUNT(labour_track.ltr_usr_id) AS company_count
			FROM
				labour_track  JOIN acc_accounts ON ltr_prt_id = prt_id AND prt_company_id = '{$company_id}'
			WHERE
				ltr_otime IS NULL AND ltr_ctime > '{$date_start_shift->format("Y-m-d")}' 
			";

		
		$r = $this->app->db->query($r);

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