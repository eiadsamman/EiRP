<?php

namespace System\Personalization;


class Bookmark
{

	public function __construct(protected \System\App $app)
	{

	}

	public function setOrder(array $order_array): bool
	{
		$stmt = $this->app->db->prepare("UPDATE user_settings SET usrset_value = ? 
			WHERE 
				usrset_usr_id= {$this->app->user->info->id} AND 
				usrset_type = " . Identifiers::SystemUserBookmark->value . " AND 
				usrset_usr_defind_name = ?
				");
		$order = 0;
		$pageid = 0;
		$stmt->bind_param("ii", $order, $pageid);
		foreach ($order_array as $v) {
			$pageid = (int) $v;
			$order++;
			$stmt->execute();
		}
		$stmt->close();
		return true;
	}
	public function list(): \Generator
	{
		try {
			$result = $this->app->db->query(
				"SELECT 
					trd_directory, pfl_value, trd_id, trd_attrib4, trd_attrib5, bookmark_id
				FROM 
					pagefile 
					JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
					JOIN 
						pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id=" . $this->app->user->info->permissions . "
							LEFT JOIN user_settings ON usrset_usr_defind_name=trd_id AND usrset_usr_id=" . $this->app->user->info->id . " AND usrset_type='" . Identifiers::SystemUserBookmark->value . "'	
					JOIN (
						SELECT
							usrset_usr_defind_name AS bookmark_page_id, usrset_id AS bookmark_id
						FROM 
							user_settings
						WHERE 
							usrset_usr_id= " . $this->app->user->info->id . " AND usrset_type = " . Identifiers::SystemUserBookmark->value . "
						) AS bookmarks  ON bookmarks.bookmark_page_id = trd_id
				WHERE 
					trd_enable = 1 
				ORDER BY
					(usrset_value+0) ASC, usrset_id;"
			);
			if ($result) {
				while ($row = $result->fetch_assoc()) {
					yield $row;
				}
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
		}
	}

	public function isBookmarked(int $page_id): bool
	{
		$result = $this->app->db->query("SELECT usrset_id AS bookmarks_count FROM user_settings WHERE usrset_usr_id= " . $this->app->user->info->id . " AND usrset_type=" . Identifiers::SystemUserBookmark->value . " AND usrset_usr_defind_name=$page_id;");
		if ($result && $result->num_rows > 0) {
			return true;
		}
		return false;
	}
	public function remove(int $page_id): bool
	{
		try {
			$result = $this->app->db->query("DELETE FROM user_settings WHERE usrset_usr_id= " . $this->app->user->info->id . " AND usrset_type=" . Identifiers::SystemUserBookmark->value . " AND usrset_usr_defind_name=$page_id;");
			if ($result && $row = $this->app->db->affected_rows > 0) {
				return true;
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
		}
		return false;
	}

	public function add(int $page_id): bool|null
	{
		try {
			$result = $this->app->db->query("SELECT usrset_id AS bookmarks_count FROM user_settings WHERE usrset_usr_id= " . $this->app->user->info->id . " AND usrset_type=" . Identifiers::SystemUserBookmark->value . " AND usrset_usr_defind_name=$page_id;");
			if ($result && $result->num_rows > 0) {
				return null;
			}

			$stmt = $this->app->db->prepare("INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name) VALUES (" . $this->app->user->info->id . ", " . Identifiers::SystemUserBookmark->value . " , ?);");
			if ($stmt) {
				$stmt->bind_param("i", $page_id);
				if ($stmt->execute()) {
					$stmt->close();
					return true;
				}
				$stmt->close();
			}
		} catch (\mysqli_sql_exception $e) {
			$this->app->errorHandler->logError($e);
		}
		return false;
	}



}