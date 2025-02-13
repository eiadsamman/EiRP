<?php

use System\Controller\Finance\Accounting;
use System\Controller\Finance\DocumentException;
use System\Controller\Finance\Invoice;
//purchase/po/view


$_TEMPLATE 	= new \System\Layout\Body();
$invoice 	= new Invoice($app);
$accounting = new Accounting($app);

$doc_id		= $invoice->DocumentURI();
if ($doc_id) {
	try {
		$chain = $invoice->Chain($doc_id);

		if (sizeof($chain) < 4 || $chain[3] != $doc_id) {
			throw new DocumentException("Requested document not found", 31001);
		}

		$doc_rm = $invoice->GetMaterialRequestDoc($chain[0]);
		$doc_rfq = $invoice->GetPurchaseQuotationDoc($chain[1]);
		$doc_po = $invoice->GetPurchaseOrderDoc($chain[2]);
		$doc_gr = $invoice->GetGRIRDoc($chain[3]);


		$_TEMPLATE->Title("GR/IR Record", null, $doc_gr['doc_id']);


		echo $_TEMPLATE->CommandBarStart();
		echo "<div class=\"btn-set\">";
		echo "<a style=\"color:#333;\" href=\"" . $fs(251)->dir . "/\" class=\"bnt-back\"></a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(240)->dir . "/?docid={$chain[0]}&token=" . md5("sysdoc_" . $chain[0] . session_id()) . "\">" . $doc_rm['po_serial'] . "</a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(234)->dir . "/?docid={$chain[1]}&token=" . md5("sysdoc_" . $chain[1] . session_id()) . "\">" . $doc_rfq['po_serial'] . "</a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(237)->dir . "/?docid={$chain[2]}&token=" . md5("sysdoc_" . $chain[2] . session_id()) . "\">" . $doc_po['po_serial'] . "</a>";
		echo "<span>" . $doc_gr['po_serial']. "</span>";
		echo "<span class=\"gap\"></span>";
		echo "<a href=\"\" class=\"clr-green\">Print</a>";
		echo "</div>";
		echo $_TEMPLATE->CommandBarEnd();

		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">GR/IR Information</span>", false, true);

		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<div class="template-gridLayout">
				<div><span>Number</span><div>' . $doc_gr['doc_id'] . '</div></div>
				<div><span>Title</span><div>' . $doc_gr['po_title'] . '</div></div>
				<div>
					<span>Vendor</span>
					<div>' . $doc_gr['comp_id'] . ' - ' . $doc_gr['comp_name'] . '</div>
					<div>' . $doc_gr['att_id'] . ' - ' . $doc_gr['po_att_name'] . '</div>
				</div>
			</div>
			<div class="template-gridLayout">
				<div><span>Creation Date</span><div>' . $doc_gr['po_date'] . '</div></div>
				<div><span>Placed By</span><div>' . $doc_gr['doc_usr_id'] . ' - ' . $doc_po['po_usr_name'] . '</div></div>
				<div></div>
			</div>
			
			<div class="template-gridLayout">
				<div>
					<span>Inventory/Asset</span>
					<div>' . $doc_gr['comp_dest_id'] . ' - ' . $doc_gr['comp_dest_name'] . '</div>
				</div>
				<div></div>
				<div></div>
			</div>
			
			<div class="template-gridLayout">
				<div><span>Remarks</span><div>' . nl2br($doc_po['po_remarks']) . '</div></div>
			</div>
			';

		echo $_TEMPLATE->NewFrameBodyEnd();


		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Goods/Assets Details</span>");
		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<table>
			<thead><tr><td width="100%">Material</td><td align="right" colspan="2">Quantity</td>';


		echo '</tr></thead>
			<tbody id="jQmaterialList" style="border:solid 1px #E6E6EB;">';
		$r = $invoice->DocGetMaterialList($doc_id);
		if ($r) {
			$bomlegend = null;
			while ($row = $r->fetch_assoc()) {
				if ($bomlegend != $row['pols_bom_part']) {
					$bomlegend = $row['pols_bom_part'];
					echo "<tr>";
					echo "<td colspan=\"3\" style=\"color:var(--color-link);border-left:solid 2px var(--color-link)\">{$row['_mat_bom']}</td>";
					echo "</tr>";
				}
				echo "<tr>";
				echo "
						<td title=\"{$row['cat_alias']} {$row['mat_name']}\" style=\"padding-left:20px;\">
							<div class=\"template-cascadeOverflow\"><div>{$row['mat_long_id']}</div></div>
							<div class=\"template-cascadeOverflow\"><div>{$row['cat_alias']}, {$row['mat_name']}</div></td>";
				echo "<td align=\"right\">" . number_format($row['pols_issued_qty'], $row['unt_decim'], ".", ",") . "</td>";
				echo "<td>{$row['unt_name']}</td>";

				echo "</tr>";
			}
		}
		echo '</tbody></table>';
		echo $_TEMPLATE->NewFrameBodyEnd();
	} catch (\System\Controller\Finance\DocumentException $e) {
		$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading GRIR failed, one or more of the following might be the cause:</span>");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>GRIR document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<ul>');
	} catch (\System\Controller\Finance\DocumentMaterialListException $e) {
		$_TEMPLATE->Title("Materials list error!", null, "", "mark-error");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Plotting materials list failed</li>
			<ul>');
	} finally {
	}
} else {
	$_TEMPLATE->Title("GR/IR", null, null);

	echo $_TEMPLATE->CommandBarStart();
	echo "<div class=\"btn-set\">";
	echo "<a href=\"" . $fs(250)->dir . "\" style=\"color:#333\">New GRIR Session</a>";
	echo "<span class=\"gap\"></span>";
	echo "</div>";
	echo $_TEMPLATE->CommandBarEnd();



	echo $_TEMPLATE->NewFrameBodyStart();
	echo "<table class=\"strip\">";
	echo "<thead style=\"position:sticky;top:146px;background-color:#fff;outline:solid 1px #ccc\">";
	echo "<tr><td>Cost Center</td><td>Document ID</td><td>Purchase Order</td><td>Inventory/Asset</td><td>Title</td><td>Date</td><td></td><td width=\"100%\"></td></tr>";
	echo "</thead>";

	$rowsploted = 0;
	$grir_type = Invoice::map['GRIR'];
	$po_type = Invoice::map['PUR_ORD'];
	$r = $app->db->query("
		SELECT 
			_main.po_id,_main.po_rel,
			CONCAT(_sp10.prx_value,LPAD(_main.po_serial,_sp10.prx_placeholder,'0')) AS doc_id,
			CONCAT(_sp11.prx_value,LPAD(_rel.po_serial,_sp11.prx_placeholder,'0')) AS parent_doc_id,
			_main.po_voided,
			_main.po_title,
			DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
			DATE_FORMAT(_main.po_date,'%H:%i') AS po_time,
			CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS doc_usr_name,
			_main.po_close_date,ccc_name,
			_dest_comp.comp_name AS comp_dest_name,
			_pols.prt_name
		FROM
			inv_main AS _main
				JOIN users ON usr_id = _main.po_usr_id
				JOIN (
					SELECT pols_po_id, pols_prt_id,prt_name
					FROM inv_records JOIN `acc_accounts` ON prt_id = pols_prt_id
					WHERE pols_issued_qty > 0
					GROUP BY pols_po_id
				) AS _pols ON _pols.pols_po_id = po_id
				JOIN inv_costcenter ON ccc_id = po_costcenter
				JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id={$app->user->info->id}
				JOIN companies AS _dest_comp ON _main.po_comp_id = _dest_comp.comp_id
				
				JOIN inv_main AS _rel ON _rel.po_id = _main.po_rel
				
		WHERE
			_main.po_type = $grir_type AND _main.po_comp_id = {$app->user->company->id}
		
		ORDER BY _main.po_date DESC
		");
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$rowsploted++;
			echo "<tr>";
			echo "<td>{$row['ccc_name']}</td>";
			echo "<td><a href=\"" . $fs(251)->dir . "/?docid={$row['po_id']}&token=" . md5("sysdoc_" . $row['po_id'] . session_id()) . "\">{$row['doc_id']}</a></td>";
			echo "<td>{$row['parent_doc_id']}</td>";
			echo "<td>{$row['comp_dest_name']}: {$row['prt_name']}</td>";
			echo "<td>{$row['po_title']}</td>";

			echo "<td>{$row['po_date']} {$row['po_time']}</td>";
			// echo "<td>{$row['doc_usr_name']}</td>";
			echo "<td></td>";
			echo "<td></td>";
			echo "</tr>";
		}
	}
	//echo $app->db->error;
	for ($irow = $invoice->listview_rows - $rowsploted; $irow > 0; $irow--) {
		echo "<tr><td colspan=\"7\">&nbsp;</td></tr>";
	}

	echo "</table>";
	echo $_TEMPLATE->NewFrameBodyEnd();

	echo "<script type=\"text/javascript\">
				Template.HistoryEntry(\"" . $fs(251)->dir . "/\", \"" . $fs(251)->title . "\");
			</script>";
}
