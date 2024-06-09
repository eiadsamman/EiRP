<?php
declare(strict_types=1);
namespace System\Views\Chunk;

class ForexTable extends \System\Views\Chunk\Chunk
{
	protected function json(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		if (
			$r = $this->app->db->query(
				"SELECT 
					curexg_from ,curexg_to,curexg_value,curexg_sell,
					cur_name,cur_shortname,cur_symbol,cur_default
				FROM
					currency_exchange 
						JOIN currencies ON cur_id = curexg_from
			"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": {$row['curexg_from']}" . ",";
				$output .= "\"name\": \"{$row['cur_name']}\"" . ",";
				$output .= "\"shortname\": \"{$row['cur_shortname']}\"" . ",";
				$output .= "\"symbol\": \"{$row['cur_symbol']}\"" . ",";
				$output .= "\"rate_buy\": " . ((float) $row['curexg_value']) . ",";
				$output .= "\"rate_sell\": " . ((float) $row['curexg_sell']) . "  ,";
				$output .= "\"timestamp\": 0" . "";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}