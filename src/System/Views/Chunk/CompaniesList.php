<?php
declare(strict_types=1);
namespace System\Views\Chunk;

class CompaniesList extends \System\Views\Chunk\Chunk
{
	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$q      = <<<SQL
			SELECT 
				comp_id, comp_name
			FROM 
				companies 
			ORDER BY
				comp_name, comp_id
			SQL;

		if ($r = $this->app->db->query($q)) {
			$smart = "";
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": \"{$row['comp_id']}\",";
				$output .= "\"value\": \"" . addslashes($row['comp_name']) . "\"";
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

}