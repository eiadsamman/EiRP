<?php
use System\Template\Gremium\Gremium;

if ($app->xhttp) {
	if (isset($_POST['method']) && $_POST['method'] == "checkitem") {
		header("Content-Type: application/json; charset=utf-8");
		$id     = (int) $_POST['materialSelection'][1];
		$qty    = (float) str_replace(",", "", $_POST['materialQuantity']);
		$output = ["result" => 0];

		$r = $app->db->query(
			"SELECT
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
				mat_id = $id;
			"
		);
		if ($r && $row = $r->fetch_assoc()) {
			if ($row['childrenCount'] == 0) {
				$output['result']    = 2;
				$output['item_id']   = $row['mat_id'];
				$output['long_id']   = $row['mat_long_id'];
				$output['item_name'] = $row['mat_name'];
				$output['item_unit'] = $row['unt_name'];
				$output['item_type'] = $row['mattyp_name'];
				$output['item_qtyx'] = number_format($qty, (int) $row['unt_decim'], ".", ",");
				$output['item_qty']  = $qty;
			} else {
				$output['result'] = 1;
			}
		}
		echo json_encode($output);
		exit;
	}

	exit;
}



$grem = new Gremium();

$grem->header()->prev("href=\"{$fs(240)->dir}\" data-href=\"{$fs(240)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\">
			<button class=\"plus\" id=\"js-input_submit\" tabindex=\"9\">&nbsp;Submit Request</button></div>");
$grem->title()->serve("Request information");
$grem->article()->open();
?>
<form id="jQpostFormDetails">
	<input type="hidden" name="posubmit">

	<div class="form">
		<label>
			<h1>Cost Center</h1>
			<div class="btn-set">
				<input name="po_costcenter" class="flex" data-slo="COSTCENTER_USER" id="formCostcenter" type="text" />
				<span><?= $app->user->company->name ?></span>
			</div>
		</label>
		<label>
			<div>
			</div>
		</label>
	</div>

	<div class="form">
		<label>
			<h1>Request Title</h1>
			<div class="btn-set">
				<input name="po_title" value="" class="flex" type="text" />
			</div>
		</label>
		<label>
			<div>
			</div>
		</label>
	</div>

	<div class="form">
		<label>
			<h1>Description and Comments</h1>
			<div class="btn-set">
				<textarea style="height:100px" class="flex" name="po_remarks"></textarea>
			</div>
		</label>
	</div>

</form>

<?php
$grem->getLast()->close();
$grem->title()->serve("Requested materials");
$grem->article()->open();
?>

<form id="formMaterialAdd" action="<?= $fs()->dir ?>">
	<div class="form">
		<label style="flex: 1;">
			<h1>Material</h1>
			<div class="btn-set">
				<input name="materialSelection" id="materialSelection" data-slo="BOM" class="flex" type="text" />
			</div>
		</label>
		<label style="min-width:200px;max-width:200px">
			<h1>Quantity</h1>
			<div class="btn-set">
				<input name="materialQuantity" id="materialQuantity" type="text" inputmode="decimal" min="0" style="width:80px;text-align: right"
					class="flex" />
				<button id="materialAddButton" type="button">Add</button>
			</div>
		</label>
	</div>
</form>

<form id="formMaterialsList">
	<table class="hover">
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
		<tbody id="materialsList">
		</tbody>
	</table>
</form>

<?php
$grem->terminate();
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

	.cssc>td>span.c:before {
		content: counter(level);
	}

	.cssc>td>div.btn-set>input[type="number"] {
		width: 120px;
		text-align: right;
	}

	.css_partofbom {
		color: #888;
	}
</style>

<script type="module" src="">

	new MaterialRequest();


	$(function () {
		return
		var fnFormSubmit = function () {
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $("#jQpopForm").serialize()
			}).done(function (enchantress) {
				materialsList.append($(enchantress));
				materialSelection.clear();
				materialSelection.focus();
				materialQuantity.val("");
				popup.close();
			});
		}
		$($(popup.controller())).on("click", "#jQpopBtnCancle", function () {
			popup.close();
		});
		$($(popup.controller())).on("click", "#jQpopBtnSubmit", function () {
			fnFormSubmit();
		});
		$(materialsList).on("click", ".op-remove", function () {
			$(this).closest("tr").remove();
		});
		$("#jQbtnItemAdd").on("click", function () {
			fnInsertMaterial();
		});
		$("#jQaddItem").on("click", function () {
			fnInsertMaterial();
		});
		$("#jQbomqty").on("keydown", function (e) {
			var keycode = (e.keyCode ? e.keyCode : e.which);
			if (keycode == 13) {
				fnInsertMaterial();
			}
		});

		$("#jQpostSubmit").on('click', function () {
			overlay.show();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $("#jQpostFormDetails").serialize() + "&" + $("#formMaterialsList").serialize(),
			}).done(function (o, textStatus, request) {
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
			}).fail(function (m) {
				messagesys.failure(m);
			}).always(function () {
				overlay.hide();
			});
		});

	});
</script>