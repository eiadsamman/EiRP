<?php
declare(strict_types=1);

namespace System\Controller\Finance\Invoice;
use System\App;
use System\Controller\Finance\Invoice\structs\InvoiceItem;
use System\Profiles\MaterialGategoryProfile;
use System\Profiles\MaterialGroupProfile;
use System\Profiles\MaterialProfile;


class InvoiceItems
{
	public function __construct(protected App $app)
	{
	}

	private function sqlQuery(): string
	{
		return "SELECT
				pols_id,
				pols_po_id,
				pols_item_id,
				pols_issued_qty,
				pols_delivered_qty,
				pols_grouping_item,
				
				pols_rel_id,
				pols_prt_id,
				pols_price,
				pols_discount,
				pols_unitsystem,
				pols_unit,

				/* Material  */
				mat_id,mat_name, mat_long_id,mat_longname,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				brand_id, brand_name
			FROM 
				inv_records 
				JOIN (
					SELECT 
						mat_id,mat_name, mat_long_id,mat_longname,
						matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
						brand_id, brand_name
					FROM 
						mat_materials 
							JOIN mat_materialtype ON mat_mattyp_id = mattyp_id
							LEFT JOIN brands ON brand_id = mat_brand_id
							JOIN 
								(SELECT matcatgrp_name, matcatgrp_id, matcat_name, matcat_id FROM mat_category JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id) 
								AS _category ON mat_matcat_id=_category.matcat_id
					) AS materialProfile ON materialProfile.mat_id = pols_item_id ";
	}

	private function parseSqlResult(array $itemRow): InvoiceItem
	{
		$invoiceItem                 = new InvoiceItem();
		$invoiceItem->id             = $itemRow['pols_id'];
		$invoiceItem->isGroupingItem = ((int) $itemRow['pols_grouping_item']) == 1 ? true : false;

		$invoiceItem->material           = new MaterialProfile();
		$invoiceItem->material->id       = (int) $itemRow['pols_item_id'];
		$invoiceItem->material->longId   = (int) $itemRow['mat_long_id'];
		$invoiceItem->material->name     = $itemRow['mat_name'];
		$invoiceItem->material->category = new MaterialGategoryProfile(
			(int) $itemRow['matcat_id'],
			$itemRow['matcat_name'],
			new MaterialGroupProfile(
				(int) $itemRow['matcatgrp_id'],
				$itemRow['matcatgrp_name']
			)
		);
		$invoiceItem->material->longName = $itemRow['mat_longname'];
		//$invoiceItem->material->unit     = new UnitProfile((int) $itemRow['uxnt_id'], $itemRow['uxnt_name'], $itemRow['uxnt_category'], (int) $itemRow['uxnt_decim']);
		//$invoiceItem->material->unit     = new UnitProfile(0, 'Name', 'Categ', (int) 3);

		$invoiceItem->relatedItem       = is_null($itemRow['pols_rel_id']) ? null : (int) $itemRow['pols_rel_id'];
		$invoiceItem->quantity          = (float) $itemRow['pols_issued_qty'];
		$invoiceItem->quantityDelivered = is_null($itemRow['pols_delivered_qty']) ? null : (float) $itemRow['pols_delivered_qty'];
		$invoiceItem->value             = (float) ($itemRow['pols_price']);
		$invoiceItem->discount          = is_null($itemRow['pols_discount']) ? null : (float) $itemRow['pols_discount'];


		$invoiceItem->material->unitSystem = \System\Enum\UnitSystem::tryFrom((int) $itemRow['pols_unitsystem']);
		$invoiceItem->unit                 = $this->app->unit->getUnit((int) $itemRow['pols_unitsystem'], (int) $itemRow['pols_unit']) ?? null;
		$this->getSubItems($invoiceItem);
		return $invoiceItem;
	}
	public function get(int $invoiceId): \Generator
	{
		$result = $this->app->db->execute_query(
			"{$this->sqlQuery()}
			WHERE
				pols_po_id = ? AND (pols_rel_id IS NULL OR pols_rel_id = 0)
			ORDER BY
				pols_id",
			[$invoiceId]
		);
		if ($result) {
			while ($itemRow = $result->fetch_assoc()) {
				yield $this->parseSqlResult($itemRow);
			}
		}
	}


	private function getSubItems(InvoiceItem &$item): void
	{

		$result = $this->app->db->execute_query(
			"{$this->sqlQuery()} WHERE pols_rel_id = ? ORDER BY pols_id",
			[$item->id]
		);
		if ($result) {
			while ($itemRow = $result->fetch_assoc()) {
				$item->subItems[] = $this->parseSqlResult($itemRow);
			}
		}

	}
}