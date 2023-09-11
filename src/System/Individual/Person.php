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

	public  function bookmark_list(): array
	{
		$output = [];
		$result = $this->app->db->query(
			"SELECT 
					trd_directory, pfl_value, trd_id, trd_attrib4, trd_attrib5, bookmark_id
				FROM 
					pagefile 
					JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
					JOIN 
						pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id=" . $this->info->permissions . "
							LEFT JOIN user_settings ON usrset_usr_defind_name=trd_id AND usrset_usr_id=" . $this->info->id . " AND usrset_name='system_count_page_visit'	
					JOIN (
						SELECT
							usrset_usr_defind_name AS bookmark_page_id, usrset_id AS bookmark_id
						FROM 
							user_settings
						WHERE 
							usrset_usr_id= " . $this->info->id . " AND usrset_name=\"system_user_bookmark\"
						) AS bookmarks  ON bookmarks.bookmark_page_id = trd_id
				WHERE 
					trd_enable = 1 
				ORDER BY
					(usrset_value+0) DESC,pfl_value
					;"
		);
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$output[] = $row;
			}
		}
		return $output;
	}

	public  function bookmark_status(int $pagefile_id): bool
	{
		$result = $this->app->db->query("SELECT usrset_id AS bookmarks_count FROM user_settings WHERE usrset_usr_id= " . $this->info->id . " AND usrset_name=\"system_user_bookmark\" AND usrset_usr_defind_name=$pagefile_id;");
		if ($result && $row = $result->num_rows > 0) {
			return true;
		}
		return false;
	}
	public  function bookmark_remove(int $pagefile_id): bool
	{
		$result = $this->app->db->query("DELETE FROM user_settings WHERE usrset_usr_id= " . $this->info->id . " AND usrset_name=\"system_user_bookmark\" AND usrset_usr_defind_name=$pagefile_id;");
		if ($result && $row = $this->app->db->affected_rows > 0) {
			return true;
		}
		return false;
	}

	public  function bookmark_add(int $pagefile_id): bool|null
	{
		#return pagefile 

		$result = $this->app->db->query("SELECT usrset_id AS bookmarks_count FROM user_settings WHERE usrset_usr_id= " . $this->info->id . " AND usrset_name=\"system_user_bookmark\" AND usrset_usr_defind_name=$pagefile_id;");
		if ($result && $row = $result->num_rows > 0) {
			return null;
		}

		$stmt = $this->app->db->prepare(
			"INSERT INTO user_settings (usrset_usr_id, usrset_name, usrset_usr_defind_name) 
				VALUES (" . $this->info->id . ", \"system_user_bookmark\" , ?);"
		);
		if ($stmt) {
			$stmt->bind_param("i", $pagefile_id);
			try {
				if ($stmt->execute()) {
					$stmt->close();
					return true;
				}
			} catch (\mysqli_sql_exception $e) {
				return false;
			}
			$stmt->close();
		}
		return false;
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
					LEFT JOIN uploads on lbr_id=up_rel AND up_deleted=0 AND (up_pagefile=" . $this->app::FILE['Person']['Photo'] . ")
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
