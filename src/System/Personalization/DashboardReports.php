<?php

namespace System\Personalization;


class DashboardReports extends Personalization
{
	protected int $identifier = Identifiers::SystemDashboard->value;

	public function __construct(protected \System\App $app)
	{
	}

	public function update(array $order_array): bool
	{
		$stmt  = $this->app->db->prepare(
			"INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value, usrset_time) 
			VALUES ({$this->app->user->info->id}, {$this->identifier}, ? , ?, ?) 
			ON DUPLICATE KEY UPDATE usrset_value = ? , usrset_time = ?;"
		);
		$order = $pageid = $state = 0;
		$stmt->bind_param("iiiii", $pageid, $order, $state, $order, $state);
		foreach ($order_array as $v) {
			$pageid = (int) $v[0];
			$state  = (int) $v[1] == 1 ? 0 : null;
			$order++;
			$stmt->execute();
		}
		$stmt->close();
		return true;
	}
	public function register(int $id): bool
	{
		return true;
	}


	public function list(bool $only_selected = null): \Generator
	{
		try {
			$result = $this->app->db->query(
				"SELECT 
					trd_directory, pfl_value, trd_id, trd_attrib4, trd_attrib5, usrset_time
				FROM 
					pagefile 
					JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id = 1
					JOIN pagefile_permissions ON pfp_trd_id = trd_id AND pfp_per_id = {$this->app->user->info->permissions} AND (pfp_value & b'1000') > 0
					LEFT JOIN user_settings ON usrset_usr_defind_name = trd_id AND usrset_usr_id = {$this->app->user->info->id} AND usrset_type = {$this->identifier}
					
				WHERE 
					trd_enable = 1 AND trd_parent = 73
					" . ($only_selected ? " AND usrset_time IS NOT NULL " : "") . "
				ORDER BY 
					(usrset_value + 0) ASC, trd_zorder ASC;"
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

	public function overview(bool $only_selected = null): \Generator
	{
		try {
			$result = $this->app->db->query(
				"SELECT 
					trd_directory, pfl_value, trd_id, trd_attrib4, trd_attrib5, usrset_time
				FROM 
					pagefile 
					JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id = 1
					JOIN pagefile_permissions ON pfp_trd_id = trd_id AND pfp_per_id = {$this->app->user->info->permissions} AND (pfp_value & b'1000') > 0
					LEFT JOIN user_settings ON usrset_usr_defind_name = trd_id AND usrset_usr_id = {$this->app->user->info->id} AND usrset_type = {$this->identifier}
					
				WHERE 
					trd_enable = 1 AND trd_parent = 19
					" . ($only_selected ? " AND usrset_time IS NOT NULL " : "") . "
				ORDER BY 
					(usrset_value + 0) ASC, trd_zorder ASC;"
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


}