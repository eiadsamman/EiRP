<?php

declare(strict_types=1);

namespace System\Finance;
use System\App;
use System\Profiles\CostCenterProfile;

class CostCenter
{
	public function __construct(protected App &$app)
	{
	}

	public function getSystemDefault(): CostCenterProfile|bool
	{
		$result = $this->app->db->execute_query(
			"SELECT
				ccc_id, ccc_name, ccc_vat, ccc_conceal
			FROM
				inv_costcenter
			WHERE
				ccc_default = 1
			",
			[]
		);
		if ($result && $result->num_rows == 1 && $row = $result->fetch_assoc()) {
			return new CostCenterProfile((int) $row['ccc_id'], $row['ccc_name'], (float) $row['ccc_vat']);
		} else {
			return false;
		}
	}
}