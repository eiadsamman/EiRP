<?php
declare(strict_types=1);
namespace System\Views\Chunk;

class FinanceBeneficiaryList extends \System\Views\Chunk\Chunk
{
	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		if ($r = $this->app->db->query(
			"SELECT acm_beneficial, count(acm_beneficial) as trend FROM acc_main GROUP BY acm_beneficial ORDER BY trend DESC")) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"value\": \"" . addslashes($row['acm_beneficial']) . "\" ";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}