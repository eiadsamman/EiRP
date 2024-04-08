<?php

use \System\Finance\Invoice;
use System\Template\Body;

$material = new Materials();

if ($h__requested_with_ajax && isset($_POST['posubmit'])) {
	$invoice = new Invoice($app);


	$output = array("inv" => array(), "msg" => array(), "result" => false);
	$hault = false;
	$_POST['po_title'] = addslashes(trim($_POST['po_title']));
	$_POST['po_remarks'] = addslashes(trim($_POST['po_remarks']));

	if (!isset($_POST['po_costcenter'][1]) or (int)$_POST['po_costcenter'][1] == 0) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Select Cost Center";
		exit;
	} else {
		$_POST['po_costcenter'][1] = (int)$_POST['po_costcenter'][1];
	}



	if ($_POST['po_title'] == "") {
		header("HTTP_X_RESPONSE: INERR");
		echo "Material Request `Title` is required";
		exit;
	}
	if (!isset($_POST['po_item']) || !is_array($_POST['po_item']) || sizeof($_POST['po_item']) == 0) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Materials list is empty";
		exit;
	}


	$doc_serial = $invoice->GetNextSerial(Invoice::map['MAT_REQ'], $app->user->company->id, $_POST['po_costcenter'][1]);
	if (!$doc_serial) {
		header("HTTP_X_RESPONSE: INERR");
		echo "Operation failed, retrieving document ID failed";
		exit;
	}


	$_POST['po_att_id'] = (int)$_POST['po_att_id'];
	$_POST['po_remarks'] = addslashes($_POST['po_remarks']);


	$app->db->autocommit(false);

	$rwo = $app->db->query("
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
				po_comp_id,
				po_costcenter,
				po_benf_comp_id,
				po_att_id) 
			VALUES 
			(
				$doc_serial,
				" . Invoice::map['MAT_REQ'] . ",
				NULL,
				{$app->user->account->id},
				{$app->user->info->id},
				NOW(),
				NULL,
				NULL,
				\"{$_POST['po_title']}\",
				\"{$_POST['po_remarks']}\",
				{$app->user->company->id},
				{$_POST['po_costcenter'][1]},
				{$app->user->company->id},
				{$_POST['po_att_id']}
			);
		");

	if ($rwo) {
		$submited_doc = $app->db->insert_id;

		$rwolq = "INSERT INTO inv_records (pols_po_id,pols_item_id,pols_issued_qty,pols_delivered_qty,pols_price,pols_bom_part) VALUES ";
		$smart = "";
		foreach ($_POST['po_item'] as $ikey => $ivalue) {
			foreach ($ivalue as $mat_id => $qty) {
				$part_of_bom = "NULL";
				if (isset($_POST['po_bom_part'][$ikey][$mat_id]) && (int)$_POST['po_bom_part'][$ikey][$mat_id] != 0) {
					$part_of_bom = (int)$_POST['po_bom_part'][$ikey][$mat_id];
				}
				$ivalue = (float)(trim(str_replace(",", "", $qty)));
				$rwolq .= $smart . "($submited_doc," . (int)$mat_id . ",$ivalue,0,0,$part_of_bom)";
				$smart = ",";
			}
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
			echo "Material requesting failed, database error";
			exit;
		}
	} else {
		$app->db->rollback();
		header("HTTP_X_RESPONSE: DBERR");
		echo "Material requesting failed, database error";
		exit;
	}
	exit;
}
if (isset($_POST['addcustomized'])) {
	$output = "";
	$_POST['part_of_bom'] = (int)$_POST['part_of_bom'];
	$part_of_bom = $material->Fetch($_POST['part_of_bom']);

	foreach ($_POST['itck'] as $chk => $chv) {
		$single = $material->Fetch($chk);
		if (false !== $single) {
			$output .= "<tr class=\"cssc\">";
			$output .= "<td><span class=\"css_rowNum\"></span>
				<input type=\"hidden\" name=\"po_item[][$chk]\" value=\"" . ((float)$_POST['itqt'][$chk]) . "\" />
				<input type=\"hidden\" name=\"po_bom_part[][$chk]\" value=\"{$_POST['part_of_bom']}\" />
				</td>";
			$output .= "<td>" . number_format((float)$_POST['itqt'][$chk], $single['unt_decim'], ".", ",") . "</td>";
			$output .= "<td>{$single['unt_name']}</td>";
			$output .= "<td>{$single['mat_long_id']}</td>";
			$output .= "<td><span class=\"css_partofbom\">{$part_of_bom['mat_name']}</span><br />{$single['mat_name']}</td>";
			$output .= "<td>{$single['mattyp_name']}</td>";
			$output .= "<td class=\"op-remove noselect\"><span></span></td>";
			$output .= "</tr>";
		}
	}
	echo $output;
	exit;
}
if (isset($_POST['method'], $_POST['id'], $_POST['qty']) && $_POST['method'] == "displaybom") {
	$_POST['id'] = (int)$_POST['id'];
	$_POST['qty'] = (float)$_POST['qty'];
	echo "<form id=\"jQpopForm\"><table class=\"bom-table\">
		<input type=\"hidden\" name=\"addcustomized\" value=\"1\" />
		<input type=\"hidden\" name=\"part_of_bom\" value=\"{$_POST['id']}\" />
		<thead>";
	echo "<tr class=\"special\"><td colspan=\"9\">Bill of Materials selection</td></tr>";

	echo "</thead><tbody>";

	echo "<tr class=\"special\"><th><input type=\"checkbox\" class=\"jQcheckB\" data-rel=\"m\" /></th><th colspan=\"8\">Selected Material</th></tr>";
	echo "<tr><td></td><th colspan=\"2\">ID</th><th>Quantity</th><th>Unit</th><th>Each</th><th width=\"100%\">Material</th><th>Type</th><th>Available</th></tr>";

	$single = $material->Fetch($_POST['id']);
	if (false === $single) {
		echo "<tr><td colspan=\"9\">[No materials found]</td></tr>";
	} else {
		echo "<tr><td colspan=\"2\"><input name=\"itck[{$_POST['id']}]\" type=\"checkbox\" data-item_id=\"{$_POST['id']}\" data-lev=\"m\" /></td>";
		echo "<td>{$single['mat_long_id']}</td>";
		echo "<td><div class=\"btn-set\"><input name=\"itqt[{$_POST['id']}]\" style=\"text-align:right;max-width:90px\" type=\"text\" value=\"" . number_format($_POST['qty'], $single['unt_decim'], ".", "") . "\"/></div></td>";
		echo "<td>{$single['unt_name']}</td>";
		echo "<td>1</td>";
		echo "<td>{$single['mat_name']}</td>";
		echo "<td>{$single['mattyp_name']}</td>";
		echo "<td>0</td>";
		echo "</tr>";
	}

	echo "<tr class=\"special\"><th><input type=\"checkbox\" class=\"jQcheckB\" data-rel=\"s\" checked=\"checked\" colspan=\"2\" /></th><th colspan=\"8\">Semi-finished goods</th></tr>";

	$single = $material->BOMGetNodes((int)$_POST['id']);
	if (false === $single || sizeof($single) == 0) {
		echo "<tr><td colspan=\"9\">[No materials found]</td></tr>";
	} else {
		$multiplier = 1;
		foreach ($single as $si_k => $si_v) {
			$si_v['mat_bom_quantity'] = is_null($si_v['mat_bom_quantity']) ? 0 : $si_v['mat_bom_quantity'];
			$multiplier = $si_v['mat_bom_quantity'];
			echo "<tr><td colspan=\"2\"><input name=\"itck[$si_k]\" type=\"checkbox\" data-item_id=\"{$si_k}\" data-lev=\"s\" /></td>";
			echo "<td>{$si_v['mat_long_id']}</td><td><div class=\"btn-set\"><input name=\"itqt[$si_k]\" style=\"text-align:right;max-width:90px;\" type=\"text\" value=\"" . number_format($_POST['qty'] * $si_v['mat_bom_quantity'], $si_v['unt_decim'], ".", "") . "\"/></div></td>";
			echo "<td>{$si_v['unt_name']}</td>";
			echo "<td>" . number_format($si_v['mat_bom_quantity'], $si_v['unt_decim'], ".", ",") . "</td>";
			echo "<td>{$si_v['mat_name']}</td>";
			echo "<td>{$si_v['mattyp_name']}</td>";
			echo "<td>0</td>";
			echo "</tr>";
			$roh = $material->BOMGetNodes((int)$si_k);
			if ($roh !== false || sizeof($roh) > 0) {

				foreach ($roh as $roh_k => $roh_v) {
					$roh_v['mat_bom_quantity'] = is_null($roh_v['mat_bom_quantity']) ? 0 : $roh_v['mat_bom_quantity'];

					echo "<tr><td></td><td><input name=\"itck[$roh_k]\" type=\"checkbox\" data-item_id=\"{$roh_k}\" data-lev=\"s\" checked=\"checked\" /></td>";
					echo "<td>{$roh_v['mat_long_id']}</td><td><div class=\"btn-set\"><input name=\"itqt[$roh_k]\" style=\"text-align:right;max-width:90px;\" type=\"text\" value=\"" . number_format($_POST['qty'] * $roh_v['mat_bom_quantity'] * $multiplier, $roh_v['unt_decim'], ".", "") . "\"/></div></td>";
					echo "<td>{$roh_v['unt_name']}</td>";
					echo "<td>" . number_format($roh_v['mat_bom_quantity'], $roh_v['unt_decim'], ".", ",") . "</td>";
					echo "<td>{$roh_v['mat_name']}</td>";
					echo "<td>{$roh_v['mattyp_name']}</td>";
					echo "<td>0</td>";
					echo "</tr>";
				}
			}
		}
	}
	echo "</tbody/>";
	echo "</table>";
	echo "<div style=\"margin-top:15px;\" class=\"btn-set center\"><button type=\"button\" id=\"jQpopBtnCancle\">Cancel</button><button id=\"jQpopBtnSubmit\" type=\"button\">Insert materials</button></div>";

	echo "</form>";
	exit;
}
if (isset($_POST['method'], $_POST['id']) && $_POST['method'] == "checkitem") {
	$id = (int)$_POST['id'];
	$qty = (float)str_replace(",", "", $_POST['qty']);
	$output = array(
		"result" => 0,
	);

	$r = $app->db->query("
		SELECT
			mat_id,
			mat_name,
			unt_name,
			mattyp_name,
			unt_decim,
			mat_long_id,
			COUNT(mat_bom_id) AS childrenCount
		FROM
			mat_materials
				JOIN mat_materialtype ON mattyp_id = mat_mattyp_id
				JOIN mat_unit ON mat_unt_id = unt_id
				LEFT JOIN mat_bom ON mat_bom_mat_id = mat_id
		WHERE
			mat_id=$id;
		");
	if ($r) {
		if ($row = $r->fetch_assoc()) {
			if ($row['childrenCount'] == 0) {
				$output['result'] = 2;
				$output['item_id'] = $row['mat_id'];
				$output['long_id'] = $row['mat_long_id'];
				$output['item_name'] = $row['mat_name'];
				$output['item_unit'] = $row['unt_name'];
				$output['item_type'] = $row['mattyp_name'];
				$output['item_qtyx'] = number_format($qty, (int)$row['unt_decim'], ".", ",");
				$output['item_qty'] = $qty;
			} else {
				$output['result'] = 1;
			}
		}
	}
	echo json_encode($output);
	exit;
}
/*AJAX-END*/

if ($h__requested_with_ajax) {
	exit;
}


$_TEMPLATE = new Body("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(false);

?>
<style type="text/css">
	body {
		counter-reset: level 0;
	}

	#jQWOBuilder>tr>td:nth-child(2) {
		text-align: right;
	}

	.cssc {
		counter-increment: level;
	}

	.cssc>td>span.css_rowNum:before {
		content: counter(level);
	}

	.css_partofbom {
		color: #888;
	}
</style>

<?php
$_TEMPLATE->Title($fs()->title, null, null);

echo $_TEMPLATE->CommandBarStart();
echo "<div class=\"btn-set\">";

echo "<a href=\"" . $fs(240)->dir . "\" style=\"color:#333\">Material Requests</a>";
echo "<span class=\"gap\"></span>";
echo "<button id=\"jQpostSubmit\" class=\"clr-green\" type=\"button\">Submit Request</button>";
echo "</div>";
echo $_TEMPLATE->CommandBarEnd();

$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Request Information</span>");

echo $_TEMPLATE->NewFrameBodyStart();
?>
<form id="jQpostFormDetails">
	<input type="hidden" name="posubmit">
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Company</span><input value="<?php echo $app->user->company->name; ?>" type="text" readonly="readonly" /></div>
		<div class="btn-set vertical"><span>Cost Center</span><input name="po_costcenter" data-slo="COSTCENTER_USER" id="jQcostcenter" type="text" /></div>
		<div></div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Request Title</span><input name="po_title" value="" type="text" /></div>
		<div class="btn-set vertical"><span>Attention</span><input name="po_att_id" type="text" data-slo="B00S" id="jQinpMan" /></div>
		<div></div>
	</div>
	<div class="template-gridLayout role-input">
		<div class="btn-set vertical"><span>Remarks</span><textarea style="height:100px" name="po_remarks"></textarea></div>
	</div>
</form>
<?php echo $_TEMPLATE->NewFrameBodyEnd(); ?>



<?php echo $_TEMPLATE->NewFrameTitleStart(false, false); ?>
<div class="btn-set" style="background-color:#fff">
	<span class="flex">Materials List</span>
</div>
<div style="background-color:#fff;padding-left:10px;margin-top:10px;">
	<div class="btn-set ">
		<span>Material</span>
		<input id="jQbomselector" data-slo="BOM" class="flex" type="text" />
		<span>Quantity</span>
		<input id="jQbomqty" type="text" style="width:80px;text-align: right" />
		<button id="jQbtnItemAdd" type="button">Add</button>
	</div>
</div>
<?php echo $_TEMPLATE->NewFrameTitleEnd(); ?>


<?php echo $_TEMPLATE->NewFrameBodyStart(); ?>
<form id="jQpostFormMaterials">
	<table class="bom-table hover">
		<thead>
			<tr>
				<td>#</td>
				<td>Quantity</td>
				<td>Unit</td>
				<td>Part Number</td>
				<td width="100%">Description</td>
				<td>Type</td>
				<td></td>
			</tr>
		</thead>
		<tbody id="jQWOBuilder">
		</tbody>
	</table>
</form>
<?php
echo $_TEMPLATE->NewFrameBodyEnd();
$_TEMPLATE->TailGap();
?>

<script type="text/javascript">
	$(function() {
		Template.HistoryEntry("<?php echo $fs()->dir; ?>", "<?php echo $fs()->title; ?>");

		$("[name=po_title]").focus();

		$("#jQinpMan").slo({
			limit: 10
		});
		var $jQBuilder = $("#jQWOBuilder");
		var $jQbomQuantity = $("#jQbomqty");
		var $jQbomSelector = $("#jQbomselector").slo({
			limit: 7,
		});
		var $jQcostcenter = $("#jQcostcenter").slo();;
		<?php /*var $jQinputShipto=$("#jQinputShipto").slo();
		$jQinputShipto.setparam({'slocompany':<?php echo $_UXSEXR['company']['id'];?>});*/ ?>

		var DisplayBOM = function(mat, qty) {
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					"method": "displaybom",
					"id": mat,
					"qty": qty
				}
			}).done(function(output) {
				popup.content(output).show();
				$(popup.controller()).find(".jQcheckB").on('click', function() {
					var _stat = $(this).prop("checked");
					var _type = $(this).attr("data-rel");
					$(popup.controller()).find("input[data-lev=" + _type + "]").prop("checked", _stat);
				});
			});
		}

		var fnFormSubmit = function() {
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $("#jQpopForm").serialize()
			}).done(function(enchantress) {
				$jQBuilder.append($(enchantress));
				$jQbomSelector.clear();
				$jQbomSelector.focus();
				$jQbomQuantity.val("");
				popup.close();
			});
		}
		var fnInsertMaterial = function() {
			if (~~$jQbomSelector.key[0].val() == 0 || ~~$jQbomQuantity.val() == 0) {
				messagesys.failure("Material item and Quantity are required");
				return;
			}
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					"method": "checkitem",
					"id": $jQbomSelector.key[0].val(),
					"qty": $jQbomQuantity.val()
				}
			}).done(function(output) {
				try {
					json = JSON.parse(output);
				} catch (e) {
					messagesys.failure("Parsing output failed");
					return false;
				}
				if (json.result == 2) {
					$newrow = "";
					$newrow += "<tr class=\"cssc\">";
					$newrow += "<td><span class=\"css_rowNum\"></span>\
						<input type=\"hidden\" name=\"po_item[][" + json.item_id + "]\" value=\"" + json.item_qty + "\" />\
						<input type=\"hidden\" name=\"po_bom_part[][0]\" value=\"0\" />\
						</td>";
					$newrow += "<td>" + json.item_qtyx + "</td>";
					$newrow += "<td>" + json.item_unit + "</td>";
					$newrow += "<td>" + json.long_id + "</td>";
					$newrow += "<td>" + json.item_name + "</td>";
					$newrow += "<td>" + json.item_type + "</td>";
					$newrow += "<td class=\"op-remove noselect\"><span></span></td>";
					$newrow += "</tr>";
					$jQBuilder.append($($newrow));
					$jQbomSelector.clear();
					$jQbomSelector.focus();

					$jQbomQuantity.val("");
				} else if (json.result == 1) {
					DisplayBOM($jQbomSelector.key[0].val(), $jQbomQuantity.val());
				} else {
					messagesys.failure("Adding material failed, material not found");
				}
			});
		}



		$($(popup.controller())).on("click", "#jQpopBtnCancle", function() {
			popup.close();
		});
		$($(popup.controller())).on("click", "#jQpopBtnSubmit", function() {
			fnFormSubmit();
		});
		$($jQBuilder).on("click", ".op-remove", function() {
			$(this).closest("tr").remove();
		});
		$("#jQbtnItemAdd").on("click", function() {
			fnInsertMaterial();
		});
		$("#jQaddItem").on("click", function() {
			fnInsertMaterial();
		});
		$("#jQbomqty").on("keydown", function(e) {
			var keycode = (e.keyCode ? e.keyCode : e.which);
			if (keycode == 13) {
				fnInsertMaterial();
			}
		});


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
					messagesys.success("Material Request posted successfully");
					Template.PageRedirect("<?php echo $fs(240)->dir; ?>" + o, "<?php echo "{$c__settings['site']['title']} - " . $fs(240)->title; ?>", true);
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