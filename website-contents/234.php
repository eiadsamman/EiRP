<?php
use System\Finance\Invoice\InvoiceItems;
use System\Template\Gremium;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;

$perpage_val = 20;
$id          = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$invoice     = new System\Finance\Invoice\PurchaseRequest($app);
$entry       = new System\Finance\Invoice\InvoiceRecord($app);

try {

	$read = $entry->get($id);
	if (!$read) {
		throw new Exception("Request document not found");
	}

	$tl    = new Timeline($app);
	$query = $tl->query($read->id);
	$query->modules(Module::InvoicingPurchaseQuotation);
	$query->actions(Action::Create);
	$totalPreVat = $read->totalValue * (1 - ($read->discountRate ?? 0) / 100) + ($read->addtionalAmmout ?? 0);
	$grem        = new Gremium\Gremium(false, false);

	$grem->header()->prev("href=\"{$fs(209)->dir}\" data-href=\"{$fs(209)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite>{$app->branding->formatId(System\Finance\Invoice\enums\Purchase::Quotation, $read->serialNumber, "-" . $read->costCenter->id . "-")}</cite>");
	$grem->menu()->sticky(true)->open();
	echo "<span class=\"flex\"></span>";
	echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs()->dir}\" id=\"appPrint\" class=\"edge-right edge-left\" tabindex=\"-1\">Print</button>";
	$grem->getLast()->close();

	if ($query->execute()) {
		$grem->column()->open();
		echo <<<HTML
			<div>
				<h1>History and Feedbacks</h1>
				<div class="links">
					
				</div>	
				{$query->plot()}
			</div>
		HTML;
		$grem->getLast()->close();
	}

	$grem->title()->serve("<span class=\"flex\">Material request information</span>");
	$grem->article()->open(); ?>
	<iframe id="plot-iframe" name="plot-iframe" style="display:block;width:0;height:0px;visibility: hidden"></iframe>
	<div class="form">
		<label>
			<h1>ID</h1>
			<div class="btn-set">
				<span><?= $app->branding->formatId(System\Finance\Invoice\enums\Purchase::Quotation, $read->serialNumber, "-" . $read->costCenter->id . "-") ?></span>
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
			<h1>Purchase Request</h1>
			<div class="btn-set">
				<span><a data-href="<?= "{$fs(240)->dir}/?id={$read->parentId}"; ?>" href="<?= "{$fs(240)->dir}/?id={$read->parentId}"; ?>">
						<?=
							$app->branding->formatId(System\Finance\Invoice\enums\Purchase::Request, $read->parentSerialNumber, "-" . $read->costCenter->id . "-") ?>
					</a></span>
			</div>
			<div class="btn-set">
				<span style="color: var(--root-font-lightcolor);">Title:</span><span><?= $read->title ?></span>
			</div>
		</label>
	</div>



	<div class="form">
		<label>
			<h1>Payment Term</h1>
			<div class="btn-set">
				<span><?= $read->paymentTerm ? $read->paymentTerm->toString() : "-" ?></span>
			</div>
		</label>
		<label>
			<h1>Shipping Term</h1>
			<div class="btn-set">
				<span><?= $read->shippingTerm ? $read->shippingTerm->toString() : "-" ?></span>
			</div>
		</label>
	</div>


	<div class="form">
		<label>
			<h1>Posted by</h1>
			<div class="btn-set">
				<span><?= $read->issuedBy->fullName() ?></span>
			</div>
			<div class="btn-set">
				<span><?= ($read->departement ? "<br />" . $read->departement->name : "") ?></span>
			</div>
		</label>
		<label>
			<h1>Posted on</h1>
			<div class="btn-set">
				<span><?= $read->issuingDate->format("Y-m-d H:s") ?></span>
			</div>
		</label>
	</div>

	<div class="form">
		<label>
			<h1>Quotation Summary</h1>
			<div class="table local03" style="padding:10px 0px">
				<main>
					<span>Currency</span>
					<div><?= " [{$read->currency->shortname}] {$read->currency->name}"; ?></div>
				</main>
				<main>
					<span>Sub Total</span>
					<div><?= number_format($read->totalValue, 2); ?></div>
				</main>
				<main>
					<span>Discount Rate</span>
					<div>
						<?= number_format($read->totalValue * ($read->discountRate / 100), 2) . " (" . number_format($read->discountRate, 2) . "%)"; ?>
					</div>
				</main>
				<main>
					<span>Additional Amount</span>
					<div><?= number_format($read->addtionalAmmout, 2); ?></div>
				</main>
				<main>
					<span>Total</span>
					<div><?= number_format($totalPreVat, 2); ?></div>
				</main>
				<main>
					<span>VAT Amount</span>
					<div>
						<?= number_format($totalPreVat * ($read->costCenter->vatRate / 100), 2) . " (" . number_format($read->costCenter->vatRate, 2) . "%)"; ?>
					</div>
				</main>
				<main>
					<span>Grand Total</span>
					<div>
						<?= number_format($totalPreVat * (1 + $read->costCenter->vatRate / 100), 2); ?>
					</div>
				</main>


			</div>

		</label>

	</div>



	<?php
	$grem->getLast()->close();

	$grem->title()->serve("<span class=\"flex\">Requested materials</span>");
	$grem->article()->open();
	?>

	<div class="table local01">
		<header>
			<div>#</div>
			<div>Product</div>
			<div class="n">Quantity</div>
			<div class="n">Cost</div>
			<div class="n">Inline</div>

		</header>
		<?php
		$items         = new InvoiceItems($app);
		$children      = $items->get($read->id);
		$rowNumber     = 1;
		$showRowNumber = true;

		function parseItem($item, $rowNumber)
		{
			echo "
				<main class=\"" . ($item->relatedItem ? "partsElement" : "") . "\">
					<div>" . ($item->isGroupingItem ? "" : $rowNumber) . "</div>
					<div class=\"ellipsis\">{$item->material->longId}</br >{$item->material->name}</div>
					<div class=\"n\">" . number_format($item->quantity, 2) . "<br />{$item->material->unit->name}</div>
					<div class=\"n\">" . ($item->isGroupingItem ? "" : rtrim(rtrim(number_format($item->value, 5), "0"), ".")) . "</div>
					<div class=\"n\">" . ($item->isGroupingItem ? "" : number_format($item->value * $item->quantity, 2)) . "</div>
				</main>
			";
			return $item->isGroupingItem ? $rowNumber : $rowNumber + 1;
		}
		foreach ($children as $item) {
			$rowNumber = parseItem($item, $rowNumber);
			foreach ($item->subItems as $subItem) {
				$rowNumber = parseItem($subItem, $rowNumber);
			}
		}
		?>
	</div>
	<?php
	$grem->getLast()->close();

	$grem->terminate(true);
	?>
	<style>
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

		.table {
			&.local01 {
				grid-template-columns: 50px minmax(120px, 5fr) 1fr 1fr 1fr;
			}

			&.local03 {
				grid-template-columns: 150px 1fr;
			}

			>header {
				>.n {
					text-align: right;
				}
			}

			>main {
				display: contents;

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
	$grem->header()->prev("href=\"{$fs(209)->dir}\" data-href=\"{$fs(209)->dir}\"")->serve("<h1>{$e->getMessage()}</h1><cite>$id</cite>");
	$grem->title()->serve("<span class=\"small-media-hide\">Couldn't open requested document, verify the following keys and try again</span>");
	$grem->article()->serve(<<<HTML
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
	$grem->header()->prev("href=\"{$fs(209)->dir}\" data-href=\"{$fs(209)->dir}\"")->serve("<h1>Invalid input</h1><cite>$id</cite>");
	$grem->title()->serve("<span class=\"small-media-hide\">Couldn't open requested document, verify the following keys and try again</span>");
	$grem->article()->serve(<<<HTML
			<ul>
				<li>Permissions denied</li>
				<li>Document is locked or out of scope</li>
				<li>Contact system administrator if this problem persists</li>
			</ul>
			HTML
	);
	$grem->terminate();
}
?>