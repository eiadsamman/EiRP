<?php
declare(strict_types=1);
namespace System\Routes\Chunk;

class CompanyAssosiatedAccounts extends \System\Routes\Chunk\Chunk
{
	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		if (!$this->app->user->logged) {
			echo gzencode("[]");
			exit;
		}

		$output = "[";
		$smart  = "";
		if (
			$r = $this->app->db->execute_query(
				"SELECT prt_id, prt_name, prt_term, cur_id, cur_name, cur_shortname, cur_symbol 
				FROM view_financial_accounts
				WHERE comp_id = ?"
				,
				[
					$this->app->user->company->id
				]
			)
		) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart;
				$arr    = array(
					"id" => (int) $row['prt_id'],
					"value" => "[" . $row['cur_shortname'] . "] " . $row['prt_name'],
					
				);
				$output .= json_encode($arr);
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}