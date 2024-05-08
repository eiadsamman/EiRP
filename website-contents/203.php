<?php
include_once("admin/class/materials.php");
$material = new Materials();


/*

  [wosubmit] => 
    [wo_title] => test09141021
    [wo_site] => Array
        (
            [0] => [EGP] : Accounting: Salary Payout
            [1] => 36
        )

    [wo_files] => 
    [wo_remarks] => 
    [wo_item] => Array
        (
            [1034] => 123123
            [1048] => 26664
            [1049] => 22220
        )
    */
if (isset($_POST['wosubmit'])) {
	$output = array("inv" => array(), "msg" => array());
	$hault = false;
	$_POST['wo_title'] = addslashes(trim($_POST['wo_title']));
	$_POST['wo_remarks'] = addslashes(trim($_POST['wo_remarks']));

	if (!isset($_POST['wo_site'][1]) || (int)$_POST['wo_site'][1] == 0) {
		$output['msg'][] = "Select work order site";
		echo json_encode($output);
		exit;
	}
	if ($_POST['wo_title'] == "") {
		$output['msg'][] = "Type in work order title";
		echo json_encode($output);
		exit;
	}
	if (!isset($_POST['wo_item']) || !is_array($_POST['wo_item']) || sizeof($_POST['wo_item']) == 0) {
		$output['msg'][] = "Select work order materials";
		echo json_encode($output);
		exit;
	}

	foreach ($_POST['wo_item'] as $ikey => $ivalue) {
		$single = $material->Fetch($ikey);
		if (false === $single || sizeof($single) == 0) {
			$output['inv'][] = $ikey;
			$hault = true;
		}
		$ivalue = (float)(trim(str_replace(",", "", $ivalue)));
		if ($ivalue == 0) {
			$output['msg']['zero'] = "Material quantity isn't valid";
			$hault = true;
		}
	}
	if (true === $hault) {
		echo json_encode($output);
		exit;
	}

	$app->db->autocommit(false);
	//$app->db->commit();
	$rwo = $app->db->query("
			INSERT INTO mat_wo (wo_creator_id,wo_date,wo_due_date,wo_site,wo_close_date,wo_title,wo_remarks,wo_manager) 
			VALUES 
			(
				{$app->user->info->id},
				NOW(),
				NULL,
				" . (int)$_POST['wo_site'][1] . ",
				NULL,
				\"{$_POST['wo_title']}\",
				\"{$_POST['wo_remarks']}\",
				0
			);
		");
	if ($rwo) {
		$wo_id = $app->db->insert_id;
		$rwolq = "INSERT INTO mat_wo_list (wol_wo_id,wol_item_id,wol_qty) VALUES ";
		$smart = "";
		foreach ($_POST['wo_item'] as $ikey => $ivalue) {
			$ivalue = (float)(trim(str_replace(",", "", $ivalue)));
			$rwolq .= $smart . "($wo_id," . (int)$ikey . ",$ivalue)";
			$smart = ",";
		}
		$rwol = $app->db->query($rwolq);
		if ($rwol) {
			$app->db->commit();
			echo "tamam";
		} else {
			$app->db->rollback();
			$output['msg'][] = "Creating work order failed!";
			echo json_encode($output);
			exit;
		}
	} else {
		$app->db->rollback();
		$output['msg'][] = "Creating work order failed!";
		echo json_encode($output);
		exit;
	}


	exit;
}

if (isset($_POST['addcustomized'])) {
	$output = "";
	foreach ($_POST['itck'] as $chk => $chv) {
		$single = $material->Fetch($chk);
		if (false === $single || sizeof($single) == 0) {
		} else {
			foreach ($single as $si_k => $si_v) {
				$output .= "<tr class=\"cssc\">";
				$output .= "<td><span></span><input type=\"hidden\" name=\"wo_item[$si_k]\" value=\"" . ((float)$_POST['itqt'][$chk]) . "\" /></td>";
				$output .= "<td>" . number_format((float)$_POST['itqt'][$chk], $si_v['unt_decim'], ".", ",") . "</td>";
				$output .= "<td>{$si_v['unt_name']}</td>";
				$output .= "<td>{$si_v['mat_long_id']}</td>";
				$output .= "<td>{$si_v['mat_pn']}</td>";
				$output .= "<td>{$si_v['mat_description']}</td>";
				$output .= "<td>{$si_v['mattyp_name']}</td>";
				$output .= "<td class=\"op-remove noselect\"><span></span></td>";
				$output .= "</tr>";
			}
		}
	}
	echo $output;
	exit;
}

if (isset($_POST['method'], $_POST['id'], $_POST['qty']) && $_POST['method'] == "displaybom") {
	$_POST['id'] = (int)$_POST['id'];
	$_POST['qty'] = (float)$_POST['qty'];
	echo "<form id=\"jQpopForm\"><table><input type=\"hidden\" name=\"addcustomized\" value=\"1\" /><thead>";
	echo "<tr class=\"special\"><td colspan=\"8\">BOM Selector</td></tr>";

	echo "</thead><tbody>";

	echo "<tr class=\"special\"><th><input type=\"checkbox\" class=\"jQcheckB\" data-rel=\"m\" /></th><th colspan=\"7\">Material</th></tr>";
	echo "<tr><td></td><th>ID</th><th>Quantity</th><th>Unit</th><th>Each</th><th width=\"100%\">Material</th><th>Type</th><th>Available</th></tr>";

	$single = $material->Fetch((int)$_POST['id']);
	if (false === $single || sizeof($single) == 0) {
		echo "<tr><td colspan=\"8\">[No materials found]</td></tr>";
	} else {
		foreach ($single as $si_k => $si_v) {
			echo "<tr><td><input name=\"itck[$si_k]\" type=\"checkbox\" data-item_id=\"{$si_k}\" data-lev=\"m\" /></td>";
			echo "<td>{$si_v['mat_long_id']}</td>";
			echo "<td><div class=\"btn-set\"><input name=\"itqt[$si_k]\" style=\"text-align:right;\" type=\"text\" value=\"" . number_format($_POST['qty'], $si_v['unt_decim'], ".", "") . "\"/></div></td>";
			echo "<td>{$si_v['unt_name']}</td>";
			echo "<td>1</td>";
			echo "<td>[{$si_v['mat_pn']}] {$si_v['mat_description']}</td>";
			echo "<td>{$si_v['mattyp_name']}</td>";
			echo "<td>0</td>";
			echo "</tr>";
		}
	}

	echo "<tr class=\"special\"><th><input type=\"checkbox\" class=\"jQcheckB\" data-rel=\"s\" checked=\"checked\" /></th><th colspan=\"7\">Sub-materials</th></tr>";

	$single = $material->BOMGetNodes((int)$_POST['id']);
	if (false === $single || sizeof($single) == 0) {
		echo "<tr><td colspan=\"8\">[No materials found]</td></tr>";
	} else {
		foreach ($single as $si_k => $si_v) {
			$si_v['mat_bom_quantity'] = is_null($si_v['mat_bom_quantity']) ? 0 : $si_v['mat_bom_quantity'];

			echo "<tr><td><input name=\"itck[$si_k]\" type=\"checkbox\" data-item_id=\"{$si_k}\" data-lev=\"s\" checked=\"checked\" /></td>";
			echo "<td>{$si_v['mat_long_id']}</td><td><div class=\"btn-set\"><input name=\"itqt[$si_k]\" style=\"text-align:right;\" type=\"text\" value=\"" . number_format($_POST['qty'] * $si_v['mat_bom_quantity'], $si_v['unt_decim'], ".", "") . "\"/></div></td>";
			echo "<td>{$si_v['unt_name']}</td>";
			echo "<td>" . number_format($si_v['mat_bom_quantity'], $si_v['unt_decim'], ".", ",") . "</td>";
			echo "<td>[{$si_v['mat_pn']}] {$si_v['mat_description']}</td>";
			echo "<td>{$si_v['mattyp_name']}</td>";
			echo "<td>0</td>";
			echo "</tr>";
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
	//Check item BOM level, if on lowest level with no sub-builds; return selected material [level:2]
	$r = $app->db->query("
		SELECT 
			mat_sch_level,CONCAT(IF(comp_sys_default=1,110,310),mattyp_id,LPAD(mat_id,6,'0'),0) AS mat_unique,
			mat_id,mat_pn,mat_description,unt_name,comp_name,mattyp_name,unt_decim
		FROM 
			mat_materials
				JOIN mat_materialtype ON mattyp_id=mat_type 
				JOIN mat_bom_schematic ON mat_sch_type_id=mat_type
				JOIN companies ON comp_id=mat_vendor
				JOIN mat_unit ON mat_unit_id=unt_id
		WHERE
			mat_id=$id;
		");
	if ($r) {
		if ($row = $r->fetch_assoc()) {
			if ($row['mat_sch_level'] == 2) {
				$output['result'] = 2;
				$output['item_id'] = $row['mat_id'];
				$output['item_pn'] = is_null($row['mat_pn']) ? "-" : $row['mat_pn'];
				$output['item_un'] = $row['mat_unique'];
				$output['item_desc'] = $row['mat_description'];
				$output['item_unit'] = $row['unt_name'];
				$output['item_type'] = $row['mattyp_name'];
				$output['item_qtyx'] = number_format($qty, (int)$row['unt_decim'], ".", ",");
				$output['item_qty'] = $qty;
				$output['item_vendor'] = $row['comp_name'];
			} else {
				$output['result'] = 1;
			}
		}
	}
	echo json_encode($output);
	exit;
}
?>
<style type="text/css">
	#jQWOBuilder>tr>td:nth-child(2) {
		text-align: right;
	}

	body {
		counter-reset: level 0;
	}

	.cssc {
		counter-increment: level;
	}

	.cssc>td>span:before {
		content: counter(level);
	}
</style>
<form id="jQpostForm">
	<input type="hidden" name="wosubmit">
	<table>
		<thead>
			<tr class="special">
				<td colspan="2">New Work Order</td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Project Title</th>
				<td width="100%">
					<div class="btn-set" style="max-width: 500px;"><input style="width: 100%" name="wo_title" value="test<?php echo date("dHis"); ?>" type="text" /></div>
				</td>
			</tr>
			<tr>
				<th>Production Plant</th>
				<td>
					<div class="btn-set" style="max-width: 500px;"><input data-slo="ACC_788" name="wo_site" id="jQdestinatoin" style="width: 100%" type="text" /></div>
				</td>
			</tr>
			<tr>
				<th>Process Documents</th>
				<td>
					<div class="btn-set" style="max-width: 500px;"><input style="width: 100%" name="wo_files" type="text" /></div>
				</td>
			</tr>
			<tr>
				<th>Due Date</th>
				<td>
					<div class="btn-set" style="max-width: 500px;"><input style="width: 100%" name="wo_due" type="text" /></div>
				</td>
			</tr>
			<tr>
				<th>Responsible Manager</th>
				<td>
					<div class="btn-set" style="max-width: 500px;"><input style="width: 100%" name="wo_manager" type="text" data-slo="B00S" id="jQinpMan" /></div>
				</td>
			</tr>
			<tr>
				<th>Remarks</th>
				<td>
					<div class="btn-set" style="max-width: 500px;"><textarea style="width: 100%;height:100px" name="wo_remarks"></textarea></div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="btn-set center"><button id="jQpostSubmit" type="button">Submit new work order</button></div>
				</td>
			</tr>
		</tbody>
	</table>
	<table class="hover" style="margin-top: 15px;">
		<thead>
			<tr class="special">
				<td colspan="9">Work Order Materials Description</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<div class="btn-set "><input id="jQbomqty" type="text" style="width:100px;text-align: right" /></div>
				</td>
				<td colspan="5" style="color:#333">
					<div class="btn-set "><input id="jQbomselector" data-slo="BOM" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" type="text" /></div>
				</td>
				<td class="op-add noselect" id="jQaddItem" style="text-align: center;"><span></span></td>
			</tr>
			<tr>
				<td>#</td>
				<td>Quantity</td>
				<td>Unit</td>
				<td>Material Code</td>
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

<script type="text/javascript">
	$(function() {
		$("#jQdestinatoin").slo({
			limit: 10
		});
		$("#jQinpMan").slo({
			limit: 10
		});
		var $jQBuilder = $("#jQWOBuilder");
		var $jQbomSelector = $("#jQbomselector").slo({
			limit: 7,
		});
		var $jQbomQuantity = $("#jQbomqty");
		$(popup.controller()).on("click", "#jQpopBtnCancle", function() {
			popup.close();
		});
		$(popup.controller()).on("click", "#jQpopBtnSubmit", function() {
			$.ajax({
				url: "<?= $fs(203)->dir ?>",
				type: "POST",
				data: $("#jQpopForm").serialize()
			}).done(function(enchantress) {
				$jQBuilder.append($(enchantress));
				$jQbomSelector.clear();
				$jQbomQuantity.val("");
				$jQbomQuantity.focus();
				popup.close();
			});
		});
		$($jQBuilder).on("click", ".op-remove", function() {
			$(this).closest("tr").remove();
		});
		var DisplayBOM = function(mat, qty) {
			$.ajax({
				url: "<?= $fs(203)->dir ?>",
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

		$("#jQaddItem").on("click", function() {
			if (~~$jQbomSelector.key[0].val() == 0 || ~~$jQbomQuantity.val() == 0) {
				messagesys.failure("Material item and Quantity are required");
				return;
			}
			$.ajax({
				url: "<?= $fs(203)->dir ?>",
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
					$newrow += "<td><span></span><input type=\"hidden\" name=\"wo_item[" + json.item_id + "]\" value=\"" + json.item_qty + "\" /></td>";
					$newrow += "<td>" + json.item_qtyx + "</td>";
					$newrow += "<td>" + json.item_unit + "</td>";
					$newrow += "<td>" + json.item_un + "</td>";
					$newrow += "<td>" + json.item_pn + "</td>";
					$newrow += "<td>" + json.item_desc + "</td>";
					$newrow += "<td>" + json.item_type + "</td>";
					$newrow += "<td class=\"op-remove noselect\"><span></span></td>";
					$newrow += "</tr>";
					$jQBuilder.append($($newrow));
					$jQbomSelector.clear();
					$jQbomQuantity.val("");
					$jQbomQuantity.focus();
				} else if (json.result == 1) {
					DisplayBOM($jQbomSelector.hidden[0].val(), $jQbomQuantity.val());
				} else {
					messagesys.failure("Adding material failed, material not found");
				}
			});
		});

		$("#jQpostSubmit").on('click', function() {
			$.ajax({
				url: "<?= $fs(203)->dir; ?>",
				type: "POST",
				data: $("#jQpostForm").serialize(),
			}).done(function(output) {
				alert(output);
			});
		});

	});
</script>