<?php
//purchase/rfq/new

use Finance\DocumentException;
use Finance\DocumentMaterialListException;
use System\Finance\Invoice;

$invoice = new Invoice($app);

if ($h__requested_with_ajax && isset($_POST['vdocid'], $_POST['token'])) {
	$post_doc_id	= (int)$_POST['vdocid'];

	$prev_doc		= false;
	$prev_pols		= array();

	if (md5("sysdoc_" . $post_doc_id . session_id()) != $_POST['token']) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Invalid document ID or session has timed out";
		exit;
	}


	$rpo = $app->db->query("
		SELECT 
			po_id,po_title,po_shipto_acc_id,po_billto_acc_id,po_comp_id,po_benf_comp_id,po_costcenter ,ccc_vat,ccc_id
		FROM 
			inv_main JOIN inv_costcenter ON ccc_id = po_costcenter
		WHERE 
			po_id=$post_doc_id AND po_type=" . Invoice::map['MAT_REQ'] . " AND po_close_date IS NULL;
			");
	if ($rpo && $rowpo = $rpo->fetch_assoc()) {
		$prev_doc = $rowpo;
	} else {
		header("HTTP_X_RESPONSE: INERR");
		echo "Invalid material request document or already the request is already closed";
		exit;
	}


	if (!isset($_POST['vvendor'][1]) || (int)$_POST['vvendor'][1] == 0) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Select quotation vendor";
		exit;
	}
	if (!isset($_POST['vcurrency'][1]) || (int)$_POST['vcurrency'][1] == 0) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Select quotation currency";
		exit;
	}

	$_POST['vcontact'][1]			= (int)$_POST['vcontact'][1] == 0 ? "NULL" : (int)$_POST['vcontact'][1];
	$prev_doc['subtotal']			= 0;
	$prev_doc['discount']			= (float)$_POST['vdiscount'] == 0 ? 0 : (float)$_POST['vdiscount'];
	$prev_doc['discount']			= ($prev_doc['discount'] < 0 ? 0 : $prev_doc['discount']);
	$prev_doc['discount']			= ($prev_doc['discount'] > 100 ? 100 : $prev_doc['discount']);
	$prev_doc['vat']				= (float)$prev_doc['ccc_vat'];
	$prev_doc['addtional_amount']	= (float)$_POST['vaddamount'] == 0 ? "NULL" : (float)$_POST['vaddamount'];
	$prev_doc['addtional_amount']	= ($prev_doc['addtional_amount'] < 0 ? 0 : $prev_doc['addtional_amount']);
	$prev_doc['remarks']			= addslashes($_POST['vremarks']);

	$doc_serial 					= $invoice->GetNextSerial(Invoice::map['PUR_QUT'], $prev_doc['po_comp_id'], $prev_doc['po_costcenter']);

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
		if (isset($_POST['vmatlist'][$v['pols_id']])) {
			$prev_pols[$k]['pols_price'] = (float)$_POST['vmatlist'][$v['pols_id']];
			$prev_doc['subtotal'] += (float)$_POST['vmatlist'][$v['pols_id']] * $prev_pols[$k]['pols_issued_qty'];
		} else {
			$prev_pols[$k]['pols_price'] = 0;
		}
	}


	$app->db->autocommit(false);
	$rwo = ("
			INSERT INTO inv_main (
				po_serial,
				po_type,
				po_shipto_acc_id,
				po_billto_acc_id,
				po_usr_id,
				
				po_date,
				po_due_date,
				po_close_date,
				po_title,
				po_remarks,
				
				po_benf_comp_id,
				po_att_id,
				po_cur_id,
				po_rel,
				po_total,
				
				po_vat_rate,
				po_additional_amount,
				po_discount,
				po_comp_id,
				po_costcenter
				) 
			VALUES 
			(
				$doc_serial,
				" . Invoice::map['PUR_QUT'] . ",
				NULL,
				{$prev_doc['po_billto_acc_id']},
				{$app->user->info->id},
				
				NOW(),
				NULL,
				NULL,
				\"{$prev_doc['po_title']}\",
				\"{$prev_doc['remarks']}\",
				
				{$_POST['vvendor'][1]},
				{$_POST['vcontact'][1]},
				{$_POST['vcurrency'][1]},
				{$prev_doc['po_id']},
				{$prev_doc['subtotal']},
				
				{$prev_doc['ccc_vat']},
				{$prev_doc['addtional_amount']},
				{$prev_doc['discount']},
				{$prev_doc['po_comp_id']},
				{$prev_doc['ccc_id']}
			);
		");

	$rwo = $app->db->query($rwo);
	if ($rwo) {
		$submited_doc = $app->db->insert_id;

		$rwolq = "INSERT INTO inv_records (pols_rel_id,pols_po_id,pols_item_id,pols_issued_qty,pols_delivered_qty,pols_price,pols_bom_part) VALUES ";
		$smart = "";
		foreach ($prev_pols as $k => $v) {
			$rwolq .= $smart . "({$prev_pols[$k]['pols_id']},$submited_doc,{$prev_pols[$k]['pols_item_id']},{$prev_pols[$k]['pols_issued_qty']},0,{$prev_pols[$k]['pols_price']},{$prev_pols[$k]['pols_bom_part']})";
			$smart = ",";
		}
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


use Template\Body;

$_TEMPLATE = new Body();
$_TEMPLATE->FrameTitlesStack(true);


$doc_id = $invoice->DocumentURI();
if ($doc_id) {
	try {
		$doc_mr = $invoice->GetMaterialRequestDoc($doc_id);

		if (is_null($doc_mr['po_close_date'])) {
			$_TEMPLATE->Title("New Quotation", null, $doc_mr['doc_id']);
		} else {
			$_TEMPLATE->Title("New Quotation", null, $doc_mr['doc_id'], "mark-lock");
		}


		echo $_TEMPLATE->CommandBarStart();
		echo "<div class=\"btn-set\">";
		echo "<a style=\"color:#333;\" href=\"" . $fs(240)->dir . "/?docid={$doc_mr['po_id']}&token=" . md5("sysdoc_" . $chain[0] . session_id()) . "\">" . $app->translatePrefix(Invoice::map['MAT_REQ'], $doc_mr['po_serial']) . "</a>";
		echo "<span>New Quotation</span>";
		echo "<span class=\"gap\"></span>";
		if (is_null($doc_mr['po_close_date'])) {
			echo "<button class=\"clr-green\" type=\"button\" id=\"jQbuttonSubmit\">Submit RFQ</button>";
		} else {
			echo "<span>Request Closed</span>";
		}

		echo "</div>";
		echo $_TEMPLATE->CommandBarEnd();


		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Material Request Information</span>");
		$_TEMPLATE->NewFrameBody('
			<div class="template-gridLayout">
				<div><span>Number</span><div>' . $app->translatePrefix(Invoice::map['MAT_REQ'], $doc_mr['po_serial']) . '</div></div>
				<div><span>Title</span><div>' . $doc_mr['po_title'] . '</div></div>
				<div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Creation Date</span><div>' . $doc_mr['po_date'] . '</div></div>
				<div>
					<span>Request By</span>
					<div>' . $doc_mr['comp_id'] . ' - ' . $doc_mr['comp_name'] . '</div>
					<div>' . $doc_mr['att_id'] . ' - ' . $doc_mr['po_att_name'] . '</div>
				</div>
				<div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Ship-to</span><div>' . $doc_mr['acc_ship_to_id'] . ' - ' . $doc_mr['acc_ship_to_name'] . '</div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Remarks</span><div>' . nl2br($doc_mr['po_remarks']) . '</div></div>
			</div>
		');

		//Document processing if not closed
		if (is_null($doc_mr['po_close_date'])) {

			$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Details</span>");
			echo $_TEMPLATE->NewFrameBodyStart(); ?>
			<form id="jQpostFormDetails">
				<input type="hidden" name="vdocid" value="<?php echo $doc_mr['po_id']; ?>" />
				<input type="hidden" name="token" value="<?php echo md5("sysdoc_" . $doc_mr['po_id'] . session_id()); ?>" />
				<div class="template-gridLayout role-input">
					<div>
						<span>Vendor</span>
						<div class="btn-set"><input value="" type="text" data-slo="COMPANY" name="vvendor" id="jQinputCompany" class="flex" /></div>
					</div>
					<div>
						<span>Contact</span>
						<div class="btn-set"><input value="" type="text" data-slo="USER_COMPANY_VENDOR" name="vcontact" id="jQinputContact" class="flex" /></div>
					</div>

					<div>
						<span>Currency</span>
						<div class="btn-set"><input value="" type="text" data-slo="CURRENCY" name="vcurrency" id="jQinputCurrency" class="flex" /></div>
					</div>
				</div>
			</form>
			<?php echo $_TEMPLATE->NewFrameBodyEnd();


			$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Materials Price Quotation</span>");
			echo $_TEMPLATE->NewFrameBodyStart();
			echo "<form id=\"jQpostFormMaterials\">
					<table class=\"bom-table\">
						<thead>
							<tr>
								<td width=\"100%\">Material</td>
								<td>Qty</td>
								<td>Price</td>
								<td>Inline</td>
							</tr>
						</thead>
						<tbody id=\"jQmaterialList\">";
			$r = $app->db->query("
								SELECT 
									pols_id,pols_bom_part,pols_issued_qty,
									_mat_materials.mat_long_id,_mat_materials.mat_name,_mat_materials.cat_alias,_mat_materials.mattyp_name,_mat_materials.unt_name,_mat_materials.unt_decim,
									CONCAT(_part_of.mat_long_id,'<br />',_part_of.cat_alias,', ',_part_of.mat_name) AS _mat_bom
								FROM
									inv_records 
									JOIN(
										SELECT
											mat_id, mat_long_id,mat_name,cat_alias,mattyp_name,unt_name,unt_decim
										FROM
											mat_materials 
												JOIN mat_materialtype ON mattyp_id=mat_mattyp_id  
												JOIN mat_unit ON unt_id = mat_unt_id
												LEFT JOIN 
													(
														SELECT 
															CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
														FROM 
															mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
													) AS _category ON mat_matcat_id=_category.matcat_id
									) AS _mat_materials ON _mat_materials.mat_id = pols_item_id
									
									
									LEFT JOIN(
										SELECT
											mat_id,mat_long_id,mat_name,cat_alias
										FROM
											mat_materials 
												LEFT JOIN 
													(
														SELECT 
															CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
														FROM 
															mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
													) AS _category ON mat_matcat_id=_category.matcat_id
									) AS _part_of ON _part_of.mat_id = pols_bom_part
								WHERE
									pols_po_id={$doc_mr['po_id']}
								ORDER BY
									pols_bom_part,pols_id
								");
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

					echo "<td align=\"right\">" . number_format($row['pols_issued_qty'], $row['unt_decim'], ".", ",") . "{$row['unt_name']}</td>";
					echo "<td><div class=\"btn-set small\"><input data-CostRel=\"{$row['pols_id']}\" style=\"width:70px;\" name=\"vmatlist[{$row['pols_id']}]\" data-CostQty=\"{$row['pols_issued_qty']}\" class=\"jQcalcCostInline\" type=\"text\" /></div></td>";
					echo "<td style=\"min-width:110px;\" data-CostRev=\"{$row['pols_id']}\" align=\"right\">0.00</td>";
					echo "</tr>";
				}
			}
			echo "</tbody></table></form>";
			echo $_TEMPLATE->NewFrameBodyEnd();


			$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Summary</span>");
			echo $_TEMPLATE->NewFrameBodyStart();
			?>
			<form id="jQpostFormAdditional">
				<div class="template-gridLayout role-input">
					<div>
						<span>Subtotal</span>
						<div class="btn-set"><input id="jQfieldSubtotal" tabindex="-1" value="0.00" readonly="readonly" class="flex" /></div>
					</div>
					<div>
						<span>Discount</span>
						<div class="btn-set"><input type="text" name="vdiscount" id="jQinputDiscount" class="flex" value="" /><span>%</span></div>
					</div>
					<div>
						<span>Additional Amount</span>
						<div class="btn-set"><input type="text" name="vaddamount" id="jQinputAddamount" class="flex" /></div>
					</div>
				</div>
				<div class="template-gridLayout role-input">
					<div>
						<span>VAT Rate</span>
						<div class="btn-set"><input type="text" name="vvat" id="jQinputVatrate" value="<?php echo number_format($doc_mr['ccc_vat'], Invoice::DecimalsNumber($doc_mr['ccc_vat']), ".", ""); ?>" readonly="readonly" class="flex" /><span>%</span></div>
					</div>
					<div>
						<span>Grand Total</span>
						<div class="btn-set"><input id="jQfieldGrandtotal" tabindex="-1" value="0.00" readonly="readonly" class="flex" /></div>
					</div>
				</div>

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
							echo "<td></td>";
							echo "</tr>";

							if ($rpo = $invoice->GetDocChildren($rowrfq['po_id'])) {
								while ($rowpo = $rpo->fetch_assoc()) {
									echo "<tr>";
									echo "<td>{$rowpo['doc_id']}</td>";
									echo "<td>Purchase Order</td>";
									echo "<td>{$rowpo['po_date']}</td>";
									echo "<td></td>";
									echo "</tr>";
								}
							}
						}
					}

					echo "</tbody></table></form>";
					echo $_TEMPLATE->NewFrameBodyEnd();
				}

					?>
		<script type="text/javascript">
			Template.HistoryEntry("<?php echo $fs()->dir; ?>/?docid=<?= $doc_id; ?>&token=<?php echo md5("sysdoc_" . $doc_id . session_id()); ?>", "<?php echo $fs()->title; ?>");

			$(document).ready(function() {
				var slocontact = $("#jQinputContact").slo();
				$("#jQinputCurrency").slo();

				$("#jQinputCompany").slo({
					'onselect': function(o) {
						slocontact.setparam({
							"slocompany": o.key
						});
					},
					'ondeselect': function() {
						slocontact.setparam({
							"slocompany": 0
						});
						slocontact.clear();
					}
				}).setparam({
					"sloexcludecompany": "<?php echo (int)$rowpo['po_comp_id']; ?>"
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
					let _sum = 0;
					let _add = 0;
					let _vat = 0;
					let _dis = 0;

					$("#jQmaterialList > tr > td > div > input.jQcalcCostInline").each(function() {
						if (isNaN(parseFloat($(this).val())) || isNaN(parseFloat($(this).attr("data-CostQty")))) {
							console.log("?")
							_sum += 0;
						} else {
							_sum += parseFloat($(this).val()) * parseFloat($(this).attr("data-CostQty"));
							console.log("[")
						}
					});

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

					$("#jQfieldSubtotal").val(_sum.numberFormat(2));
					let _grand = _sum + _add - (_sum * _dis / 100);
					_grand += (_grand * _vat / 100);
					$("#jQfieldGrandtotal").val(_grand.numberFormat(2));
				}

				$(".jQcalcCostInline").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
					OnlyFloat(this);
					if (isNaN(parseFloat($(this).val())) || isNaN(parseFloat($(this).attr("data-CostQty")))) {
						$("td[data-CostRev=" + $(this).attr("data-CostRel") + "]").html("0.00");
					} else {
						let calcResult = parseFloat($(this).val()) * parseFloat($(this).attr("data-CostQty"));
						$("td[data-CostRev=" + $(this).attr("data-CostRel") + "]").html(calcResult.numberFormat(2));
					}
					updateTotal();
				});


				$("#jQpostFormDetails,#jQpostFormMaterials,#jQpostFormAdditional").on('submit', function(e) {
					e.preventDefault;
					return false;
				});

				$("#jQbuttonSubmit").on("click", function() {

					overlay.show();
					$.ajax({
						type: 'POST',
						url: '<?php echo $fs()->dir; ?>',
						data: $("#jQpostFormDetails").serialize() + "&" + $("#jQpostFormMaterials").serialize() + "&" + $("#jQpostFormAdditional").serialize(),
					}).done(function(o, textStatus, request) {
						let response = request.getResponseHeader('HTTP_X_RESPONSE');
						if (response == "INERR") {
							messagesys.failure(o);
						} else if (response == "SUCCESS") {
							messagesys.success("Quotation submitted successfully");
							Template.PageRedirect("<?php echo $fs(234)->dir; ?>" + o, "<?php echo "{$c__settings['site']['title']} - " . $fs(234)->title; ?>", true);
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
<?php
	} catch (DocumentException $e) {
		//echo $e->getMessage();
		$_TEMPLATE->Title("Not Found!", null, "", "mark-error");
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading material request order failed, one or more of the following might be the cause:</span>");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Material Request document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<ul>');
	} catch (DocumentMaterialListException $e) {
		$_TEMPLATE->Title("Materials list error!", null, "", "mark-error");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Plotting materials list failed</li>
			<ul>');
	} catch (Exception $e) {
	} finally {
	}
} else {
	$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
	$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading material request order failed, one or more of the following might be the cause:</span>");
	$_TEMPLATE->NewFrameBody('<ul>
			<li>Material Request document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			</ul>
			<b>Actions</b>
			<ul>
				<li>Return to <a href="' . $fs(234)->dir . '">Request for Quotation</a></li>
				<li>Goto to <a href="' . $fs(240)->dir . '">material requests</a></li>
			</ul>
			');
}
?>