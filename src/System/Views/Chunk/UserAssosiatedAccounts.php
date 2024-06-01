<?php
declare(strict_types=1);
namespace System\Views\Chunk;

class UserAssosiatedAccounts extends \System\Views\Chunk\Chunk
{
	protected function json(): void
	{
		$this->headerJSONCacheGzip();
		if (!$this->app->user->logged) {
			echo gzencode("[]");
			exit;
		}

		$output = "[";
		$smart  = "";
		if (
			$r = $this->app->db->query(
				"SELECT 
					prt_id, prt_name, prt_company_id, prt_currency,
					upr_prt_inbound, upr_prt_outbound, upr_prt_fetch, upr_prt_view,
					cur_id, cur_name,cur_shortname,cur_symbol
				FROM 
					acc_accounts
						JOIN user_partition ON upr_prt_id = prt_id AND upr_usr_id = {$this->app->user->info->id} 
						JOIN currencies ON cur_id = prt_currency
				"
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart;
				$arr    = array(
					"company" => (int) $row['prt_company_id'],
					"id" => (int) $row['prt_id'],
					"name" => $row['prt_name'],
					"currency" => array(
						"id" => (int) $row['cur_id'],
						"name" => $row['cur_name'],
						"shortname" => $row['cur_shortname'],
						"symbol" => $row['cur_symbol'],
					),
				);
				$output .= json_encode($arr);
				$smart = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}