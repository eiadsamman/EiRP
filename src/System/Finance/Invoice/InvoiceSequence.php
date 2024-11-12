<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\App;
use System\Finance\Invoice\structs\InvoiceDetails;


class InvoiceSequence extends InvoiceDetails
{
	public function __construct(protected App $app)
	{
	}
	public function children(int $id): \Generator
	{
		$result = $this->app->db->execute_query(
			"SELECT
				{$this->sqlSelectQuery()}
			FROM
				inv_main AS a1
					LEFT JOIN currencies ON a1.po_cur_id = cur_id
					JOIN user_costcenter ON a1.po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$this->app->user->info->id}
					LEFT JOIN acc_accounts ON prt_id = a1.po_departement_id
					LEFT JOIN inv_main AS a2 ON a2.po_id = a1.po_rel
					JOIN (SELECT comp_id,comp_name,comp_address,comp_country,comp_city,comp_tellist,cntry_name,cntry_code FROM companies LEFT JOIN countries ON comp_country = cntry_id  ) AS client_company ON client_company.comp_id = a1.po_client_id
					JOIN users AS issuer ON issuer.usr_id = a1.po_issuedby_id
					JOIN inv_costcenter ON ccc_id = a1.po_costcenter
			WHERE
				a1.po_rel = ?
			ORDER BY
				a1.po_id
			",
			[$id]
		);
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				yield new InvoiceDetails($row);
			}
		}
	}
}