<?php

namespace System;


use Exception;
use mysqli_sql_exception;

class PermissionsBaseEntry extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class Company
{
	public $id;
	public $name;
	public $logo;
}


class Pool
{
	public static $sql;
	public static $user;
	public static $_user;
	public static $prefixList = array();
	public static $rememberloginage = (86400 * 7); //7 days
	public static $subdomain;
	public static $base_permission = 0;

	public static $operational_page = array(
		"index" => 19,
		"login" => 20,
	);
	//public static $


	public const FILE = array(
		"Person" => array(
			"Photo" => 189,
			"ID" => 190
		), "Company" => array(
			"Logo" => 242

		)
	);


	function __construct()
	{
	}

	public static function getBasePermission(): bool
	{
		
		if (basename(get_class(static::$sql)) == "SQL") {
			
			$lowsetlevel = static::$sql->query("SELECT per_id FROM permissions WHERE per_order = (SELECT MIN(per_order) FROM permissions); ");
			if ($lowsetlevel && $rowlowsetlevel = static::$sql->fetch_assoc($lowsetlevel)) {
				static::$base_permission = (int) $rowlowsetlevel['per_id'];
				
			}
		}
		
		if (static::$base_permission == 0) {
			return false;
		} else {
			return true;
		}
	}
	public static function buildPrefixList(): bool
	{
		static::$prefixList = array();
		$r = static::$sql->query("SELECT prx_id,prx_value,prx_placeholder FROM system_prefix;");
		if ($r) {
			while ($row = static::$sql->fetch_assoc($r)) {
				static::$prefixList[$row['prx_id']] = array($row['prx_value'], (int)$row['prx_placeholder']);
			}
		}
		return true;
	}

	public function TranslatePrefix(int $type, int $number): string
	{
		if (!is_array(static::$prefixList) || sizeof(static::$prefixList) == 0) {
			return (string)$number;
		}
		$type = (int)$type;
		if (isset(static::$prefixList[$type])) {
			return static::$prefixList[$type][0] . str_pad((string)$number, static::$prefixList[$type][1], "0", STR_PAD_LEFT);
		}
		return (string)$number;
	}
	public function paddingPrefix(int $type, int $number): string
	{
		if (!is_array(static::$prefixList) || sizeof(static::$prefixList) == 0) {
			return (string)$number;
		}
		$type = (int)$type;
		if (isset(static::$prefixList[$type])) {
			return str_pad((string)$number, static::$prefixList[$type][1], "0", STR_PAD_LEFT);
		}
		return (string)$number;
	}
	public static function bookmarksList(): array
	{
		$output = [];
		if (basename(get_class(static::$sql)) == "SQL") {
			$result = static::$sql->query(
				"SELECT 
					trd_directory, pfl_value, trd_id, trd_attrib4, trd_attrib5, bookmark_id
				FROM 
					pagefile 
					JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
					JOIN 
						pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id=" . static::$_user->info->permissions . "
							LEFT JOIN user_settings ON usrset_usr_defind_name=trd_id AND usrset_usr_id=" . static::$_user->info->id . " AND usrset_name='system_count_page_visit'	
					JOIN (
						SELECT
							usrset_usr_defind_name AS bookmark_page_id, usrset_id AS bookmark_id
						FROM 
							user_settings
						WHERE 
							usrset_usr_id= " . static::$_user->info->id . " AND usrset_name=\"system_user_bookmark\"
						) AS bookmarks  ON bookmarks.bookmark_page_id = trd_id
				WHERE 
					trd_enable = 1 
				ORDER BY
					(usrset_value+0) DESC,pfl_value
					;"
			);
			if ($result) {
				while ($row = static::$sql->fetch_assoc($result)) {
					$output[] = $row;
				}
			}
		}
		return $output;
	}

	public static function bookmarksStatus(int $pagefile_id): bool
	{
		if (basename(get_class(static::$sql)) == "SQL") {
			$result = static::$sql->query("SELECT usrset_id AS bookmarks_count FROM user_settings WHERE usrset_usr_id= " . static::$_user->info->id . " AND usrset_name=\"system_user_bookmark\" AND usrset_usr_defind_name=$pagefile_id;");
			if ($result && $row = static::$sql->num_rows($result) > 0) {
				return true;
			}
		}
		return false;
	}
	public static function bookmarkRemove(int $pagefile_id): bool
	{
		if (basename(get_class(static::$sql)) == "SQL") {
			$result = static::$sql->query("DELETE FROM user_settings WHERE usrset_usr_id= " . static::$_user->info->id . " AND usrset_name=\"system_user_bookmark\" AND usrset_usr_defind_name=$pagefile_id;");
			if ($result && $row = static::$sql->affected_rows() > 0) {
				return true;
			}
		}
		return false;
	}

	public static function bookmarkAdd(int $pagefile_id): bool|null
	{
		#return pagefile 
		if (basename(get_class(static::$sql)) == "SQL") {

			$result = static::$sql->query("SELECT usrset_id AS bookmarks_count FROM user_settings WHERE usrset_usr_id= " . static::$_user->info->id . " AND usrset_name=\"system_user_bookmark\" AND usrset_usr_defind_name=$pagefile_id;");
			if ($result && $row = static::$sql->num_rows($result) > 0) {
				return null;
			}


			$stmt = static::$sql->prepare(
				"INSERT INTO user_settings (usrset_usr_id, usrset_name, usrset_usr_defind_name) 
				VALUES (" . static::$_user->info->id . ", \"system_user_bookmark\" , ?);"
			);
			if ($stmt) {
				$stmt->bind_param("i", $pagefile_id);
				try {
					if ($stmt->execute()) {
						$stmt->close();
						return true;
					}
				} catch (mysqli_sql_exception $e) {
					return false;
				}
				$stmt->close();
			}
		}
		return false;
	}

	public static function formatTime(int $time, bool $include_seconds = true): string
	{
		$time = abs($time);
		$output = "";
		$output .= str_pad(floor(($time) / 60 / 60), 2, "0", STR_PAD_LEFT) . ":" . str_pad(floor(($time) / 60 % 60), 2, "0", STR_PAD_LEFT);
		if ($include_seconds)
			$output .= ":" . str_pad(floor(($time) % 60), 2, "0", STR_PAD_LEFT);
		return $output;
	}
}
