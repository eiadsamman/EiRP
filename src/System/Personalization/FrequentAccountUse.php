<?php
namespace System\Personalization;

class FrequentAccountUse
{
	public function __construct(protected \System\App $app, ?int $register_account = null)
	{
		if ($register_account != null) {
			$this->registerAccountUse($register_account);
		}
	}
	public function registerAccountUse(int $id)
	{
		$this->app->db->query(
			"INSERT INTO 
				user_settings (usrset_usr_id,usrset_type,usrset_usr_defind_name,usrset_value,usrset_time) 	
			VALUES ({$this->app->user->info->id}," . \System\Personalization\Identifiers::SystemCountAccountOperation->value . ",{$id}, 1 ,NOW()) 
			ON DUPLICATE KEY UPDATE usrset_value = usrset_value+1;"
		);
	}
}