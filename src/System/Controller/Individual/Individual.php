<?php

declare(strict_types=1);

namespace System\Controller\Individual;

use System\Profiles\Gender;
use System\Profiles\IndividualProfile;


class Individual
{
	public bool $loaded = false;
	public IndividualProfile $info;
	protected \System\App $app;

	public function __construct(&$app)
	{
		$this->app               = $app;
		$this->info              = new IndividualProfile();
		$this->info->permissions = $this->app->base_permission;
	}


	public static function colorId(int $userId): string
	{
		return "hsl(" . ($userId * 10 % 360) . ", 75%, 50%)";
	}
	public function load(int $person_id): bool
	{
		$query = $this->app->db->query(
			"SELECT 
				usr_id,
				usr_firstname,
				usr_lastname,
				up_id,
				lbr_resigndate,
				usr_username,
				usr_privileges,
				per_order,
				usr_phone_list,
				usr_activate,
				usr_gender
			FROM 
				labour 
					JOIN users ON usr_id=lbr_id
					LEFT JOIN permissions ON per_id = usr_privileges
					LEFT JOIN uploads on lbr_id = up_rel AND up_deleted = 0 AND up_pagefile = " . \System\Lib\Upload\Type::HrPerson->value . " AND 1
			WHERE
				lbr_id = " . (int) $person_id . ";"
		);

		if ($query && $row = $query->fetch_assoc()) {
			$this->loaded              = true;
			$this->info                = new IndividualProfile();
			$this->info->id            = (int) $row['usr_id'];
			$this->info->contactNumber = $row['usr_phone_list'] ?? null;
			$this->info->firstname     = $row['usr_firstname'] ?? "";
			$this->info->lastname      = $row['usr_lastname'] ?? "";
			$this->info->username      = $row['usr_username'];
			$this->info->photoid       = (int) $row['up_id'];
			$this->info->resigned      = $row['lbr_resigndate'] === null ? false : true;
			$this->info->resignDate    = $row['lbr_resigndate'];
			$this->info->permissions   = (int) $row['usr_privileges'];
			$this->info->level         = (int) $row['per_order'];
			$this->info->active        = (int) $row['usr_activate'] == 1 ? true : false;
			$this->info->gender        = Gender::tryFrom((int) $row['usr_gender']);

			return true;
		} else {
			throw new \System\Core\Exceptions\HR\PersonNotFoundException("Profile doesn't exists", 21001);
		}
	}

	public function __toString(): string
	{
		return print_r([
			'id' => $this->info->id,
			'name' => $this->info->fullName(),
			'permissions' => $this->info->permissions,
		], true);
	}
}
