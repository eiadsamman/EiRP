<?php

use System\Controller\Finance\DocumentException;
use System\Template\Body;
use \System\Controller\Finance\Invoice;
//warehouse/grir/new/


$invoice 	= new Invoice($app);
$accounting = new \System\Controller\Finance\Accounting($app);


if ($h__requested_with_ajax && isset($_POST['vdocid'], $_POST['token'])) {
	$post_doc_id	= (int)$_POST['vdocid'];
	$prev_doc		= false;
	$prev_pols		= array();


	if (md5("sysdoc_" . $post_doc_id . session_id()) != $_POST['token']) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Invalid document ID or session has timed out";
		exit;
	}


	$rpo = $app->db->query("SELECT po_id,po_title,po_shipto_acc_id,po_billto_acc_id,po_comp_id,po_cur_id,po_costcenter,po_vat_rate,po_benf_comp_id FROM inv_main WHERE po_id=$post_doc_id AND po_type=" . Invoice::map['PUR_ORD'] . " AND po_close_date IS NULL");
	if ($rpo && $rowpo = $rpo->fetch_assoc()) {
		$prev_doc = $rowpo;
	} else {
		header("HTTP_X_RESPONSE: INERR");
		echo "Invalid material request document or already closed";
		exit;
	}

	if (!isset($_POST['vinventoryasset'][1]) || (int)$_POST['vinventoryasset'][1] == 0) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Select destination Inventory/Asset Account";
		exit;
	}
	if (!isset($_POST['vgrir'][1]) || (int)$_POST['vgrir'][1] == 0) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Select GR/IR Account";
		exit;
	}


	$_POST['po_remarks'] = addslashes($_POST['po_remarks']);

	$prev_doc['subtotal'] = 0;



	$doc_serial = $invoice->GetNextSerial(Invoice::map['GRIR'], $prev_doc['po_comp_id'], $prev_doc['po_costcenter']);


	$r = $app->db->query("
		SELECT 
			pols_id,pols_item_id,pols_issued_qty,pols_price,pols_bom_part,pols_discount
		FROM
			inv_records 
		WHERE
			pols_po_id={$rowpo['po_id']}
		ORDER BY
			pols_id
		");

	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$prev_pols[] = $row;
		}
	}


	foreach ($prev_pols as $k => $v) {
		$prev_pols[$k]['pols_bom_part'] = is_null($prev_pols[$k]['pols_bom_part']) ? "NULL" : $prev_pols[$k]['pols_bom_part'];

		if (isset($_POST['vmatlist'][$v['pols_id']]) && (float)$_POST['vmatlist'][$v['pols_id']] > 0) {
			$prev_pols[$k]['pols_subqty'] = (float)$_POST['vmatlist'][$v['pols_id']];
			$prev_doc['subtotal'] += (float)$_POST['vmatlist'][$v['pols_id']] * $prev_pols[$k]['pols_price'];
		} else {
			$prev_pols[$k]['pols_subqty'] = 0;
		}
	}


	$app->db->autocommit(false);
	$rwo = ("
			INSERT INTO inv_main (	po_serial,po_type,po_shipto_acc_id,po_billto_acc_id,po_usr_id,
									po_date,po_due_date,po_close_date,
									po_title,po_remarks,po_comp_id,po_att_id,po_cur_id,po_rel,
									po_total,po_vat_rate,po_additional_amount,po_discount,po_costcenter,po_benf_comp_id
									) 
			VALUES 
			(
				$doc_serial,
				" . Invoice::map['GRIR'] . ",
				NULL,
				{$prev_doc['po_billto_acc_id']},
				{$app->user->info->id},
				NOW(),
				NULL,
				NULL,
				\"{$prev_doc['po_title']}\",
				\"{$_POST['po_remarks']}\",
				{$prev_doc['po_comp_id']},
				NULL,
				{$prev_doc['po_cur_id']},
				{$prev_doc['po_id']},
				{$prev_doc['subtotal']},
				0,
				0,
				0,
				{$prev_doc['po_costcenter']},
				{$prev_doc['po_benf_comp_id']}
			);
		");


	$rwo = $app->db->query($rwo);
	if ($rwo) {

		$submited_doc = $app->db->insert_id;
		$rwol = true;

		//Outbound/Inbound
		$rwolq = "INSERT INTO inv_records (pols_rel_id,pols_po_id,pols_item_id,pols_issued_qty,pols_delivered_qty,pols_price,pols_bom_part,pols_prt_id) VALUES ";
		$smart = "";
		foreach ($prev_pols as $k => $v) {
			$rwolq .= $smart . "({$prev_pols[$k]['pols_id']},$submited_doc,{$prev_pols[$k]['pols_item_id']}," . (-1 * $prev_pols[$k]['pols_subqty']) . ",0,{$prev_pols[$k]['pols_price']},{$prev_pols[$k]['pols_bom_part']},{$_POST['vgrir'][1]}), ";
			$rwolq .= "({$prev_pols[$k]['pols_id']},$submited_doc,{$prev_pols[$k]['pols_item_id']}," . (+1 * $prev_pols[$k]['pols_subqty']) . ",0,{$prev_pols[$k]['pols_price']},{$prev_pols[$k]['pols_bom_part']},{$_POST['vinventoryasset'][1]})";
			$smart = ",";
		}
		$rwol &= $app->db->query($rwolq);

		if ($rwol) {
			$app->db->commit();
			header("HTTP_X_RESPONSE: SUCCESS");
			echo "/?docid={$submited_doc}&token=" . md5("sysdoc_" . $submited_doc . session_id());
			exit;
		} else {
			//echo $app->db->error;
			$app->db->rollback();
			header("HTTP_X_RESPONSE: DBERR");
			echo "Submitting quotation failed, database error";
			exit;
		}
	} else {
		$app->db->rollback();
		header("HTTP_X_RESPONSE: DBERR");
		echo "Submitting quotation failed, database error";
		exit;
	}
	exit;
}



$_TEMPLATE 	= new Body();
$doc_id		= $invoice->DocumentURI();
if ($doc_id)
	try {
		$subtotal = 0;
		$grandtotal = 0;
		$chain	= $invoice->Chain($doc_id);
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
		$_TEMPLATE->Title("Release Purchase Order Invoice", null, $doc_po['doc_id']);

		$doc_value = $doc_po['po_total'] - ($doc_po['po_total'] * $doc_po['po_discount'] / 100) + $doc_po['po_additional_amount'];
		$doc_value += $doc_value * $doc_po['po_vat_rate'] / 100;


		echo $_TEMPLATE->CommandBarStart();
		echo "<div class=\"btn-set\">";
		echo "<a style=\"color:#333;\" href=\"" . $fs(251)->dir . "/\" class=\"bnt-back\"></a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(240)->dir . "/?docid={$chain[0]}&token=" . md5("sysdoc_" . $chain[0] . session_id()) . "\">{$doc_rm['doc_id']}</a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(234)->dir . "/?docid={$chain[1]}&token=" . md5("sysdoc_" . $chain[1] . session_id()) . "\">{$doc_rfq['doc_id']}</a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(237)->dir . "/?docid={$chain[2]}&token=" . md5("sysdoc_" . $chain[2] . session_id()) . "\">{$doc_po['doc_id']}</a>";
		echo "<span>Invoice</span>";
		echo "<span class=\"gap\"></span>";
		echo "<button class=\"clr-green\" id=\"jQpostSubmit\">Submit Invoice</button>";
		echo "</div>";
		echo $_TEMPLATE->CommandBarEnd();

		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Pruchase Order Information</span>", false, true);
		echo $_TEMPLATE->NewFrameBodyStart();
		//<div class="template-gridLayout inline-title">Accounts settings</div>
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
		</div>';
		echo $_TEMPLATE->NewFrameBodyEnd();


		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Received Materials</span>");
		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<form id="jQpostFormMaterials">';
		echo '<table>
		<thead>
			<tr>
				<td style="width:100%;">Material</td>
				<td align="right" colspan="2">Issued Quantity</td>
				<td align="right" colspan="2">Received Quantity</td>
				<td>Unit Price</td>
				<td>Inline-Total</td>
			</tr>
		</thead>
		<tbody id="jQmaterialList" style="border:solid 1px #E6E6EB;">';

		$r = $app->db->query(
			"SELECT 
				*
			FROM
				inv_main
					JOIN (
						SELECT
							mat_id,mat_long_id,mat_name,cat_alias,mattyp_name,unt_name,unt_decim,pols_po_id,pols_issued_qty,_recqty,pols_price
						FROM
							inv_records 
							JOIN (
								SELECT 
									mat_id,mat_long_id,mat_name,cat_alias,mattyp_name,unt_name,unt_decim
								FROM
									mat_materials
										JOIN mat_materialtype ON mattyp_id=mat_mattyp_id
										JOIN matx_unit ON unt_id = mat_unitsystem
										LEFT JOIN 
											(SELECT CONCAT_WS(', ', matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
												FROM mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
											) AS _category ON mat_matcat_id=_category.matcat_id
							) AS _material ON pols_item_id = mat_id
							LEFT JOIN (
								SELECT pols_rel_id, SUM(pols_issued_qty) AS _recqty
								FROM 
									inv_records 
										JOIN inv_main ON pols_po_id = po_id AND po_rel=$doc_id
								WHERE 
									pols_issued_qty > 0 AND pols_prt_id IS NOT NULL
								GROUP BY
									pols_rel_id
							) AS _inv_records_receveid ON _inv_records_receveid.pols_rel_id = pols_id
						WHERE
							pols_po_id=$doc_id
					) AS _inv_records
					ON po_id = _inv_records.pols_po_id
				JOIN currencies ON cur_id = po_cur_id
					
			WHERE
				po_id=$doc_id AND po_type = " . Invoice::map['PUR_ORD'] . "
			"
		);

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
				echo "<td align=\"right\">" . number_format($row['_recqty'], $row['unt_decim'], ".", ",") . "</td>";
				echo "<td>{$row['unt_name']}</td>";
				echo "<td align=\"right\">" . number_format($row['pols_price'], Invoice::DecimalsNumber($row['pols_price']), ".", ",") . "</td>";
				echo "<td align=\"right\">" . number_format($row['pols_price'] * $row['_recqty'], 3, ".", ",") . " {$row['cur_symbol']}</td>";
				echo "</tr>";

				$subtotal += $row['pols_price'] * $row['_recqty'];
			}
		}
		$grandtotal = $subtotal + $doc_po['po_additional_amount'] - ($subtotal * $doc_po['po_discount'] / 100);
		$grandtotal += $grandtotal * ($doc_po['po_vat_rate'] / 100);

		echo '</tbody></table>';
		echo '</form>';
		echo $_TEMPLATE->NewFrameBodyEnd();


		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Invoice Details</span>", false, true);
		echo $_TEMPLATE->NewFrameBodyStart();
		echo '
	<form id="jQpostFormDetails">
		<input type="hidden" name="vdocid" value="' . $doc_po['po_id'] . '" />
		<input type="hidden" name="token" value="' . md5("sysdoc_" . $doc_po['po_id'] . session_id()) . '" />
		
		
		<div class="template-gridLayout inline-title">Purchase Order details</div>
		<div class="template-gridLayout">
			<div><span>Total</span><div>' . number_format($doc_po['po_total'], 2, ".", ",") . " " . $doc_po['cur_shortname'] . '</div></div>
			<div><span>Discount</span><div>' . number_format($doc_po['po_discount'], 2, ".", ",") . '%</div></div>
			<div><span>Addtional amount</span><div>' . number_format($doc_po['po_additional_amount'], 2, ".", ",") . ' ' . $doc_po['cur_shortname'] . '</div></div>
		</div>
		<div class="template-gridLayout">
			<div><span>VAT Rate</span><div>' . number_format($doc_po['po_vat_rate'], 2, ".", ",") . '%</div></div>
			<div><span>Grand total</span><div>' . number_format($doc_value, 2, ".", ",") . " " . $doc_po['cur_shortname'] . '</div></div>
			<div><span>Local currency grand total</span><div>' . number_format($doc_value * $excrate, 2, ".", ",") . " " . $_syscur['shortname'] . '</div></div>
		</div>
		


		<div class="template-gridLayout inline-title">Purchase Order invoice details</div>
		<div class="template-gridLayout role-input">
			<div>
				<span>Subtotal</span>
				<div class="btn-set"><input id="jQfieldSubtotal" tabindex="-1" value="' . number_format($subtotal, 2, ".", ",") . '" readonly="readonly" class="flex" /></div>
			</div>
			<div>
				<span>Discount</span>
				<div class="btn-set"><input type="text" name="vdiscount" id="jQinputDiscount" class="flex" value="' . number_format($doc_po['po_discount'], 2, ".", ",") . '" /><span>%</span></div>
			</div>
			<div>
				<span>Additional Amount</span>
				<div class="btn-set"><input type="text" name="vaddamount" id="jQinputAddamount" class="flex" value="' . number_format($doc_po['po_additional_amount'], 2, ".", "") . '" /></div>
			</div>
		</div>
		<div class="template-gridLayout role-input">
			<div>
				<span>VAT Rate</span>
				<div class="btn-set"><input type="text" name="vvat" tabindex="-1" id="jQinputVatrate" value="' . number_format($doc_po['po_vat_rate'], 2, ".", ",") . '" readonly="readonly" class="flex" /><span>%</span></div>
			</div>
			<div>
				<span>Grand Total</span>
				<div class="btn-set"><input id="jQfieldGrandtotal" tabindex="-1" readonly="readonly" value="' . number_format($grandtotal, 2, ".", ",") . '" class="flex" /></div>
			</div>
			<div></div>
		</div>
		
		
		
		<div class="template-gridLayout role-input">		
			<div class="btn-set vertical">
				<span>Vendor Payable Account</span>
				<input type="text" name="vgrir" data-slo="ACC_OUTBOUND" id="jQGIsource" />
				<!--<span style="color:#093">From PO Vendor company</span>-->
			</div>
			<div></div>
			<div></div>
		</div>
		<div class="template-gridLayout role-input">
			<div class="btn-set vertical"><span>Remarks</span><textarea style="height:100px" name="po_remarks"></textarea></div>
		</div>';
		echo '</form>';
		echo $_TEMPLATE->NewFrameBodyEnd();



?>
	<script type="text/javascript">
		$(document).ready(function(e) {
			var jQGIdest = $("#jQGIdest").slo({
				'limit': 10,
				onselect: function() {}
			}).setparam({
				"company": <?= $app->user->info->id; ?>
			});
			var $jQGIsource = $("#jQGIsource").slo({
				'limit': 10,
				onselect: function() {}
			}).setparam({
				"company": <?php echo (int)$doc_po['po_benf_comp_id']; ?>
			});

			$(".jQqtyfield").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
				OnlyFloat(this, null, 0);
			});

			$("#jQinputAddamount").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
				OnlyFloat(this, null, 0);
				updateTotal();
			});
			$("#jQinputDiscount").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
				OnlyFloat(this, 100, 0);
				updateTotal();
			});

			var updateTotal = function() {
				let _sum = <?php echo $subtotal; ?>;
				let _add = 0;
				let _vat = 0;
				let _dis = 0;

				if (!isNaN(parseFloat($("#jQinputVatrate").val()))) {
					_vat = parseFloat($("#jQinputVatrate").val());
					if (_vat < 0) {
						_vat = 0;
					}
				}
				if (!isNaN(parseFloat($("#jQinputAddamount").val()))) {
					_add = parseFloat($("#jQinputAddamount").val());
					if (_add < 0) {
						_add = 0;
					}
				}
				if (!isNaN(parseFloat($("#jQinputDiscount").val()))) {
					_dis = parseFloat($("#jQinputDiscount").val());
					if (_dis < 0) {
						_dis = 0;
					}
					if (_dis > 100) {
						_dis = 100;
					}
				}


				let _grand = _sum + _add - (_sum * _dis / 100);
				_grand += (_grand * _vat / 100);
				$("#jQfieldGrandtotal").val(_grand.numberFormat(2));
			}

			$("#jQpostSubmit").on('click', function() {
				overlay.show();
				$.ajax({
					url: "<?php echo $fs()->dir; ?>",
					type: "POST",
					data: $("#jQpostFormDetails").serialize() + "&" + $("#jQpostFormMaterials").serialize(),
				}).done(function(o, textStatus, request) {
					let response = request.getResponseHeader('HTTP_X_RESPONSE');
					if (response == "INERR") {
						messagesys.failure(o);
					} else if (response == "SUCCESS") {
						messagesys.success("GR/IR posted successfully");
						Template.PageRedirect("<?php echo $fs(251)->dir; ?>" + o, "<?php echo "{$c__settings['site']['title']} - " . $fs(251)->title; ?>", true);
						Template.ReloadSidePanel();
					} else if (response == "DBERR") {
						messagesys.failure(o);
					}
				}).fail(function(m) {
					messagesys.failure(m);
				}).always(function() {
					overlay.hide();
				});
			});

		});
	</script>

<?php
	} catch (DocumentException $e) {
		$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading GR/IR failed, one or more of the following might be the cause:</span>");
		$_TEMPLATE->NewFrameBody('<ul>
		<li>Purchase Order document number is invalid</li>
		<li>GR/IR document number is invalid</li>
		<li>Session has expired</li>
		<li>Database query failed, contact system administrator</li>
		<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		<br />Return to <a href="' . $fs(237)->dir . '">Purchase Orders</a>
			
		');
	} catch (DocumentMaterialListException $e) {
		$_TEMPLATE->Title("Materials list error!", null, "", "mark-error");
		$_TEMPLATE->NewFrameBody('<ul>
		<li>Plotting materials list failed</li>
		<ul>');
	} finally {
	}
