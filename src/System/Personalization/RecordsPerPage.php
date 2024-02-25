<?php
namespace System\Personalization;

class RecordsPerPage extends Personalization
{
	protected int $identifier = Identifiers::AccountCustomePerpage->value;
	private int $default = 25;

	public function __construct(protected \System\App $app)
	{

	}

	public function register(?int $id, ?int $perpage = 25): bool|null
	{
		$result = $this->app->db->query(
			"INSERT INTO 
				user_settings 
					(usrset_usr_id, usrset_type, usrset_usr_defind_name, usrset_value) 
			VALUES 
					({$this->app->user->info->id}, {$this->identifier}, " . (empty($id) ? "0" : $id) . ", {$perpage}) 
			ON DUPLICATE KEY UPDATE 
				usrset_value = $perpage;"
		);
		return $result ? true : false;
	}

	public function get(?int $id = null): int
	{
		$id = empty($id) ? 0 : (int) $id;
		$output = $this->default;
		$r_perpage = $this->app->db->query(
			"SELECT 
				usrset_value ,usrset_usr_defind_name 
			FROM 
				user_settings 
			WHERE 
				usrset_usr_id={$this->app->user->info->id} AND 
				usrset_type = {$this->identifier} AND
				(usrset_usr_defind_name = '$id' || usrset_usr_defind_name = '0')
				;"
		);
		if ($r_perpage) {
			$global = null;
			$specific = null;
			while ($row_perpage = $r_perpage->fetch_assoc()) {
				if ((int) $row_perpage['usrset_usr_defind_name'] == 0) {
					$global = $row_perpage['usrset_value'];
				} else {
					$specific = $row_perpage['usrset_value'];
				}
			}
			if (!empty($specific)) {
				$output = $specific;
			} elseif (!empty($global)) {
				$output = $global;
			}
		}
		return $output;
	}
}