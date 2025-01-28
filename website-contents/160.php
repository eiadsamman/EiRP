<?php
use System\Models\Material;
use System\Layout\Gremium;


function tempalteDescription($materialId = "-", $creationDate = "-", $type = "-", $category = "-", $materialName = "-")
{
	return "<div>
		<div class=\"form\"><label><h1>Material ID</h1><div class=\"btn-set\"><span>$materialId</span></div></label><label><h1>Creation date</h1><div class=\"btn-set\"><span>$creationDate</span></div></label></div>
		<div class=\"form\"><label><h1>Type</h1><div class=\"btn-set\"><span>$type</span></div></label><label><h1>Category</h1><div class=\"btn-set\"><span>$category</span></div></label></div>
		<div class=\"form\"><label><h1>Material Name</h1><div class=\"btn-set\"><span>$materialName</span></div></label><label></label></div>
		</div>";
}


if (isset($_POST['method']) && $_POST['method'] == 'save') {
	$matid = (int) $_POST['matid'];

	if (is_array($_POST['bom_qty']) && is_array($_POST['bom_unit']) && sizeof($_POST['bom_qty']) > 0) {
		$output            = [
			"result" => false,
			"message" => "",
			"highlight" => []
		];
		$material_id       = 0;
		$material_part_id  = 0;
		$material_quantity = 0;
		$unit_id           = 0;
		$rbool             = true;

		$presented_parts = [];
		foreach ($_POST['bom_qty'] as $part_id => $bom_qty) {
			if (in_array($part_id, $presented_parts)) {
				$rbool                 = false;
				$output['highlight'][] = $part_id;
				continue;
			}
			if ((float) $bom_qty > 0 && (int) $_POST['bom_unit'][$part_id][1] > 0) {
				$presented_parts[] = $part_id;
			} else {
				$rbool                 = false;
				$output['highlight'][] = $part_id;
				$output['message']     = "Invalid parts parameters, check all parts quantities and units";
			}
		}

		if (!$rbool) {
			echo json_encode($output);
			exit;
		}

		$app->db->autocommit(false);

		$rbool &= $app->db->query("DELETE FROM mat_bom WHERE mat_bom_mat_id={$matid};");
		$stmt  = $app->db->prepare(
			"INSERT INTO 
				mat_bom (mat_bom_mat_id, mat_bom_part_id, mat_bom_quantity, mat_bom_level, mat_bom_unitsystem, mat_bom_unit,mat_bom_tolerance)
			SELECT 
				?,mat_id,?,1,mat_unitsystem,?,0 
			FROM 
				mat_materials
			WHERE
				mat_id = ? "
		);

		$stmt->bind_param("idii", $material_id, $material_quantity, $unit_id, $material_part_id);

		foreach ($_POST['bom_qty'] as $part_id => $bom_qty) {
			if ((float) $bom_qty > 0 && (int) $_POST['bom_unit'][$part_id][1] > 0) {
				$material_id       = -((int) $matid);
				$material_part_id  = (int) $part_id;
				$material_quantity = (float) $bom_qty;
				$unit_id           = (int) $_POST['bom_unit'][$part_id][1];
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

		if ($rbool && $rintegrity) {
			/* If material found inside the material itself, rollback all queries and return an error */
			if ($rintegrity->num_rows > 0) {
				$app->db->rollback();
			} else {
				/* Integrity check passed, set material ID back to positive */
				$rfixminus = $app->db->query("UPDATE mat_bom SET mat_bom_mat_id = $matid WHERE mat_bom_mat_id = -$matid;");
				if ($rfixminus) {
					$output['result'] = true;
					$app->db->commit();
				} else {
					$app->db->rollback();
				}
			}
		} else {
			$app->db->rollback();
		}

	}

	echo json_encode($output);
	exit;
}


if (isset($_POST['method'], $_POST['id']) && $_POST['method'] == "show") {
	header("Content-Type: application/json; charset=utf-8");
	$output   = ["description" => [], "items" => []];
	$material = new Material($app);
	if ($loadedMaterial = $material->load((int) $_POST['id'])) {
		$output['description']['id']           = $loadedMaterial->longId;
		$output['description']['creationDate'] = $loadedMaterial->creationDate->format("Y-m-d");
		$output['description']['type']         = (string) $loadedMaterial->type;
		$output['description']['category']     = $loadedMaterial->category->group->name . ": " . $loadedMaterial->category->name;
		$output['description']['name']         = $loadedMaterial->name;
		foreach ($material->parts($loadedMaterial->id) as $part) {
			$output['items'][$part->id] = [
				'id' => "{$part->id}",
				'longId' => "{$part->longId}",
				'creationDate' => $part->creationDate->format("Y-m-d"),
				'type' => (string) $part->type,
				'category' => $part->category->group->name . ": " . $part->category->name,
				'quantity' => $part->quantity,
				'name' => $part->name,
				'unitName' => $part->unit->symbol,
				'unitId' => $part->unit->id,
				'unitSystemId' => $part->unitSystem->value
			];
		}
	}
	echo json_encode($output);
	exit;
}


?>
<style>
	.table {
		&.local01 {
			grid-template-columns: minmax(40px, auto) minmax(130px, 1fr) 180px 60px;
		}

		>main.invalid>div {
			color: red;

			&:first-child {
				border-left: solid 3px red
			}
		}

		.input-qunatity {
			text-align: right;
			width: 100px;
		}
	}
</style>

<?php
$grem = new Gremium\Gremium(true, false);
$grem->header()->serve("<h1>BOM Manager</h1>");
$grem->menu()->open();
echo <<<HTML
	<span>Material name</span>
	<input type="text" data-slo="BOM" class="flex" id="material-select-button" />
	<button type="button" id="save-button" disabled>Save material build</button>
HTML;
$grem->getLast()->close();
$grem->title()->serve("<span class=\"flex\">Material desciprtion</span>");
$grem->article()->serve("<div id=\"material-description\">" . tempalteDescription() . "</div>");
$grem->title()->serve("Material parts");

$grem->article()->open();
echo <<<HTML
	<form id="formMaterialAdd" action="{$fs()->dir}">
		<div class="form" style="">
			<label style="flex:1;">
				<h1>Material</h1>
				<div class="btn-set">
					<input id="input-part-selection" data-slo="BOM" class="flex" type="text" />
				</div>
			</label>
			<label style="max-width:80px">
				<h1></h1>
				<div class="btn-set" style="max-width:80px;min-width:80px">
					<button id="button-part-add" disabled class="flex" type="button">Add</button>
				</div>
			</label>
		</div>
	</form>
	
	<form id="material-list-form">
		<div id="material-parts" class="table local01">
			<header>
				<div>#</div>
				<div>Material</div>
				<div>Quantity</div>
				<div></div>
			</header>
		</div>
	</form>
HTML;
$grem->getLast()->close();

$grem->terminate(true);
?>
<script>
	class BillOfMaterial {
		constructor() {
			this.activeMaterial = 0;
			this.submitButton = null;
			this.submitForm = null;
			this.partAddButton = null;
			this.partAddMaterial = null;
			this.partSelected = null;
			this.materialSelectButton = null;
			this.materialParts = null;

			this.events();
		}

		descriptionTemplate(id = "-", creationDate = "-", type = "-", category = "-", name = "-") {
			return `
				<div>
					<div class="form"><label><h1>Material ID</h1><div class="btn-set"><span>${id}</span></div></label><label><h1>Creation date</h1><div class="btn-set"><span>${creationDate}</span></div></label></div>
					<div class="form"><label><h1>Type</h1><div class="btn-set"><span>${type}</span></div></label><label><h1>Category</h1><div class="btn-set"><span>${category}</span></div></label></div>
					<div class="form"><label><h1>Material Name</h1><div class="btn-set"><span>${name}</span></div></label><label></label></div>
				</div>
			`
		}

		events() {
			document.addEventListener("DOMContentLoaded", () => {
				this.partAddMaterial = document.getElementById("input-part-selection");
				this.submitButton = document.getElementById("save-button");
				this.submitForm = document.getElementById("material-list-form");
				this.partAddButton = document.getElementById("button-part-add");
				this.materialDescription = document.getElementById("material-description");
				this.materialSelectButton = document.getElementById("material-select-button");
				this.materialParts = document.getElementById("material-parts");
				this.submitButton.addEventListener("click", () => {
					this.post();
				});

				this.partAddMaterial = $(this.partAddMaterial).slo({
					onselect: (data) => {
						console.log(data)
						this.partSelected = {
							"material": {
								"id": parseInt(data.key),
								"name": data.value
							},
							"quantity": 0,
							"unit": {
								"system": data.embeds.params.unitsystem,
								"id": 0,
								"name": "",
							}
						}
						this.addPartRow(this.partSelected);
						this.partAddMaterial.clear();

					}, ondeselect: () => { this.partSelected = null; }
				}).clear();
				this.partAddMaterial.disable();

				this.submitForm.addEventListener("submit", (e) => {
					e.preventDefault();
					this.post();
					return;
				});
				this.partAddButton.addEventListener("click", () => {
					if (this.partSelected)
						this.addPartRow(this.partSelected);
				});
				$(this.materialSelectButton).slo({
					onselect: (selected_bom) => {
						this.activeMaterial = selected_bom.key
						this.submitButton.disabled = false;
						this.partAddButton.disabled = false;
						this.partAddMaterial.enable();
						this.loadMaterial();
					},
					ondeselect: () => {
						this.activeMaterial = 0;
						this.materialDescription.innerHTML = this.descriptionTemplate();
						this.clearForms();
						this.partAddButton.disabled = true;
						this.submitButton.disabled = true;
						this.partAddMaterial.disable();
					}
				}).clear();
			});
		}

		updateCounters() {
			let counter = 1;
			this.materialParts.querySelectorAll("main").forEach((e) => {
				e.querySelector("div>span").innerHTML = `${counter}`;
				counter++;
			});
		}

		clearForms() {
			this.materialParts.querySelectorAll("main").forEach((e) => {
				e.remove();
			});
		}

		clearUnit(domOwner) {
			let dom = domOwner.querySelector(".qty-unit-container");
			if (dom != undefined) {
				dom.innerHTML = "";
			}
		}

		async loadMaterial() {
			this.clearForms();
			const formData = new FormData();
			formData.append("method", "show");
			formData.append("id", this.activeMaterial);
			const response = await fetch('<?= $fs()->dir ?>', {
				method: 'POST',
				mode: "cors",
				cache: "no-cache",
				credentials: "same-origin",
				referrerPolicy: "no-referrer",
				headers: {
					"X-Requested-With": "fetch",
					"Application-From": "same",
					'Accept': 'application/json',
				},
				body: formData,
			});
			const payload = await response.json();
			this.materialDescription.innerHTML = this.descriptionTemplate(payload.description.id, payload.description.creationDate, payload.description.type, payload.description.category, payload.description.name);
			Object.keys(payload.items).forEach((key) => {
				this.addPartRow(
					{
						"material": {
							"id": payload.items[key].id,
							"name": payload.items[key].longId + ", " + payload.items[key].name
						},
						"quantity": payload.items[key].quantity,
						"unit": {
							"system": payload.items[key].unitSystemId,
							"id": payload.items[key].unitId,
							"name": payload.items[key].unitName,
						}
					}
				);
			});
			this.updateCounters();
		}


		deleteRow(domOwner) {
			domOwner.remove();
			this.updateCounters();
		}

		addPartRow(part) {
			let domElem = document.createElement("main");

			let uri = "_/UnitMeasurment/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_UnitMeasurment.a?unit=" + part.unit.system;
			let unitpre = part.unit.id != 0 ? ` value=${part.unit.name} data-slodefaultid=${part.unit.id}` : '';
			domElem.dataset.partid = part.material.id;
			let html = `
					<div><span></span></div>
					<div class="ellipsis">
						${part.material.name}
					</div>
					<div>
						<input class="number-field" style="width:90px" name="bom_qty[${part.material.id}]" type="text" value="${part.quantity}" inputmode="decimal" min="0" />
						<input class="number-field" style="width:60px;text-align:left" name="bom_unit[${part.material.id}]" data-slo=":LIST" type="text" ${unitpre} data-source="${uri}" />
					</div>
					<div class="control"><button type="button" class="delete"></button></div>
				`;

			domElem.innerHTML = html;
			domElem.querySelectorAll("input").forEach(element => {
				if (element.dataset.slo == ":LIST")
					$(element).slo({});
			});

			domElem.querySelectorAll("button").forEach(element => {
				if (element.classList.contains("delete"))
					element.addEventListener("click", (e) => {
						this.deleteRow(domElem);
					})

			});

			this.materialParts.appendChild(domElem);
			this.updateCounters();
		}

		async post() {
			this.materialParts.querySelectorAll(`main`).forEach(elem => {
				elem.classList.remove("invalid")
			});
			try {
				overlay.show();
				const formData = new FormData(this.submitForm);
				formData.append("method", "save");
				formData.append("matid", this.activeMaterial);

				const response = await fetch('<?= $fs()->dir ?>', {
					method: 'POST',
					mode: "cors",
					cache: "no-cache",
					credentials: "same-origin",
					referrerPolicy: "no-referrer",
					headers: {
						"X-Requested-With": "fetch",
						"Application-From": "same",
						'Accept': 'application/json',
					},
					body: formData,
				});
				const payload = await response.json();
				overlay.hide();
				if (payload.result) {
					messagesys.success("Material Bil of Materials updated successfully");
				} else {
					this.materialParts.querySelectorAll(`main[data-partid]`).forEach(elem => {
						if (payload.highlight.includes(parseInt(elem.dataset.partid))) {
							elem.classList.add("invalid")
						}
					});
					messagesys.failure(payload.message);
				}

			} catch (e) {
				messagesys.failure(e);
			}
		}
	}

	bom = new BillOfMaterial();
</script>