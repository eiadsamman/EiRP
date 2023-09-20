<?php

declare(strict_types=1);

namespace System\Individual;

class Person
{
	public bool $loaded = false;
	public PersonData $info;
	protected \System\App $app;

	public function __construct(&$app)
	{
		$this->app = $app;
		$this->info = new PersonData();
		$this->info->permissions = $this->app->base_permission;
	}

	

	public function load(int $person_id): bool
	{
		$query = $this->app->db->query(
			"SELECT 
				CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS empname,
				usr_id,
				up_id,
				lbr_resigndate,
				usr_username,
				usr_privileges,
				per_order,usr_activate
			FROM 
				labour 
					JOIN users ON usr_id=lbr_id
					LEFT JOIN permissions ON per_id = usr_privileges
					LEFT JOIN uploads on lbr_id=up_rel AND up_deleted=0 AND (up_pagefile=" . $this->app->scope->individual->portrait . ")
			WHERE
				lbr_id='" . (int)$person_id . "';"
		);

		if ($query && $row = $query->fetch_assoc()) {
			$this->loaded = true;
			$this->info = new PersonData();
			$this->info->id = (int) $row['usr_id'];
			$this->info->name = $row['empname'];
			$this->info->username = $row['usr_username'];
			$this->info->photoid = (int) $row['up_id'];
			$this->info->resigned = $row['lbr_resigndate'] === null ? false : true;
			$this->info->resignDate = $row['lbr_resigndate'];
			$this->info->permissions = (int) $row['usr_privileges'];
			$this->info->level = (int) $row['per_order'];
			$this->info->active = (int)$row['usr_activate'] == 1 ? true : false;

			return true;
		} else {
			throw new PersonNotFoundException("No matches found for given ID", 21001);
		}
	}
}
