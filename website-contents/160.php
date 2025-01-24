<?php
use System\Models\Material;
use System\Template\Gremium;


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

	if (is_array($_POST['bom_material']) && sizeof($_POST['bom_material']) > 0) {
		$material_id       = 0;
		$material_part_id  = 0;
		$material_quantity = 0;
		$rbool             = true;


		$app->db->autocommit(false);

		$rbool &= $app->db->query("DELETE FROM mat_bom WHERE mat_bom_mat_id={$matid};");
		$stmt  = $app->db->prepare("INSERT INTO mat_bom (mat_bom_mat_id, mat_bom_part_id, mat_bom_quantity, mat_bom_level) VALUES (?,?,?,1);");
		$stmt->bind_param("iid", $material_id, $material_part_id, $material_quantity);
		foreach ($_POST['bom_material'] as $lvl1_k => $lvl1_v) {
			if (isset($lvl1_v[1], $_POST['bom_quantity'][$lvl1_k]) && (int) $lvl1_v[1] > 0) {
				/* Set the material ID in the BOM table to `negative` to exclude it from integrity check */
				$material_id       = -((int) $matid);
				$material_part_id  = (int) $lvl1_v[1];
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
	$id              = (int) $_POST['id'];
	$defaultSystemId = $app->unit->defaultUnit((int) $id);
	if ($defaultSystemId) {
		$default = $app->unit->getUnit((int) $id, $defaultSystemId);
	}
	echo json_encode([
		"id" => (int) $id,
		"name" => \System\enums\UnitSystem::tryFrom((int) $id)->toString(),
		"default_id" => $defaultSystemId ? $app->unit->defaultUnit((int) $id) : 0,
		"default_symbol" => $defaultSystemId ? $default->symbol : "",
	]);
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
			grid-template-columns: minmax(50px, auto) minmax(130px, 1fr) 180px 60px;
		}

		.input-qunatity {
			text-align: right;
			width: 100px;
		}
	}
</style>

<?php
$grem = new Gremium\Gremium();
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
$grem->title()->serve("Bill of materials");
$grem->legend()->serve("<span class=\"flex\"></span><button type=\"button\" class=\"edge-left\" disabled id=\"part-add-button\">Add material</button>");
$grem->article()->open();
echo <<<HTML
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

$grem->terminate();
?>
<script>
	class BillOfMaterial {
		constructor() {
			this.activeMaterial = 0;
			this.submitButton = null;
			this.submitForm = null;
			this.partAddButton = null;
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
				this.submitButton = document.getElementById("save-button");
				this.submitForm = document.getElementById("material-list-form");
				this.partAddButton = document.getElementById("part-add-button");
				this.materialDescription = document.getElementById("material-description");
				this.materialSelectButton = document.getElementById("material-select-button");
				this.materialParts = document.getElementById("material-parts");
				this.submitButton.addEventListener("click", () => {
					this.post();
				});
				this.submitForm.addEventListener("submit", (e) => {
					e.preventDefault();
					this.post();
					return;
				});
				this.partAddButton.addEventListener("click", () => {
					this.addPartRow();
				});
				$(this.materialSelectButton).slo({
					onselect: (selected_bom) => {
						this.activeMaterial = selected_bom.key
						this.submitButton.disabled = false;
						this.partAddButton.disabled = false;
						this.loadMaterial();
					},
					ondeselect: () => {
						this.activeMaterial = 0;
						this.materialDescription.innerHTML = this.descriptionTemplate();
						this.clearForms();
						this.partAddButton.disabled = true;
						this.submitButton.disabled = true;
					}
				});
			});
		}

		updateCounters() {
			let counter = 1;
			this.materialParts.querySelectorAll("main").forEach((e) => {
				e.querySelector("div>span").innerHTML = `<div class="btn-set"><span>${counter}</span></div>`;
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

		async getUnit(unitSystem, domOwner) {
			const formData = new FormData(this.submitForm);
			formData.append("method", "getunit");
			formData.append("id", unitSystem);

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

			let uri = "_/UnitMeasurment/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_UnitMeasurment.a?unit=" + payload.id;

			let unitInputHtml = `
					<input type="number" class="input-qunatity" name="quantity[]" value="0" />
					<input type="text" style="width: 60px" class="mat-unit" name=\"unit[]\"
						data-source="${uri}" ` + (payload.default_id != 0 ? ` value="${payload.default_symbol}" data-slodefaultid="${payload.default_id}" ` : ``) + `
						data-slo=":LIST" />`;
			let dom = domOwner.querySelector(".qty-unit-container");
			if (dom != undefined) {
				dom.innerHTML = unitInputHtml;
				dom.querySelectorAll("input").forEach(element => {
					if (element.dataset.slo !== undefined)
						$(element).slo();
				})
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
			console.log(payload)
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
							"id": payload.items[key].unitSystemId,
							"name": payload.items[key].unitName,
						}
					}
				);
			});
			this.addPartRow();
			this.updateCounters();
		}


		deleteRow(domOwner) {
			domOwner.remove();
			this.updateCounters();
		}

		addPartRow(part = null) {
			let emptyRow = false;
			let uri = "";
			let html = "";
			let domElem = document.createElement("main");

			if (part == null) {
				emptyRow = true;
				html = `
					<div><span></span></div>
					<div><div class="btn-set"><input type="text" class="flex materialselection" name="material[]" data-slo="BOM" /></div></div>
					<div><div class="btn-set qty-unit-container"></div></div>
					<div class="control"><button type="button" class="delete"></button></div>`;
			} else {
				uri = "_/UnitMeasurment/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_UnitMeasurment.a?unit=" + part.unit.id;

				html = `
					<div><span></span></div>
					<div>
						<div class="btn-set">
							<input type="text" class="flex materialselection" value="${part.material.name}" data-slodefaultid="${part.material.id}" name="material[]" data-slo="BOM" />
						</div>
					</div>
					<div>
						<div class="btn-set qty-unit-container">		
							<input type="number" class="input-qunatity" name="quantity[]" value="${part.quantity}" />
							<input type="text" style="width: 60px" class="mat-unit" name=\"unit[]\" data-source="${uri}" value="${part.unit.name}" data-slodefaultid="${part.unit.id}" data-slo=":LIST" />
						</div>
					</div>
					<div class="control"><button type="button" class="delete"></button></div>
				`;
			}

			domElem.innerHTML = html;
			domElem.querySelectorAll("input").forEach(element => {
				if (element.dataset.slo == "BOM")
					$(element).slo({
						onselect: (data) => { this.getUnit(data.embeds.params.unitsystem, domElem); },
						ondeselect: () => { this.clearUnit(domElem); }
					});
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
		}
	}

	bom = new BillOfMaterial();
</script>