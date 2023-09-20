<?php
namespace System\Personalization;

class FrequentVisit
{

	public function __construct(protected \System\App $app)
	{
	}
	public function registerVisit(int $id)
	{
		$this->app->db->query("INSERT INTO user_settings (usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value, usrset_time) 
			VALUES (" . $this->app->user->info->id . "," . Identifiers::SystemFrequentVisit->value . ", {$id} , 1 ,NOW()) ON DUPLICATE KEY UPDATE usrset_value = usrset_value + 1;");
	}
}