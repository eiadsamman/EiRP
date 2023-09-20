<?php
namespace System\Personalization;

class Personalization
{
	public function __construct(protected \System\App $app)
	{
	}

	public function migrate()
	{
		$map = array(
			"system_count_page_visit" => 101,
			"system_working_account" => 102,
			"system_working_company" => 103,
			"system_user_bookmark" => 104,
			"system_count_account_selection" => 105,
			"system_count_company_selection" => 106,
			"system_count_account_operation" => 108,
			"system_productiontrack_material" => 107,
			"system_productiontrack_section" => 109,
			"account_custome_perpage" => 201,
			"account_custome_query_save" => 202
		);

		foreach ($map as $k => $v) {
			$this->app->db->query("UPDATE user_settings SET usrset_type = {$v} WHERE usrset_name = '{$k}';");
			$this->app->db->query("DELETE FROM user_settings WHERE usrset_name = '';");
			$this->app->db->query("ALTER TABLE user_settings DROP INDEX usrset_usr_id, ADD UNIQUE usrset_usr_id (usrset_usr_id, usrset_type, usrset_usr_defind_name) USING BTREE;");
			//$this->app->db->query("ALTER TABLE user_settings DROP usrset_name;");
		}

	}
}