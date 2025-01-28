<?php
declare(strict_types=1);
namespace System\Routes\Chunk;

class FinanceCategoryList extends \System\Routes\Chunk\Chunk
{
	protected function slo(): void
	{

		$this->headerJSONCacheGzip();
		$output = "[";
		$smart  = "";
		if ($r = $this->app->db->query(
			"SELECT 
				acccat_id, CONCAT(accgrp_name,\": \",acccat_name) AS category_name
			FROM 
				acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group
			"
		)) {
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": \"{$row['acccat_id']}\",";
				$output .= "\"value\": \"" . addslashes($row['category_name']) . "\" ";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";
		echo gzencode($output);
	}
}