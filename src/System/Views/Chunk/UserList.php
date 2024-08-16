<?php
declare(strict_types=1);
namespace System\Views\Chunk;

class UserList extends \System\Views\Chunk\Chunk
{
	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		if (
			$r = $this->app->db->query(
				"SELECT 
				usr_id,
				CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS userName,
				comp_id,
				comp_name
			FROM 
				users LEFT JOIN companies ON comp_id = usr_entity
			ORDER BY 
				FIELD(comp_id, {$this->app->user->company->id}) DESC , usr_id
			"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": \"{$row['usr_id']}\",";
				$output .= "\"value\": \"" . addslashes(trim($row['userName'])) . "\" ";
				$output .= ($row['comp_id'] == $this->app->user->company->id ? "" : ",\"highlight\": \"" . addslashes($row['comp_name']) . "\" ");
				$output .= ",\"keywords\": \"{$row['usr_id']}  " . ($row['comp_id'] != $this->app->user->company->id ? $row['comp_name'] : "") . "\"";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}