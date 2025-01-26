<?php

declare(strict_types=1);

namespace System\Controller\Individual;

class Employee extends Individual
{
	public function GetRunningAttendance(): array
	{
		$output = ["id" => false, "time" => 0];
		$r = $this->app->db->query("SELECT ltr_id, ltr_ctime
			FROM labour_track 
			WHERE ltr_ctime IS NOT NULL AND ltr_otime IS NULL AND ltr_usr_id = " . $this->info->id . ";"
		);
		if ($r && $row = $r->fetch_assoc()) {
			$output = ["id" => $row['ltr_id'], "time" => new \DateTime($row['ltr_ctime'])];
		}
		return $output;
	}
}
