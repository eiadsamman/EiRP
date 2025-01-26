<?php
declare(strict_types=1);
namespace System\Controller\Personalization;

abstract class Personalization
{
	protected int $identifier;
	public function __construct(protected \System\App $app)
	{

	}

	public function register(int $id): bool|null
	{
		if ($this->app->user->logged) {
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
		return false;
	}
	public function list(): \Generator
	{
		return new \Generator;
	}

	public static function purgeAllPreferences($app): bool
	{
		if ($app->user->logged) {
			$result = $app->db->query("DELETE FROM user_settings WHERE usrset_usr_id = {$app->user->info->id}");
			return $result ? true : false;
		}
		return false;
	}

}