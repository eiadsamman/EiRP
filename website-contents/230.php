<?php
use System\Controller\Finance\CostCenter;
use System\Controller\Finance\Invoice\PurchaseRequest;
use System\Models\Material;
use System\Profiles\MaterialProfile;
use System\Layout\Gremium\Gremium;
use System\Controller\Timeline\Action;
use System\Controller\Timeline\Module;
use System\Controller\Timeline\Timeline;
use System\Unit;

const ERROR_ROOT = 230000;
if ($app->xhttp) {

	if (isset($_POST['method']) && $_POST['method'] == "post") {
		//header("Content-Type: application/json; charset=utf-8");


		$invoice = new PurchaseRequest($app);
		try {
			$invoice->comments($_POST['comments']);
			$invoice->title($_POST['title']);


			if ((int) $_POST['departement'] > 0) {
				$invoice->departement((int) $_POST['departement']);
			} else {
				throw new Exception("Select requesting departement", ERROR_ROOT + 110);
			}

			if (isset($_POST['costcenter']) && (int) $_POST['costcenter'] > 0) {
				$invoice->costCenter((int) $_POST['costcenter']);
			} else {
				$costCenter        = new CostCenter($app);
				$defaultCostCenter = $costCenter->getSystemDefault();
				if (!$defaultCostCenter) {
					throw new Exception("Posting request failed, system financial failure", ERROR_ROOT + 100);
				}
				$invoice->costCenter($defaultCostCenter->id);
			}

			$invoice->curreny(null);
			$materialServicies = [];
			//$app->errorHandler->customError(print_r($_POST,true));

			foreach ($_POST['material']['id'] as $index => $materialId) {
				$item                 = new \System\Controller\Finance\Invoice\structs\InvoiceItem();
				$item->isGroupingItem = $_POST['material']['extract'][$index] && $_POST['material']['extract'][$index] == "1" ? true : false;


				$item->material          = (new Material($app))->load((int) $materialId);
				$item->quantity          = (float) $_POST['material']['qty'][$index];
				$item->unit              = $app->unit->getUnit($item->material->unitSystem->value, (int) $_POST['material']['unit'][$index]) ?? null;
				$item->quantityDelivered = null;

				//Load material BOM and assign parent quantities for each part
				if ($item->isGroupingItem) {
					/** @var \System\Profiles\MaterialPartProfile $material */
					foreach ((new Material($app))->parts($item->material->id) as $material) {
						$subItem                    = new \System\Controller\Finance\Invoice\structs\InvoiceItem();
						$subItem->material          = $material;
						$subItem->quantity          = $item->quantity * $material->bomPortion;
						$subItem->quantityDelivered = $item->quantity * $material->bomPortion;
						$subItem->unit              = $material->unit;
						$item->subItems[]           = $subItem;
					}
				}
				$invoice->appendItem($item);
			}

			$insert_id = $invoice->post();
			$tl        = new Timeline($app);
			$tl->register(module: Module::InvoicingMaterialRequest, action: Action::Create, owner: $insert_id);
			$result = array(
				"result" => true,
				"insert_id" => $insert_id,
				"forward" => $fs(240)->dir,
			);
		} catch (Exception $e) {
			$result = array(
				"result" => false,
				"errno" => $e->getCode(),
				"error" => $e->getMessage(),
			);
		}

		echo json_encode($result);
		exit;
	}

	if (isset($_POST['method'], $_POST['id'], $_POST['qty']) && $_POST['method'] == "displaybom") {
		$material = new Material($app);
		$id       = (int) $_POST['id'];
		$qty      = (float) $_POST['qty'];
		$mat      = $material->load($id);
		if (false !== $mat) {
			$defaultUnit = "";
			if ($unitDefault = $app->unit->defaultUnit((int) $mat->unitSystem->value)) {
				if ($unitProfile = $app->unit->getUnit((int) $mat->unitSystem->value, $unitDefault)) {
					$defaultUnit = " data-unitdefaultid=\"{$unitProfile->id}\" data-unitdefaultsymbol=\"{$unitProfile->symbol}\" ";
				}
			}
			echo <<<HTML
				<div class="mainMaterial" style="display: none;"
					data-id		= "{$mat->id}"
					data-longid	= "{$mat->longId}"
					data-name	= "{$mat->name}"
					data-unit	= "{$mat->unitSystem->name}"
					data-type	= "{$mat->category->name}"
					data-qty	= "{$qty}"
					data-unitsystemid = "{$mat->unitSystem->value}"
					$defaultUnit
				></div>
				<h1>Requested material is part of a manufacturing scheme<br /><br />How do you want to import it?</h1>
				<div class="btn-set"><label style="white-space: wrap"><input type="radio" tabindex="20" name="importStyle" value="1" checked />Import requested material as it is.</label></div>
				<div class="btn-set"><label style="white-space: wrap"><input type="radio" tabindex="21" name="importStyle" value="2" />Import requested material parts separately.</label></div>
				<div class="btn-set"><label style="white-space: wrap"><input type="radio" tabindex="22" name="importStyle" value="3" />Import requested material parts bounded to the their parent.</label></div>
				<br /><h1>Material parts and components (BOM):</h1>
			HTML;

			echo "<table><tbody>";
			/** @var \System\Profiles\MaterialPartProfile $mat */
			foreach ($material->parts($id) as $mat) {
				echo "<tr>";
				echo "<td class=\"subMaterial\"
					data-id		 = \"{$mat->id}\"
					data-longid	 = \"{$mat->longId}\"
					data-name	 = \"{$mat->name}\"
					data-unit	 = \"{$mat->unit->name}\"
					data-type	 = \"{$mat->category->name}\"
					data-portion = \"{$mat->bomPortion}\"
					data-unitsystemid = \"{$mat->unitSystem->value}\"
					data-unitdefaultid = \"{$mat->unit->id}\"
					data-unitdefaultsymbol = \"{$mat->unit->symbol}\"

				><div>{$mat->longId}</div><span>{$mat->category->group->name}: {$mat->category->name}</span><div>{$mat->name}</div></td>";
				echo "</tr>";
			}
			echo "</tbody></table>";
		}
		echo <<<HTML
			<div style="margin-top:15px;" class="btn-set right">
				<button type="button" data-role="previous" tabindex="23" class="standard">Cancel</button>
				<button type="submit" tabindex="24">Import materials</button>
			</div>
		HTML;
		exit;
	}

	if (isset($_POST['method']) && $_POST['method'] == "checkitem") {
		header("Content-Type: application/json; charset=utf-8");
		$id       = (int) $_POST['materialSelection'][1];
		$qty      = (float) str_replace(",", "", $_POST['materialQuantity']);
		$output   = ["result" => 0];
		$material = new Material($app);
		$app->errorHandler->customError($id);
		$mat = $material->load($id);

		if ($mat) {
			if ($mat->subMaterialsCount == 0) {
				$output['result']            = 2;
				$output['id']                = $mat->id;
				$output['longId']            = $mat->longId;
				$output['name']              = $mat->name;
				$output['unitsystemid']      = $mat->unitSystem->value;
				$output['unitdefaultid']     = 0;
				$output['unitdefaultsymbol'] = "";
				$output['type']              = $mat->category->group->name . ": " . $mat->category->name;
				$output['qty']               = $qty;


				if ($unitDefault = $app->unit->defaultUnit((int) $mat->unitSystem->value)) {
					if ($unitProfile = $app->unit->getUnit((int) $mat->unitSystem->value, $unitDefault)) {
						$output['unitdefaultid']     = $unitProfile->id;
						$output['unitdefaultsymbol'] = $unitProfile->symbol;
					}
				}

			} else {
				$output['result'] = 1;
			}
		}
		echo json_encode($output);
		exit;
	}

	$displayCostcenterSelection = false;

	$rCostcenter = $app->db->execute_query("SELECT usrccc_ccc_id FROM user_costcenter WHERE usrccc_usr_id = ?", [$app->user->info->id]);
	if ($rCostcenter && $rCostcenter->num_rows > 1) {
		$displayCostcenterSelection = true;
	}
	unset($rCostcenter);


	$grem = new Gremium(true);
	$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\"><button class=\"plus\" id=\"appApplicationPost\" tabindex=\"9\">&nbsp;Submit Request</button></div>");
	$grem->title()->serve("Request information");
	$grem->article()->open();
	$hash = md5($app->id . $app->user->company->id);

	?>
	<form id="jQpostFormDetails">
		<input type="hidden" name="posubmit">
		<div class="form">
			<label>
				<h1>Requested by</h1>
				<div class="btn-set">
					<span><?= $app->user->company->name ?></span>
					<input name="po_departement" id="po_departement" title="Departement" type="text" data-slo=":LIST" class="flex"
						   data-source="_/CompanyAssosiatedAccounts/slo/{$hash}/slo_CompanyAssosiatedAccounts.a"
						   default="<?= $app->user->account->id; ?>" />
				</div>
			</label>
			<?php if ($displayCostcenterSelection) { ?>
				<label>
					<h1>Cost center</h1>
					<div class="btn-set">
						<input name="po_costcenter" placeholder="Cost Center" data-slo="COSTCENTER_USER" id="formCostcenter" type="text" />
					</div>
				</label>
			<?php } else { ?>
				<label>
					<div>
					</div>
				</label>
			<?php } ?>
		</div>
		<div class="form">
			<label>
				<h1>Request Title</h1>
				<div class="btn-set">
					<input name="po_title" autofocus value="" class="flex" type="text" />
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
					<textarea style="height:100px" class="flex" name="po_comments"></textarea>
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
		<div class="table local230" id="materialsList" action="<?= $fs()->dir ?>">
			<header>
				<div>#</div>
				<div>Quantity</div>
				<div>Part Number</div>
				<div></div>
			</header>
		</div>
	</form>

	<?php

	$grem->getLast()->close();
	$grem->terminate(true);


	exit;
}
?>