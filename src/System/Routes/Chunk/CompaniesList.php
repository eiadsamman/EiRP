<?php
declare(strict_types=1);
namespace System\Routes\Chunk;

class CompaniesList extends \System\Routes\Chunk\Chunk
{
	protected function slo(): void
	{
		$this->headerJSONCacheGzip();
		$output = "[";
		$q      = <<<SQL
			SELECT 
				comp_id, comp_name, commercial_legalName, commercial_registrationNumber, commercial_taxNumber, commercial_vatNumber
			FROM 
				companies 
				LEFT JOIN
					companies_legal ON comp_id = commercial_companyId AND commercial_default = 1
			ORDER BY
				comp_name, comp_id
			SQL;

		if ($r = $this->app->db->query($q)) {
			$smart = "";
			while ($row = $r->fetch_assoc()) {
				$output .= $smart . "{";
				$output .= "\"id\": \"{$row['comp_id']}\",";
				$output .= "\"value\": \"" . addslashes($row['comp_name']) . "\"";
				
				if (!is_null($row['commercial_registrationNumber'])) {
					$output .= ",\"legalName\": \"" . $row['commercial_legalName'] . "\",";
					$output .= "\"regNo\": \"" . $row['commercial_registrationNumber'] . "\",";
					$output .= "\"taxNo\": \"" . $row['commercial_taxNumber'] . "\",";
					$output .= "\"vatNo\": \"" . $row['commercial_vatNumber'] . "\"";
				}
				//$output .= "highlight: \"\",";
				//$output .= "keywords: \"\"";
				//$output .= "selected: false,";
				$output .= "}";
				$smart  = ",";
			}
		}
		$output .= "]";

		echo gzencode($output);
	}

}