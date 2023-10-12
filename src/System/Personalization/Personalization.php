<?php
namespace System\Personalization;

abstract class Personalization
{

	protected int $identifier;
	public function __construct(protected \System\App $app)
	{

	}

	public function register(int $id): bool|null
	{
		$result = $this->app->db->query(
			"INSERT INTO 
				user_settings 
					(usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value) 
			VALUES 
					({$this->app->user->info->id},{$this->identifier}, $id, 1) 
			ON DUPLICATE KEY UPDATE 
				usrset_value = usrset_value + 1;"
		);
		return $result ? true : false;
	}
	public function list(): \Generator
	{
		return new \Generator;
	}

	public function purgeAllPreferences(): void
	{
		echo "Are you sure?";
		//$this->app->db->query("");
	}

}