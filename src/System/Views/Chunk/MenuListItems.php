<?php
declare(strict_types=1);
namespace System\Views\Chunk;

class MenuListItems extends \System\Views\Chunk\Chunk
{
	protected function json(): void
	{
		header('Content-Type: application/json; charset=utf-8', true);
	}

	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$ident  = \System\Personalization\Identifiers::SystemFrequentVisit->value;
		$q      = <<<SQL
			SELECT 
				trd_directory, CONCAT(trd_id,': ', pfl_value) AS pagefile_title
			FROM 
				pagefile 
				JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id = 1 
				JOIN 
					pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id = {$this->app->user->info->permissions}
						LEFT JOIN user_settings ON usrset_usr_defind_name = trd_id AND usrset_usr_id = {$this->app->user->info->id} 
						AND usrset_type = {$ident}
			WHERE 
				trd_enable = 1 AND trd_visible = 1
			ORDER BY
				(usrset_value + 0) DESC, pfl_value
			SQL;

		if ($r = $this->app->db->query($q)) {
			$smart = "";
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": \"{$row['trd_directory']}\",";
				$output .= "\"value\": \"" . addslashes($row['pagefile_title']) . "\"";
				//$output .= "highlight: \"\",";
				//$output .= "keywords: \"\",";
				//$output .= "selected: false,";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";

		echo gzencode($output);
	}
	protected function html(): void
	{
		$this->headerCache();
		header('Content-Type: text/html; charset=utf-8', true);
		header("Content-Encoding: gzip");

		$output    = "<b class=\"index-link\"><span style=\"color:#333;font-family:icomoon;\">&#xe600;</span><a class=\"alink\" href=\"\">Homepage</a></b>";
		$hierarchy = new \System\FileSystem\Hierarchy($this->app, $this->app->user->info->permissions);
		$output .= $hierarchy->render();

		echo gzencode($output);
	}

}