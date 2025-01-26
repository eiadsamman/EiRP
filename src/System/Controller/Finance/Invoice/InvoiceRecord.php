<?php
declare(strict_types=1);

namespace System\Controller\Finance\Invoice;
use System\App;
use System\Controller\Finance\Invoice\structs\InvoiceDetails;

class InvoiceRecord extends InvoiceDetails
{
	public function __construct(protected App $app)
	{
	}
	public function get(int $id): InvoiceDetails|bool
	{
		$result = $this->app->db->execute_query(
			"SELECT
				{$this->sqlSelectQuery()}
			FROM
				inv_main AS a1
					LEFT JOIN currencies ON a1.po_cur_id = cur_id
					LEFT JOIN user_costcenter ON a1.po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$this->app->user->info->id}
					LEFT JOIN acc_accounts ON prt_id = a1.po_departement_id
					LEFT JOIN inv_main AS a2 ON a2.po_id = a1.po_rel
					JOIN (SELECT comp_id,comp_name,comp_address,comp_country,comp_city,comp_tellist,cntry_name,cntry_code FROM companies LEFT JOIN countries ON comp_country = cntry_id  ) AS client_company ON client_company.comp_id = a1.po_client_id
					JOIN users AS issuer ON issuer.usr_id = a1.po_issuedby_id
					JOIN inv_costcenter ON ccc_id = a1.po_costcenter
			WHERE
				a1.po_id = ?
			ORDER BY
				a1.po_id
			",
			[$id]
		);
		if ($result) {
			if ($row = $result->fetch_assoc()) {
				if (is_null($row['usrccc_usr_id'])) {
					throw new \Exception("Permissions denied", ERROR_ROOT + 300);
				}
				return new InvoiceDetails($row);
			}
		}
		return false;
	}

}