<?php

namespace System\Person;

include_once("admin/class/person.php");

use System\Pool;

class Employee extends Person
{

	public function GetRunningAttendance(): array
	{
		if (!$this->info->id) {
			throw new NotPersonLoaded("No person loaded", 22190702);
		}
		$output = array(
			"id" => false,
			"time" => 0,
		);
		$query = Pool::$sql->query("
			SELECT 
				ltr_id, UNIX_TIMESTAMP(ltr_ctime) AS ltr_ctime
			FROM 
				labour_track 
			WHERE
				ltr_otime IS NULL AND ltr_usr_id = " . $this->info->id . ";");

		if ($query && $row = Pool::$sql->fetch_assoc($query)) {
			$output = array(
				"id" => $row['ltr_id'],
				"time" => $row['ltr_ctime'],
			);
		}
		return $output;
	}
}
