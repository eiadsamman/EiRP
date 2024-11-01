<?php
use System\Finance\CostCenter;
use System\Finance\Invoice\Item;
use System\Finance\Invoice\MaterialRequest;
use System\Profiles\MaterialProfile;
use System\Template\Gremium;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;


const SALT = "#F@*G3HP#1{F@4*H(";

if ($app->xhttp) {

	if (isset($_POST['method']) && $_POST['method'] == "post") {
		header("Content-Type: application/json; charset=utf-8");
		$result  = [];
		$invoice = new MaterialRequest($app);
		//echo json_encode($_POST);exit;
		try {

			if (hash('SHA512', SALT . ($_POST['docId'] ?? 0) . SALT) != $_POST['docHash']) {
				throw new Exception("Invalid document reference!", ($fs()->id * 1000) + 1);
			}
			$read = $invoice->read((int) $_POST['docId']);
			if (!$read) {
				throw new Exception("Invalid document reference!", ($fs()->id * 1000) + 2);
			}

			if (empty($_POST['vendor']) || (int) $_POST['vendor'] <= 0)
				throw new Exception("Invalid quotation vendor", ($fs()->id * 1000) + 3);

			if (empty($_POST['currency']) || (int) $_POST['currency'] <= 0)
				throw new Exception("Invalid quotation currency", ($fs()->id * 1000) + 4);

			$requestItems = [];
			foreach ($invoice->items($read->id) as $item) {
				if (!empty($_POST['inv_material'][$item->id]) && (float) $_POST['inv_material'][$item->id] > 0) {
					$requestItems[$item->id]        = $item;
					$requestItems[$item->id]->value = (float) $_POST['inv_material'][$item->id];
				} else {
					$requestItems[$item->id] = null;
				}
			}

			foreach ($requestItems as $item) {
				if (is_null($item)) {
					throw new Exception("One or more material cost is invalid", ($fs()->id * 1000) + 5);
				}
			}

			if (empty($_POST['paymentTerm']) || (int) $_POST['paymentTerm'] <= 0)
				throw new Exception("Invalid payment term", ($fs()->id * 1000) + 6);
			if (empty($_POST['shippingTerm']) || (int) $_POST['shippingTerm'] <= 0)
				throw new Exception("Invalid shipping term", ($fs()->id * 1000) + 7);



			$invoice->discountRate((float) $_POST['discount']);
			$invoice->addtionalAmmout((float) $_POST['additionalAmount']);

			$invoice->client((int) $_POST['vendor']);
			$invoice->curreny((int) $_POST['currency']);
			$invoice->title($read->title);
			$invoice->comments($_POST['comments'] ?? "");
			$invoice->costCenter($read->costCenter->id);
			$invoice->departement($read->departement);
			$invoice->shippingTerm((int) $_POST['shippingTerm']);
			$invoice->paymentTerm((int) $_POST['paymentTerm']);

			$result["item"] = $requestItems;
			//$insert_id = $invoice->post();

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


	$id      = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
	$invoice = new System\Finance\Invoice\MaterialRequest($app);
	try {
		$read = $invoice->read($id);
		if (!$read) {
			throw new Exception("Request document not found");
		}

		$tl    = new Timeline($app);
		$query = $tl->query($read->id);
		$query->modules(Module::InvoicingMaterialRequest);
		$query->actions(Action::Create);

		$grem = new Gremium\Gremium(false, false);

		$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>{$fs()->title}</h1>
			<cite></cite><div class=\"btn-set\"><button class=\"plus\" id=\"appApplicationPost\" tabindex=\"9\">&nbsp;Submit Request</button></div>");
		if ($query->execute()) {
			$grem->column()->open();
			echo <<<HTML
			<div>
				<h1>History and Feedbacks</h1>
				<div class="links">
					<a href="">Terminate material request</a>
				</div>	
				{$query->plot()}
			</div>
		HTML;
			$grem->getLast()->close();
		}

		$grem->title()->serve("<span class=\"flex\">Material request information</span>");


		$grem->article()->open(); ?>
		<form action="<?= $fs()->dir; ?>">
			<fieldset>
				<div class="form">
					<label>
						<h1>Purchase Request</h1>
						<div class="btn-set">

							<?php
							echo "<a class=\"standard\" href=\"{$fs(240)->dir}/?id={$read->id}\" data-href=\"{$fs(240)->dir}/?id={$read->id}\">{$app->prefixList[100][0]}" . $read->costCenter->id . str_pad($read->serialNumber, $app->prefixList[100][1], "0", STR_PAD_LEFT) . "</a>";
							?>
						</div>
					</label>
					<label>
						<h1>Cost Center</h1>
						<div class="btn-set">
							<span><?= $read->costCenter->name . " (" . number_format($read->costCenter->vatRate, 2) . "%)" ?></span>
						</div>
					</label>
				</div>
				<div class="form">
					<label>
						<h1>Project title</h1>
						<div class="btn-set">
							<span><?= $read->title ?></span>
						</div>
					</label>
					<label>
						<h1>Departement</h1>
						<div class="btn-set">
							<span><?= $read->departement->name ?></span>
						</div>
					</label>
				</div>
				<div class="form">
					<label>
						<h1>Issued by</h1>
						<div class="btn-set">
							<span><?= $read->issuedBy->fullName() ?></span>
						</div>
					</label>
					<label>
						<h1>Issuing date</h1>
						<div class="btn-set">
							<span><?= $read->issuingDate->format("Y-m-d H:s") ?></span>
						</div>
					</label>
				</div>
			</fieldset>
		</form>
		<?php
		$grem->getLast()->close();

		$grem->title()->serve("<span class=\"flex\">Quotation offer details</span>");
		$grem->article()->open();
		?>
		<form action="<?= $fs()->dir; ?>">
			<fieldset>
				<input type="hidden" name="docId" id="docId" value="<?= $read->id ?>" />
				<input type="hidden" name="docHash" id="docHash" value="<?= hash('SHA512', SALT . $read->id . SALT) ?>" />
				<div class="form">
					<label for="">
						<h1>Vendor</h1>
						<div class="btn-set">
							<input name="vendor" id="vendor" type="text" placeholder="Select vendor..." class="flex" title="Vendor seletion" data-slo=":LIST"
								   data-source="_/CompaniesList/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_CompaniesList.a" />
						</div>
						<div id="vendorTicket" for="">
						</div>
					</label>

					<label>
						<h1>Contact person</h1>
						<div class="btn-set">
							<input name="attention" id="attention" type="text" placeholder="Reference contact" class="flex" title="Reference contact"
								   data-slo=":LIST" data-source="_/UserList/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_userList.a" />
						</div>
					</label>
				</div>

				<div class="form">
					<label>
						<h1>currency</h1>
						<div class="btn-set">
							<input name="currency" id="currency" type="text" placeholder="Reference contact" class="flex" title="Reference contact"
								   data-slo=":LIST"
								   data-source="_/FinanceCurrencyList/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_currencyList.a" />
						</div>
					</label>
					<label>
						<h1></h1>
						<div class="btn-set">
						</div>
					</label>
				</div>
			</fieldset>
		</form>
		<?php
		$grem->getLast()->close();
		$grem->title()->serve("<span class=\"flex\">Requested materials</span>");
		$grem->article()->open();
		?>

		<form action="<?= $fs()->dir; ?>" id="formMaterialsList" name="formMaterialsList">
			<fieldset>
				<div class="table inv233">
					<header>
						<div>#</div>
						<div>Part Number</div>
						<div class="n">Quantity</div>
						<div>Cost</div>
					</header>
					<?php
					$children      = $invoice->items($read->id);
					$rowNumber     = 0;
					$showRowNumber = true;
					foreach ($children as $item) {
						$cssDefinition = "";
						$inputField    = "";
						if ($item->isGroupingItem) {
							$showRowNumber = false;
							$cssDefinition = "";
						} elseif (!is_null($item->relatedItem)) {
							$showRowNumber = true;
							$rowNumber++;
							$cssDefinition = "partsElement";
						} else {
							$showRowNumber = true;
							$rowNumber++;
						}
						$showRowNumber = $showRowNumber ? $rowNumber : "";
						$quantity      = number_format($item->quantity, 2);
						$inputField    = $showRowNumber ? "<input class=\"numberField itemValue\" name=\"inv_material[{$item->id}]\" data-quantity=\"{$item->quantity}\" type=\"text\" value=\"0.00\" inputmode=\"decimal\" min=\"0\" />" : "";
						echo <<<HTML
						<main class="{$cssDefinition}">
							<div>{$showRowNumber}</div>
							<div class="ellipsis">{$item->material->longId}<br />{$item->material->name}</div>
							<div class="n">{$quantity}<br />{$item->material->unit->name}</div>
							<div>{$inputField}</div>
						</main>
					HTML;
					}
					?>
					<footer>
						<div></div>
						<div class="a ellipsis">Subtotal</div>
						<div><input type="text" id="appSubtotal" inputmode="decimal" disabled value="0.00" class="numberField" /></div>
					</footer>
					<footer>
						<div></div>
						<div class="a ellipsis">Discount Rate (%)</div>
						<div><input type="number" id="appDiscount" name="discount" inputmode="decimal" pattern="[0-9.]*" min="0" max="100" value="0.00"
								   class="numberField" />
						</div>
					</footer>

					<footer>
						<div></div>
						<div class="a ellipsis">Additional amount</div>
						<div><input type="number" id="appAdditionalAmount" name="additionalAmount" inputmode="decimal" value="0.00" class="numberField" />
						</div>
					</footer>

					<footer>
						<div></div>
						<div class="a ellipsis">Total</div>
						<div><input type="text" id="appTotal" inputmode="decimal" disabled value="0.00" class="numberField" /></div>
					</footer>

					<footer>
						<div></div>
						<div class="a ellipsis">VAT Rate (%)</div>
						<div><input type="text" id="appVat" inputmode="decimal" data-value="<?= $read->costCenter->vatRate ?? 0; ?>" disabled
								   value="<?= number_format($read->costCenter->vatRate ?? 0, 2) ?>" class="numberField" /></div>
					</footer>

					<footer>
						<div></div>
						<div class="a ellipsis">Grand Total</div>
						<div><input type="text" id="appGrand" inputmode="decimal" disabled value="0.00" class="numberField" /></div>
					</footer>

				</div>
			</fieldset>
		</form>
		<br /><br />
		<h1>Addtional information</h1>
		<div class="form">
			<label>
				<h1>Payment terms</h1>
				<div class="btn-set">
					<input name="paymentTerm" id="paymentTerm" type="text" placeholder="Payment Term" class="flex" title="Payment Term" data-slo=":LIST"
						   data-source="_/PaymentTerms/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_paymentTerm.a" />
				</div>
			</label>
			<label>
				<h1>Shipping terms</h1>
				<div class="btn-set">
					<input name="ShippingTerm" id="ShippingTerm" type="text" placeholder="Shipping Term" class="flex" title="Shipping Term" data-slo=":LIST"
						   data-source="_/ShippingTerms/slo/<?= md5($app->id . $app->user->company->id); ?>/slo_shippingTerm.a" />
				</div>
			</label>
		</div>
		<div class="form">
			<label>
				<h1>Remarks and comments</h1>
				<div class="btn-set">
					<textarea class="flex" name="comments" style="height:100px;"></textarea>
				</div>
			</label>
		</div>

		<div style="height: 1vh;"></div>

		<?php
		$grem->getLast()->close();
		$grem->terminate();
		?>

		<style>
			#vendorTicket {
				padding: 0px 10px;

				>div {
					padding: 8px 0px;

					>span:first-child {
						display: inline-block;
						min-width: 130px;
						color: var(--root-font-lightcolor);
						padding-right: 10px;
					}
				}
			}


			.links {
				padding-bottom: 20px;

				>a {
					display: block;
					padding: 12px 10px;
					font-size: 1em;
					position: relative;

					&::before {
						content: "-";
						position: absolute;
						left: 0px;
					}
				}
			}


			.table.inv233 {
				grid-template-columns: 40px minmax(80px, 3fr) 1fr minmax(100px, 150px);

				input.numberField {
					-moz-appearance: textfield;
					-webkit-appearance: textfield;
					appearance: textfield;

					text-align: right;
					border: none;
					color: var(--root-link-color);
					margin: 0;
					padding: 9px;
					border-radius: var(--input_border-radius);
					border: solid 1px var(--input_border-color);
					width: 100%;
					color: var(--root-font-color);
					font-size: 1em;

					&:hover {
						border-color: var(--input-hover_border-color);
						z-index: 12;
					}

					&:focus {
						border-color: var(--input-active_border-color);
						z-index: 13;
					}

					&::-webkit-outer-spin-button,
					&::-webkit-inner-spin-button {
						-webkit-appearance: none;
						margin: 0;
					}
				}

				>header {
					>.n {
						text-align: right;
					}
				}

				>footer {

					>div:nth-child(2) {
						grid-column: 2/4;
					}

					&:first-of-type>div {
						border-top: solid 2px var(--input_border-color);
					}

					>div.a {
						text-transform: uppercase;
						color: var(--root-font-lightcolor);
						cursor: default;
						text-align: right;
						padding: 18px 10px;
						font-size: 0.9em;
					}

					>div.n {
						text-align: right;
					}

				}

				>main {
					>.n {
						text-align: right;
					}

					&.partsElement>div {
						background-color: var(--slo-menu-itemhover-background-color);
					}
				}
			}
		</style>

		<?php
	} catch (Exception $e) {
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>{$e->getMessage()}</h1><cite>$id</cite>");
		$grem->title()->serve("<span class=\"small-media-hide\">Couldn't open requested document, verify the following keys and try again</span>");
		$grem->article()->serve(
			<<<HTML
			<ul>
				<li>Permissions denied</li>
				<li>Document is locked or out of scope</li>
				<li>Contact system administrator if this problem persists</li>
			</ul>
			HTML
		);
		$grem->terminate();
	} catch (TypeError $e) {
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>Invalid input</h1><cite>$id</cite>");
		$grem->title()->serve("<span class=\"small-media-hide\">Couldn't open requested document, verify the following keys and try again</span>");
		$grem->article()->serve(
			<<<HTML
			<ul>
				<li>Permissions denied</li>
				<li>Document is locked or out of scope</li>
				<li>Contact system administrator if this problem persists</li>
			</ul>
			HTML
		);
		$grem->terminate();
	}
}
?>