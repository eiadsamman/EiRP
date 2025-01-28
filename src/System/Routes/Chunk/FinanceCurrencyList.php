<?php
declare(strict_types=1);
namespace System\Routes\Chunk;

class FinanceCurrencyList extends \System\Routes\Chunk\Chunk
{
	protected function slo(): void
	{

		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		if (
			$r = $this->app->db->query(
				"SELECT 
				cur_id, cur_name, cur_shortname, cur_symbol
			FROM 
				currencies;"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": \"{$row['cur_id']}\",";
				$output .= "\"value\": \"[" . $row['cur_shortname'] . "] " . addslashes($row['cur_name']) . "\", ";
				$output .= "\"shortname\": \"{$row['cur_shortname']}\",";
				$output .= "\"symbol\": \"{$row['cur_symbol']}\"";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}