<?php

declare(strict_types=1);

namespace System\Individual;

class Employee extends Person
{
	public function GetRunningAttendance(): array
	{
		if (!$this->info->id) {
			//throw new PersonNotFoundException("No person loaded", 22190702);
		}
		$output = array(
			"id" => false,
			"time" => 0,
		);

		$r = $this->app->db->query(
			"SELECT 
				ltr_id, UNIX_TIMESTAMP(ltr_ctime) AS ltr_ctime
			FROM 
				labour_track 
			WHERE
				ltr_otime IS NULL AND ltr_usr_id = " . $this->info->id . ";");

		if ($r && $row = $r->fetch_assoc()) {
			$output = array(
				"id" => $row['ltr_id'],
				"time" => $row['ltr_ctime'],
			);
		}
		return $output;
	}
}
