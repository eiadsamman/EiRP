<?php
use System\Template\Gremium;

/*
 * Todo:
 *	create master table for BOM insertions with date and owner
 *
 */


if (isset($_POST['method']) && $_POST['method'] == 'save') {
	$matid = (int) $_POST['matid'];

	if (is_array($_POST['bom_material']) && sizeof($_POST['bom_material']) > 0) {
		$material_id = 0;
		$material_part_id = 0;
		$material_quantity = 0;
		$rbool = true;


		$app->db->autocommit(false);

		$rbool &= $app->db->query("DELETE FROM mat_bom WHERE mat_bom_mat_id={$matid};");
		$stmt = $app->db->prepare("INSERT INTO mat_bom (mat_bom_mat_id, mat_bom_part_id, mat_bom_quantity, mat_bom_level) VALUES (?,?,?,1);");
		$stmt->bind_param("iid", $material_id, $material_part_id, $material_quantity);
		foreach ($_POST['bom_material'] as $lvl1_k => $lvl1_v) {
			if (isset($lvl1_v[1], $_POST['bom_quantity'][$lvl1_k]) && (int) $lvl1_v[1] > 0) {
				/* Set the material ID in the BOM table to `negative` to exclude it from integrity check */
				$material_id = -((int) $matid);
				$material_part_id = (int) $lvl1_v[1];
				$material_quantity = (float) $_POST['bom_quantity'][$lvl1_k];
				$rbool &= $stmt->execute();
				if (!$rbool) {
					break;
				}
			}
		}


		/**
		 * Recursive product parts integrity check
		 * Search for material id within the bom recursively
		 */
		$rintegrity = $app->db->query(
			"WITH RECURSIVE cte (mat_bom_mat_id, mat_bom_part_id, level) AS (

				SELECT     mat_bom_mat_id, mat_bom_part_id, 1 level
				FROM       mat_bom
				WHERE      mat_bom_mat_id = -$matid

				UNION ALL

				SELECT     p.mat_bom_mat_id, p.mat_bom_part_id, level + 1
				FROM       mat_bom p
				INNER JOIN cte
				  ON p.mat_bom_mat_id = cte.mat_bom_part_id AND level < 100
			)
			
			SELECT * FROM cte
			WHERE cte.mat_bom_part_id = $matid;"
		);

		if ($rintegrity) {
			/* If material found inside the material itself, rollback all queries and return an error */
			if ($rintegrity->num_rows > 0) {
				header("HTTP_X_RESPONSE: INTEGRITYERROR");
				$app->db->rollback();
			} else {
				/* Integrity check passed, set material ID back to positive */
				$rfixminus = $app->db->query("UPDATE mat_bom SET mat_bom_mat_id = $matid WHERE mat_bom_mat_id = -$matid;");
				if ($rfixminus) {
					header("HTTP_X_RESPONSE: SUCCESS");
					$app->db->commit();
				} else {
					header("HTTP_X_RESPONSE: ERR");
					$app->db->rollback();
				}
			}

		} else {
			header("HTTP_X_RESPONSE: ERR");
			$app->db->rollback();
		}


	} else {
		$app->db->query("DELETE FROM mat_bom WHERE mat_bom_mat_id={$matid};");
		header("HTTP_X_RESPONSE: SUCCESS");
	}

	exit;
}

if (isset($_POST['method']) && $_POST['method'] == 'getunit') {
	$id = (int) $_POST['id'];
	$r = $app->db->query(
		"SELECT 
			unt_name, unt_decim
		FROM
			mat_materials 
				JOIN mat_unit ON mat_unt_id=unt_id
		WHERE
			mat_id = $id
		"
	);
	if ($r && $row = $r->fetch_assoc()) {
		header("HTTP_X_RESPONSE: SUCCESS");
		echo $row['unt_name'];
	} else {
		header("HTTP_X_RESPONSE: ERR");
		echo "Submitting quotation failed, database error";
	}
	exit;
}


if (isset($_POST['method'], $_POST['id']) && $_POST['method'] == "show") {
	$bomid = (int) $_POST['id'];
	$r = $app->db->query(
		"SELECT 
			mat_id, mat_long_id, mattyp_description ,cat_alias, mat_name, mat_date
		FROM
			mat_materials 
				JOIN mat_materialtype ON mattyp_id=mat_mattyp_id 
				LEFT JOIN 
				(
					SELECT 
						CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
					FROM 
						mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
				) AS _category ON mat_matcat_id=_category.matcat_id
		WHERE
			mat_id = $bomid
		"
	);

	echo "<div>";
	if ($r) {
		if ($row = $r->fetch_assoc()) {
			echo '
			<div>
				<div class="template-gridLayout">
					<div><span>Material ID</span><div>' . $row['mat_long_id'] . '</div></div>
					<div><span>Creation date</span><div>' . $row['mat_date'] . '</div></div>
					<div><span></span><div></div></div>
				</div>
				<div class="template-gridLayout">
					<div><span>Type</span><div>' . $row['mattyp_description'] . '</div></div>
					<div><span>Category</span><div>' . $row['cat_alias'] . '</div></div>
					<div><span></span><div></div></div>
				</div>
				<div class="template-gridLayout">
					<div><span>Material Name</span><div>' . $row['mat_name'] . '</div></div>
				</div>
			</div>';
		}
	}
	echo "<div><table class=\"bom-table form-table\" id=\"bom-contents\">";
	echo "
		<thead>
			<tr>
				<td>#</td>
				<td width=\"100%\">Part material</td>
				<td colspan=\"2\">Quantity</td>
			</tr>
		</thead>
		<tbody>";

	$r = $app->db->query(
		"SELECT 
				mat_bom_id,mat_id,mat_long_id,cat_alias,mat_name,unt_name,unt_decim,mat_bom_quantity
			FROM
				mat_bom 
					JOIN (
						SELECT 
							mat_id, mat_long_id, mattyp_id ,cat_alias, mat_name, unt_name, unt_decim
						FROM
							mat_materials 
								JOIN mat_materialtype ON mattyp_id=mat_mattyp_id 
								JOIN mat_unit ON mat_unt_id=unt_id
								LEFT JOIN 
								(
									SELECT 
										CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
									FROM 
										mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
								) AS _category ON mat_matcat_id=_category.matcat_id
						) AS mast_conj ON mast_conj.mat_id = mat_bom_part_id
			WHERE
				mat_bom_mat_id = $bomid
			ORDER BY
				mat_bom_id
			"
	);

	if ($r && $r->num_rows > 0) {
		while ($row = $r->fetch_assoc()) {
			echo "<tr class=\"level\">
					<td>
						<span></span>
					</td>
					<td>
						<div class=\"btn-set\">
						<input type=\"text\" class=\"flex\" name=\"bom_material[d{$row['mat_bom_id']}]\" value=\"{$row['mat_long_id']} {$row['cat_alias']}, {$row['mat_name']}\" data-slo=\"BOM\" data-slodefaultid=\"{$row['mat_id']}\" />
						</div>
					</td>
					<td>
						<div class=\"btn-set\">
							<input type=\"text\" name=\"bom_quantity[d{$row['mat_bom_id']}]\" class=\"material-qty\" value=\"" . number_format($row['mat_bom_quantity'], $row['unt_decim'], ".", ",") . "\" />
							<span style=\"min-width:50px;text-align:center\" class=\"mat-unit\">
								{$row['unt_name']}
							</span>
						</div>
					</td>
					<td class=\"op-remove jQdel_lvl0\"><span></span></td>
				</tr>";
		}
	}
	echo "</tbody></table></div>";
	echo "</div>";
	exit;
}


?>
<style>
	.material-qty {
		width: 100px;
		text-align: right;
	}

	body {
		counter-reset: level 0;
	}

	.level {
		counter-increment: level;
	}

	.level>td:first-child>span {
		display: block;
		padding: 7px;
	}

	.level>td>span:before {
		content: counter(level);
	}
</style>
<?php
$grem = new Gremium\Gremium();
$grem->header()->serve("<h1>BOM Manager</h1>");
$grem->menu()->open();
echo "<span>Material name</span>";
echo "<input type=\"text\" data-slo=\"BOM\" class=\"flex\" id=\"jQmaterialSelection\" />";
echo "<button type=\"button\" id=\"jQsubmit\">Save material build</button>";
$grem->getLast()->close();

$grem->title()->serve("<span class=\"flex\">Material desciprtion</span>");
$grem->article()->serve("<div id=\"jQdesc\"></div>");

$grem->title()->serve("Bill of materials");
$grem->legend()->serve("<span class=\"flex\"></span><button type=\"button\" disabled class=\"edge-left\" id=\"jqAddmaterial\">Add material</button>");
$grem->article()->serve("<form action=\"\" id=\"jQmainform\"><input type=\"hidden\" name=\"method\" value=\"save\" /><div id=\"jQmain\"></div></form>");
unset($grem);
?>
<script>
	$(function () {
		let uniqueid = 0;
		$("#jQmainform").on('submit', function (e) {
			e.preventDefault();
			var $this = $(this);
			$.ajax({
				url: "",
				type: "POST",
				data: $this.serialize() + '&matid=' + $("#jQmaterialSelection_1").val()
			}).done(function (o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				if (response == "ERR") {
					messagesys.failure(o);
				} else if (response == "SUCCESS") {
					messagesys.success("Material BOM build updated successfully");
				} else if (response == "INTEGRITYERROR") {
					messagesys.failure("Integriy error, parent material can not be a part of itself");
				}
			});
			return false;
		});
		$("#jQsubmit").on('click', function () {
			$("#jQmainform").submit();
		});

		let getUnit = function (mat_id, dom) {
			overlay.show();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: { "method": "getunit", "id": mat_id },
			}).done(function (o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				if (response == "ERR") {
					messagesys.failure(o);
				} else if (response == "SUCCESS") {
					dom.html(o);
				}
			}).fail(function (m) {
				messagesys.failure(m);
			}).always(function () {
				overlay.hide();
			});
		}
		let clearUnit = function (dom) {
			dom.html("-");
		}


		$("#jQmaterialSelection").slo({
			"limit": 7,
			onselect: function (selected_bom) {
				$.ajax({
					url: "",
					type: "POST",
					data: { "id": selected_bom.key, "method": "show" }
				}).done(function (o, textStatus, request) {
					$data = $(o);
					$childs = [];
					$childs[0] = $data.children().first().first();
					$childs[1] = $childs[0].next();
					$childs[1].find("input[data-slo]").slo({
						onselect: function (data) {
							getUnit(data.key, $(this).closest("tr").find(".mat-unit"));
						}, ondeselect: function () {
							clearUnit($(this).closest("tr").find(".mat-unit"));
						}
					});
					$("#jQdesc").html($childs[0]);
					$("#jQmain").html($childs[1]);

					$(".material-qty").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
						OnlyFloat(this, null, 0);
					});
					$("#jqAddmaterial").prop("disabled", false)
				});
			},
			ondeselect: function () {
				$("#jQmain").html("");
				$("#jQdesc").html("");
				$("#jqAddmaterial").prop("disabled", true)
			}
		});

		$("#jqAddmaterial").on('click', function () {
			uniqueid++;
			let $temp = "";
			$temp += "<tr class=\"level\"><td><span></span></td>";
			$temp += "<td><div class=\"btn-set\"><input type=\"text\" class=\"flex\" name=\"bom_material[a" + uniqueid + "]\" data-slo=\"BOM\" /></div></td>";
			$temp += "<td><div class=\"btn-set\">";
			$temp += "<input type=\"text\" name=\"bom_quantity[a" + uniqueid + "]\" class=\"material-qty\" value=\"0\" />";
			$temp += "<span style=\"min-width:50px;text-align:center\" class=\"mat-unit\">-</span>";
			$temp += "</div></td>";
			$temp += "<td class=\"op-remove jQdel_lvl0\"><span></span></td>";
			$temp += "</tr>";
			$temp = $($temp);


			$temp.find("input[data-slo]").slo({
				onselect: function (data) {
					getUnit(data.key, $temp.find(".mat-unit"));
				}, ondeselect: function () {
					clearUnit($(this).closest("tr").find(".mat-unit"));
				}
			});

			$("#bom-contents").append($temp);
			$(".material-qty").on("input keydown keyup mousedown mouseup select contextmenu drop", function () {
				OnlyFloat(this, null, 0);
			});
		});

		$("#jQmain").on('click', '.jQdel_lvl0', function () {
			$(this).closest("tr").remove();
		});

	});
</script>