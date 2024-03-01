<?php
namespace System\Personalization;

class ThemeDarkMode extends Personalization
{

	

	public string $mode = "light";
	public function __construct(protected \System\App $app)
	{
		$this->identifier = Identifiers::SystemDarkMode->value;
		$this->getMode();
	}
	public function register(int $id): bool
	{
		$this->app->db->query("INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value) 
			VALUES ({$this->app->user->info->id}, {$this->identifier}, 0 , $id) 
			ON DUPLICATE KEY UPDATE usrset_value = $id;");
		$this->mode = $id == 0 ? "light" : "dark";
		return true;
	}


	public function getMode(): string
	{
		$result = $this->app->db->query(
			"SELECT 
					usrset_value 
				FROM 
					user_settings 
				WHERE
					usrset_usr_id = {$this->app->user->info->id} AND 
					usrset_type = {$this->identifier} AND
					usrset_usr_defind_name = 0 ;"
		);
		if ($result && $result->num_rows > 0 && $row = $result->fetch_row()) {
			$this->mode = $row[0] == 1 ? "dark" : "light";
		}
		return "light";
	}

}