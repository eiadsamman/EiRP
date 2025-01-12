<?php

use System\Finance\Accounting;
use System\Finance\DocumentException;
use System\Finance\DocumentMaterialListException;
use System\Finance\Invoice;
use System\Template\Body;
//purchase/po/view

$_TEMPLATE 	= new Body();
$invoice 	= new Invoice($app);
$accounting = new Accounting($app);

$doc_id		= $invoice->DocumentURI();
if ($doc_id) {
	try {
		$chain = $invoice->Chain($doc_id);

		if (sizeof($chain) < 3 || $chain[2] != $doc_id) {
			throw new DocumentException("Requested document not found", 31001);
		}
		$doc_rm = $invoice->GetMaterialRequestDoc($chain[0]);
		$doc_rfq = $invoice->GetPurchaseQuotationDoc($chain[1]);
		$doc_po = $invoice->GetPurchaseOrderDoc($chain[2]);

		$excrate = 1;
		$_syscur = $accounting->system_default_currency();
		if ($_syscur != false && $doc_po['po_cur_id'] != $_syscur['id']) {
			$excrate = $accounting->currency_exchange($doc_po['po_cur_id'], $_syscur['id']);
		}

		$_TEMPLATE->Title("Purchase Order Record", null, $doc_po['doc_id']);

		$doc_value = $doc_po['po_total'] - ($doc_po['po_total'] * $doc_po['po_discount'] / 100) + $doc_po['po_additional_amount'];
		$doc_value += $doc_value * $doc_po['po_vat_rate'] / 100;


		echo $_TEMPLATE->CommandBarStart();
		echo "<div class=\"btn-set\">";
		echo "<a style=\"color:#333;\" href=\"" . $fs(237)->dir . "/\" class=\"bnt-back\"></a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(240)->dir . "/?docid={$chain[0]}&token=" . md5("sysdoc_" . $chain[0] . session_id()) . "\">" . $doc_rm['po_serial'] . "</a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(234)->dir . "/?docid={$chain[1]}&token=" . md5("sysdoc_" . $chain[1] . session_id()) . "\">" . $doc_rfq['po_serial'] . "</a>";
		echo "<span>" . $doc_po['po_serial'] . "</span>";
		echo "<span class=\"gap\"></span>";
		echo "<a href=\"" . $fs(250)->dir . "/?docid={$chain[2]}&token=" . md5("sysdoc_" . $chain[2] . session_id()) . "\" class=\"clr-green\">Proccess Payment</a>";
		echo "<a href=\"" . $fs(253)->dir . "/?docid={$chain[2]}&token=" . md5("sysdoc_" . $chain[2] . session_id()) . "\" class=\"clr-green\">Release Invoice</a>";
		echo "<span style=\"background-color:#fff;border-top:0;border-bottom:0\"></span>";
		echo "<a href=\"" . $fs(250)->dir . "/?docid={$chain[2]}&token=" . md5("sysdoc_" . $chain[2] . session_id()) . "\" class=\"clr-green\">Receive Materials</a>";
		echo "</div>";
		echo $_TEMPLATE->CommandBarEnd();

		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Pruchase Order Information</span>", false, true);

		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<div class="template-gridLayout">
				<div><span>Cost Center</span><div>' . $doc_po['ccc_name'] . '</div></div>
				<div><span>Number</span><div>' . $doc_po['doc_id'] . '</div></div>
				<div><span>Title</span><div>' . $doc_po['po_title'] . '</div></div>
				
			</div>
			<div class="template-gridLayout">
				<div><span>Creation Date</span><div>' . $doc_po['po_date'] . '</div></div>
				<div>
					<span>Vendor</span>
					<div>' . $doc_po['comp_id'] . ' - ' . $doc_po['comp_name'] . '</div>'
			. ($doc_po['po_att_id'] != 0 ? '<div>' . $doc_po['att_id'] . ' - ' . $doc_po['po_att_name'] . '</div>' : "") .
			'
				</div>
				<div></div>
			</div>
			
			<div class="template-gridLayout">
				<div>
					<span>Placed By</span>
					<div>' . $doc_po['doc_usr_id'] . ' - ' . $doc_po['po_usr_name'] . '</div>
				</div>
				<div></div>
				<div></div>
			</div>
			
			<div class="template-gridLayout">
				<div><span>Remarks</span><div>' . nl2br($doc_po['po_remarks']) . '</div></div>
			</div>
			';
		if ($invoice->Per(248)->read) {
			echo '<div class="template-gridLayout inline-title">Financial Information</div>';
			echo '<div class="template-gridLayout">
					<div><span>Total</span><div>' . number_format($doc_po['po_total'], 2, ".", ",") . " " . $doc_po['cur_shortname'] . '</div></div>
					<div><span>Discount</span><div>' . number_format($doc_po['po_discount'], 2, ".", ",") . '%</div></div>
					<div><span>Addtional amount</span><div>' . number_format($doc_po['po_additional_amount'], 2, ".", ",") . " " . $doc_po['cur_shortname'] . '</div></div>
				</div>
				<div class="template-gridLayout">
					<div><span>VAT Rate</span><div>' . number_format($doc_po['po_vat_rate'], 2, ".", ",") . '%</div></div>
					<div><span>Grand total</span><div>' . number_format($doc_value, 2, ".", ",") . " " . $doc_po['cur_shortname'] . '</div></div>
					<div><span>Local currency grand total</span><div>' . number_format($doc_value * $excrate, 2, ".", ",") . " " . $_syscur['shortname'] . '</div></div>
				</div>';
		}
		echo $_TEMPLATE->NewFrameBodyEnd();


		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Details</span>");
		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<table>
			<thead><tr><td width="100%">Material</td><td align="right" colspan="2">Quantity</td>';

		if ($invoice->Per(248)->read)
			echo '<td align="right">Unit Price</td><td align="right">Inline</td>';
		echo '</tr></thead>
			<tbody id="jQmaterialList" style="border:solid 1px #E6E6EB;">';
		$query_materialList = $invoice->DocGetMaterialList($doc_id);
		if ($query_materialList) {
			$bomlegend = null;
			while ($row = $query_materialList->fetch_assoc()) {
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

				if ($invoice->Per(248)->read) {
					echo "<td align=\"right\">" . number_format($row['pols_price'], Invoice::DecimalsNumber($row['pols_price']), ".", ",") . "</td>";
					echo "<td align=\"right\">" . number_format($row['pols_price'] * $row['pols_issued_qty'], 3, ".", ",") . "</td>";
				}
				echo "</tr>";
			}
		}
		echo '</tbody></table>';
		echo $_TEMPLATE->NewFrameBodyEnd();
	} catch (DocumentException $e) {
		$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading purchase order failed, one or more of the following might be the cause:</span>");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Purchase Order document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<ul>');
	} catch (DocumentMaterialListException $e) {
		$_TEMPLATE->Title("Materials list error!", null, "", "mark-error");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Plotting materials list failed</li>
			<ul>');
	} finally {
	}
} else {
	$_TEMPLATE->Title("Purchase Orders", null, null);

	echo $_TEMPLATE->CommandBarStart();
	echo "<div class=\"btn-set\">";
	echo "<a href=\"" . $fs(230)->dir . "\" style=\"color:#333\">New material request</a>";
	echo "<span class=\"gap\"></span>";
	echo "</div>";
	echo $_TEMPLATE->CommandBarEnd();



	echo $_TEMPLATE->NewFrameBodyStart();
	echo "<table class=\"strip\">";
	echo "<thead style=\"position:sticky;top:146px;background-color:#fff;outline:solid 1px #ccc\">";
	echo "<tr><td>Cost Center</td><td>Document ID</td><td>Title</td><td>Date</td><td>Items</td><td width=\"100%\">Status</td></tr>";
	echo "</thead>";

	$rowsploted = 0;
	$po_type = Invoice::map['PUR_ORD'];
	$r = $app->db->query("
		SELECT 
			_main.po_id,_main.po_rel,
			CONCAT(_sp10.prx_value,LPAD(po_serial,_sp10.prx_placeholder,'0')) AS doc_id,
			_main.po_voided,
			_main.po_title,
			DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
			DATE_FORMAT(_main.po_date,'%H:%i') AS po_time,
			CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS doc_usr_name,
			COUNT(pols_id) AS matcount,
			_main.po_close_date,
			ccc_name,ccc_id
		FROM
			inv_main AS _main
				JOIN users ON usr_id = _main.po_usr_id
				LEFT JOIN inv_records ON pols_po_id = _main.po_id
				JOIN inv_costcenter ON ccc_id = po_costcenter
				JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id={$app->user->info->id}
		WHERE
			_main.po_type = $po_type AND po_comp_id = {$app->user->company->id}
		GROUP BY
			_main.po_id
		ORDER BY _main.po_date DESC
		");
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$rowsploted++;
			echo "<tr>";
			echo "<td>{$row['ccc_name']}</td>";
			echo "<td><a href=\"" . $fs(237)->dir . "/?docid={$row['po_id']}&token=" . md5("sysdoc_" . $row['po_id'] . session_id()) . "\">{$row['doc_id']}</a></td>";
			echo "<td>{$row['po_title']}</td>";
			echo "<td>{$row['po_date']}</td>";
			// echo "<td>{$row['doc_usr_name']}</td>";
			echo "<td>{$row['matcount']}</td>";
			echo "<td>" . (is_null($row['po_close_date']) ? "Open" : "Closed") . "</td>";
			echo "</tr>";
		}
	}
	for ($irow = $invoice->listview_rows - $rowsploted; $irow > 0; $irow--) {
		echo "<tr><td colspan=\"7\">&nbsp;</td></tr>";
	}

	echo "</table>";
	echo $_TEMPLATE->NewFrameBodyEnd();

	echo "<script type=\"text/javascript\">
				Template.HistoryEntry(\"" . $fs(237)->dir . "\", \"" . $fs(237)->title . "\");
			</script>";
}
