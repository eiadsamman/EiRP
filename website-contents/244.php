<?php
//purchase/po/new

use System\Finance\Accounting;
use System\Finance\DocumentException;
use System\Finance\DocumentMaterialListException;
use System\Finance\Invoice;

$invoice = new Invoice($app);


if ($h__requested_with_ajax && isset($_POST['vdocid'], $_POST['token'])) {
	$post_doc_id	= (int)$_POST['vdocid'];

	$prev_doc		= false;


	if (md5("sysdoc_" . $post_doc_id . session_id()) != $_POST['token']) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Invalid document ID or session has timed out";
		exit;
	}


	$rpo = $app->db->query("SELECT po_id,po_comp_id,po_costcenter FROM inv_main WHERE po_id=$post_doc_id AND po_type=" . Invoice::map['PUR_QUT'] . " AND po_close_date IS NULL");
	if ($rpo && $rowpo = $rpo->fetch_assoc()) {
		$prev_doc = $rowpo;
	} else {
		header("HTTP_X_RESPONSE: INERR");
		echo "Invalid material request document or the request is already closed";
		exit;
	}


	$prev_doc['remarks'] = trim(addslashes($_POST['vremarks']));
	$prev_doc['remarks'] = ($prev_doc['remarks']) == "" ? "NULL" : "'" . $prev_doc['remarks'] . "'";


	$doc_serial 	= $invoice->GetNextSerial(Invoice::map['PUR_ORD'], $prev_doc['po_comp_id'], $prev_doc['po_costcenter']);


	$app->db->autocommit(false);
	$rwo = ("INSERT INTO inv_main (	po_serial,po_type,po_shipto_acc_id,po_billto_acc_id,po_usr_id,
									po_date,po_due_date,po_close_date,
									po_title,po_remarks,po_comp_id,po_att_id,po_cur_id,po_rel,
									po_total,po_vat_rate,po_additional_amount,po_discount,po_benf_comp_id,po_costcenter
								) 
			SELECT 
				$doc_serial," . Invoice::map['PUR_ORD'] . ",po_shipto_acc_id,po_billto_acc_id,{$app->user->info->id},
				NOW(),NULL,NULL,
				po_title,{$prev_doc['remarks']},po_comp_id,po_att_id,po_cur_id,{$prev_doc['po_id']},
				po_total,po_vat_rate,po_additional_amount,po_discount,po_benf_comp_id,po_costcenter
			FROM 
				inv_main 
			WHERE 
				po_id={$prev_doc['po_id']}
		");


	$rwo = $app->db->query($rwo);
	if ($rwo) {
		$submited_doc = $app->db->insert_id;
		/*Close all chain records*/
		$chain = $invoice->Chain($submited_doc);
		$rwosq = $app->db->query("UPDATE inv_main SET po_close_date = NOW() WHERE po_id = {$chain[0]} OR po_id = {$chain[1]}");
		if (!$rwosq) {
			$app->db->rollback();
			header("HTTP_X_RESPONSE: DBERR");
			echo "Posting Purchase Order failed";
			exit;
		}


		$rwolq = "
			INSERT INTO inv_records (pols_rel_id,pols_po_id,pols_item_id,pols_issued_qty,pols_delivered_qty,pols_price,pols_bom_part) 
			SELECT pols_rel_id,$submited_doc,pols_item_id,pols_issued_qty,pols_delivered_qty,pols_price,pols_bom_part
			FROM inv_records
			WHERE pols_po_id = {$prev_doc['po_id']}
			";

		$rwol = $app->db->query($rwolq);
		if ($rwol) {
			$app->db->commit();
			header("HTTP_X_RESPONSE: SUCCESS");
			echo "/?docid={$submited_doc}&token=" . md5("sysdoc_" . $submited_doc . session_id());
			exit;
		} else {
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



$_TEMPLATE = new \System\Template\Body();
if (is_null($accounting)) {
	include_once("admin/class/accounting.php");
	$accounting = new Accounting($app);
}



$doc_id = $invoice->DocumentURI();
if ($doc_id) {
	try {
		$chain 		= $invoice->Chain($doc_id);
		$doc_mr 	= $invoice->GetMaterialRequestDoc($chain[0]);
		$doc_rfq 	= $invoice->GetPurchaseQuotationDoc($chain[1]);

		$excrate 	= 1;
		$_syscur	= $accounting->system_default_currency();

		if ($_syscur != false && $doc_rfq['po_cur_id'] != $_syscur['id']) {
			$excrate = $accounting->currency_exchange((int)$doc_rfq['po_cur_id'], (int) $_syscur['id']);
		}

		$doc_value = $doc_rfq['po_total'] - ($doc_rfq['po_total'] * $doc_rfq['po_discount'] / 100) + $doc_rfq['po_additional_amount'];
		$doc_value += $doc_value * $doc_rfq['po_vat_rate'] / 100;

		if (is_null($doc_mr['po_close_date'])) {
			$_TEMPLATE->Title("New Purchase Order", null, $doc_rfq['doc_id']);
		} else {
			$_TEMPLATE->Title("New Purchase Order", null, $doc_rfq['doc_id']);
		}



		echo $_TEMPLATE->CommandBarStart();
		echo "<div class=\"btn-set\">";
		echo "<a style=\"color:#333;\" href=\"" . $fs(237)->dir . "/\" class=\"bnt-back\"></a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(240)->dir . "/?docid={$chain[0]}&token=" . md5("sysdoc_" . $chain[0] . session_id()) . "\">" . $app->translate_prefix(Invoice::map['MAT_REQ'], $doc_mr['po_serial']) . "</a>";
		echo "<a style=\"color:#333;\" href=\"" . $fs(234)->dir . "/?docid={$chain[1]}&token=" . md5("sysdoc_" . $chain[1] . session_id()) . "\">" . $app->translate_prefix(Invoice::map['PUR_QUT'], $doc_rfq['po_serial']) . "</a>";
		echo "<span>Purchase Order</span>";
		echo "<span class=\"gap\"></span>";
		if (is_null($doc_mr['po_close_date'])) {
			echo "<button type=\"button\" class=\"clr-green\" id=\"jQbuttonSubmit\">Submit PO</button>";
		} else {
			echo "<span>Request Closed</span>";
			echo "<span class=\"mark-lock\"></span>";
		}

		echo "</div>";
		echo $_TEMPLATE->CommandBarEnd();



		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Information</span>", false, true);
		echo $_TEMPLATE->NewFrameBodyStart();

		echo '
			<div class="template-gridLayout">
				<div><span>Cost Center</span><div>' . $doc_rfq['ccc_name'] . '</div></div>
				<div><span>Number</span><div>' . $doc_rfq['doc_id'] . '</div></div>
				<div><span>Title</span><div>' . $doc_rfq['po_title'] . '</div></div>
				
				
			</div>
			<div class="template-gridLayout">
				<div><span>Creation Date</span><div>' . $doc_rfq['po_date'] . '</div></div>
				<div>
					<span>Vendor</span>
					<div>' . $doc_rfq['comp_id'] . ' - ' . $doc_rfq['comp_name'] . '</div>
					'
			. ($doc_rfq['po_att_id'] != 0 ? '<div>' . $doc_rfq['att_id'] . ' - ' . $doc_rfq['po_att_name'] . '</div>' : "") .
			'
				</div>
				<div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Placed By</span><div>' . $doc_rfq['doc_usr_id'] . ' - ' . $doc_rfq['po_usr_name'] . '</div></div>
				<div></div>
				<div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Remarks</span><div>' . nl2br($doc_rfq['po_remarks']) . '</div></div>
			</div>
			
			';
		if ($invoice->Per(248)->read) {
			echo '<div class="template-gridLayout inline-title">Financial Information</div>';
			echo '
				<div class="template-gridLayout">
					<div><span>Total</span><div>' . number_format($doc_rfq['po_total'], 2, ".", ",") . " " . $doc_rfq['cur_shortname'] . '</div></div>
					<div><span>Discount</span><div>' . number_format($doc_rfq['po_discount'], 2, ".", ",") . '%</div></div>
					<div><span>Addtional amount</span><div>' . number_format($doc_rfq['po_additional_amount'], 2, ".", ",") . " " . $doc_rfq['cur_shortname'] . '</div></div>
				</div>
				<div class="template-gridLayout">
					<div><span>VAT Rate</span><div>' . number_format($doc_rfq['po_vat_rate'], 2, ".", ",") . '%</div></div>
					<div><span>Grand total</span><div>' . number_format($doc_value, 2, ".", ",") . " " . $doc_rfq['cur_shortname'] . '</div></div>
					<div><span>Local currency grand total</span><div>' . number_format($doc_value * $excrate, 2, ".", ",") . " " . $_syscur['shortname'] . '</div></div>
				</div>
				
				';
		}
		echo $_TEMPLATE->NewFrameBodyEnd();







		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Details</span>");
		echo $_TEMPLATE->NewFrameBodyStart();
		echo "<table class=\"bom-table\">
					<thead>
						<tr>
							<td width=\"100%\">Material</td>
							<td align=\"right\" colspan=\"2\">Quantity</td>
							
							<td align=\"right\">Unit Price</td>
							<td align=\"right\">Inline</td>
						</tr>
					</thead>F
					<tbody id=\"jQmaterialList\">";
		$r = $invoice->DocGetMaterialList($doc_rfq['po_id']);
		if ($r) {
			$bomlegend = null;
			while ($row = $r->fetch_assoc()) {
				if ($bomlegend != $row['pols_bom_part']) {
					$bomlegend = $row['pols_bom_part'];
					echo "<tr>";
					echo "<td colspan=\"6\" class=\"css_bom-legend\">{$row['_mat_bom']}</td>";
					echo "</tr>";
				}
				echo "<tr>";
				echo "
									<td title=\"{$row['cat_alias']} {$row['mat_name']}\" style=\"padding-left:20px;\">
										<div class=\"template-cascadeOverflow\"><div>{$row['mat_long_id']}</div></div>
										<div class=\"template-cascadeOverflow\"><div>{$row['cat_alias']}, {$row['mat_name']}</div>
									</td>";

				echo "<td align=\"right\">" . number_format($row['pols_issued_qty'], $row['unt_decim'], ".", ",") . "</td>";
				echo "<td>{$row['unt_name']}</td>";
				echo "<td align=\"right\">" . number_format($row['pols_price'], Invoice::DecimalsNumber($row['pols_price']), ".", ",") . "</td>";

				$inlinetotal = $row['pols_price'] * $row['pols_issued_qty'];
				echo "<td style=\"min-width:110px;\" data-CostRev=\"{$row['pols_id']}\" align=\"right\">" .
					number_format($inlinetotal, 3, ".", ",") . "</td>";
				echo "</tr>";
			}
		}
		echo "</tbody></table>";
		echo $_TEMPLATE->NewFrameBodyEnd();


		if (is_null($doc_mr['po_close_date'])) {
			$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Purchase Order Summary</span>");
			echo $_TEMPLATE->NewFrameBodyStart();
?>
			<form id="jQpostFormAdditional">
				<input type="hidden" name="vdocid" value="<?php echo $doc_rfq['po_id']; ?>" />
				<input type="hidden" name="token" value="<?php echo md5("sysdoc_" . $doc_rfq['po_id'] . session_id()); ?>" />
				<div class="template-gridLayout role-input">
					<div>
						<span>Remarks</span>
						<div class="btn-set"><textarea value="" type="text" name="vremarks" style="height:80px;min-height:100px;" class="flex"></textarea></div>
					</div>
				</div>
			</form><?php
					echo $_TEMPLATE->NewFrameBodyEnd();
				} else {

					$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Document History</span>");
					echo $_TEMPLATE->NewFrameBodyStart();
					echo "<table class=\"bom-table\">
						<thead>
							<tr>
								<td>Document ID</td>
								<td>Document Type</td>
								<td>Creation Date</td>
								<td width=\"100%\">Status</td>
							</tr>
						</thead>
						<tbody id=\"jQmaterialList\">";

					echo "<tr><td>{$doc_mr['doc_id']}</td><td>Material Request</td><td>{$doc_mr['po_date']}</td><td>Closed</td></tr>";
					$chain = $invoice->Chain($doc_id);
					if ($rrfq = $invoice->GetDocChildren($chain[0])) {
						while ($rowrfq = $rrfq->fetch_assoc()) {
							echo "<tr>";
							echo "<td>{$rowrfq['doc_id']}</td>";
							echo "<td>Request for Quotation</td>";
							echo "<td>{$rowrfq['po_date']}</td>";
							echo "<td>Closed</td>";
							echo "</tr>";

							if ($rpo = $invoice->GetDocChildren($rowrfq['po_id'])) {
								while ($rowpo = $rpo->fetch_assoc()) {
									echo "<tr>";
									echo "<td>{$rowpo['doc_id']}</td>";
									echo "<td>Purchase Order</td>";
									echo "<td>{$rowpo['po_date']}</td>";
									echo "<td>Closed</td>";
									echo "</tr>";
								}
							}
						}
					}
				}
			} catch (DocumentException $e) {
				//echo $e->getMessage();
				$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
				$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading purchase order failed, one or more of the following might be the cause:</span>");
				$_TEMPLATE->NewFrameBody('<ul>
			<li>Purchase Order document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			</ul>
			<br />Return to <a href="' . $fs(237)->dir . '">Purhcase Orders</a>
			<br /><br />Place a new <a href="' . $fs(230)->dir . '">Material Request</a>
			');
			} catch (DocumentMaterialListException $e) {
				$_TEMPLATE->Title("&nbsp;Materials list error!", null, "", "mark-error");
				$_TEMPLATE->NewFrameBody('<ul>
			<li>Plotting materials list failed</li>
			</ul>');
			} catch (Exception $e) {
			} finally {
			}
		} else {
			//echo $e->getMessage();
			$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
			$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading purchase order failed, one or more of the following might be the cause:</span>");
			$_TEMPLATE->NewFrameBody('<ul>
			<li>Purchase Order document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			</ul>
			<br />Return to <a href="' . $fs(237)->dir . '">Purchase Orders</a>
			<br /><br />Place a new <a href="' . $fs(230)->dir . '">Material Request</a>
			
			');
		}
					?>
<script type="text/javascript">
	Template.HistoryEntry("<?php echo $fs()->dir; ?>/?docid=<?= $doc_id; ?>&token=<?php echo md5("sysdoc_" . $doc_id . session_id()); ?>", "<?php echo $fs()->title; ?>");

	$(document).ready(function() {
		var slocontact = $("#jQinputContact").slo();
		var origianlvalue = $("#jQfieldOrgGrandtotal");
		$("#jQinputAddamount").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
			OnlyFloat(this);
			updateTotal();
		});

		var updateTotal = function() {
			let _add = 0;
			if (!isNaN(parseFloat($("#jQinputAddamount").val()))) {
				_add = parseFloat($("#jQinputAddamount").val());
			}
			let _grand = parseFloat(origianlvalue.val()) + _add;
			$("#jQfieldGrandtotal").val(_grand.numberFormat(2));
		}


		$("#jQpostFormAdditional").on('submit', function(e) {
			e.preventDefault;
			return false;
		});

		$("#jQbuttonSubmit").on("click", function() {
			overlay.show();
			$.ajax({
				type: 'POST',
				url: '<?php echo $fs()->dir; ?>',
				data: $("#jQpostFormAdditional").serialize(),
			}).done(function(o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				if (response == "INERR") {
					messagesys.failure(o);
				} else if (response == "SUCCESS") {
					messagesys.success("Purchase Order posted successfully");
					Template.PageRedirect("<?php echo $fs(237)->dir; ?>" + o, "<?php echo "{$c__settings['site']['title']} - " . $fs(237)->title; ?>", true);
					Template.ReloadSidePanel();
				} else if (response == "DBERR") {
					messagesys.failure(o);
				} else {
					messagesys.failure("Unknown error");
				}
			}).fail(function(a, b, c) {
				messagesys.failure(b);
			}).always(function() {
				overlay.hide();
			});
		});

	});
</script>