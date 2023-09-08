<?php

namespace System\Person;

include_once("admin/class/system.php");


use Exception;
use System\System;

class PersonNotFoundException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}

class PersonResignedException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}


class NotPersonLoaded extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}

class PersonData
{
	public $id = 0;
	public $name;
	public $username;
	public $permissions;
	public $level;
	public $photoid;
	public $resignDate;
	public $resigned;
}

class Person extends System
{
	public $info;
	public function __construct()
	{
		$this->info = new PersonData();
		$this->info->permissions = System::$base_permission;
	}

	public function load(int $person_id): bool
	{
		$query = System::$sql->query("
			SELECT 
				CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS empname,
				usr_id,
				up_id,
				lbr_resigndate,
				usr_username,
				usr_privileges,
				per_order
			FROM 
				labour 
					JOIN users ON usr_id=lbr_id
					LEFT JOIN permissions ON per_id = usr_privileges
					LEFT JOIN uploads on lbr_id=up_rel AND up_deleted=0 AND (up_pagefile=" . System::FILE['Person']['Photo'] . ")
			WHERE
				lbr_id='" . (int)$person_id . "';");

		if ($query && $row = System::$sql->fetch_assoc($query)) {
			$this->info = new PersonData();
			$this->info->id = (int) $row['usr_id'];
			$this->info->name = $row['empname'];
			$this->info->username = $row['usr_username'];
			$this->info->photoid = (int) $row['up_id'];
			$this->info->resigned = $row['lbr_resigndate'] === null ? false : true;
			$this->info->resignDate = $row['lbr_resigndate'];
			$this->info->permissions = (int) $row['usr_privileges'];
			$this->info->level = (int) $row['per_order'];
			return true;
		} else {
			throw new PersonNotFoundException("No matches found for given ID", 21001);
		}
	}
}
