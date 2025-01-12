<?php
use System\Finance\Invoice\InvoiceItems;
use System\Finance\Invoice\InvoiceSequence;
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
	$query->modules(Module::InvoicingMaterialRequest, Module::InvoicingPurchaseQuotation);
	$query->actions(Action::Create);

	$grem = new Gremium\Gremium(false, false);



	$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite>{$app->branding->formatId(System\Finance\Invoice\enums\Purchase::Request, $read->serialNumber, "-" . $read->costCenter->id . "-")}</cite>");
	$grem->menu()->sticky(false)->open();
	echo "<a href=\"{$fs(233)->dir}/?id={$read->id}\" data-href=\"{$fs(233)->dir}/?id={$read->id}\" class=\"edge-left edge-right plus\">&nbsp;New Quotation</a>";
	echo "<span class=\"flex\"></span>";
	echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs()->dir}\" id=\"appPrint\" class=\"edge-left edge-right\" tabindex=\"-1\">Print</button>";
	$grem->getLast()->close();

	if ($query->execute()) {
		$grem->column()->open();
		echo <<<HTML
			<div>
				<h1>History and Feedbacks</h1>
				<div class="links">
					<a href="{$fs(230)->dir}" data-href="{$fs(230)->dir}">New Material Request</a>
					<a href="{$fs(233)->dir}/?id={$read->id}" data-href="{$fs(233)->dir}/?id={$read->id}?">New Quotation</a>
					<a href="">Terminate material request</a>
				</div>	
				{$query->plot()}
			</div>
		HTML;
		$grem->getLast()->close();
	}




	$grem->title()->serve("<span class=\"flex\">Request Information</span>");


	$grem->article()->open(); ?>
	<iframe id="plot-iframe" name="plot-iframe" style="display:block;width:0;height:0px;visibility: hidden"></iframe>

	<div class="form">
		<label>
			<h1>ID</h1>
			<div class="btn-set">
				<span><?= $app->branding->formatId(System\Finance\Invoice\enums\Purchase::Request, $read->serialNumber, "-" . $read->costCenter->id . "-") ?></span>
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
			<h1>Order Title</h1>
			<div class="btn-set">
				<span><?= $read->title ?></span>
			</div>
		</label>
	</div>
	<div class="form">
		<label>
			<h1>Requested by</h1>
			<div class="btn-set">
				<span><?= $read->issuedBy->fullName() . " / " . ($read->departement ? $read->departement->name : "<span style=\"color: red\">NA</span>") ?></span>
			</div>
		</label>
		<label>
			<h1>Requested on</h1>
			<div class="btn-set">
				<span><?= $read->issuingDate->format("Y-m-d H:s") ?></span>
			</div>
		</label>
	</div>

	<?php
	$grem->getLast()->close();

	$grem->title()->serve("<span class=\"flex\">Requested Materials</span>");
	$grem->article()->open();
	?>

	<div class="table local01">
		<header>
			<div>#</div>
			<div>Part Number</div>
			<div>Item</div>
			<div class="n">Quantity</div>
			<div>Unit</div>
		</header>
		<?php
		$items         = new InvoiceItems($app);
		$children      = $items->get($read->id);
		$rowNumber     = 1;
		$showRowNumber = true;

		function parseItem($item, $rowNumber)
		{
			$cssDefinition = "";
			if ($item->isGroupingItem) {
				$showRowNumber = false;
				$cssDefinition = "";
			} elseif (!is_null($item->relatedItem)) {
				$showRowNumber = true;
				$cssDefinition = "partsElement";
			} else {
				$showRowNumber = true;
			}
			$showRowNumber = $showRowNumber ? $rowNumber : "";
			$quantity      = number_format($item->quantity, 2);
			echo <<<HTML
				<main class="{$cssDefinition}">
					<div>{$showRowNumber}</div>
					<div>{$item->material->longId}</div>
					<div class="ellipsis">{$item->material->name}</div>
					<div class="n">{$quantity}</div>
					<div>{$item->material->unit->name}</div>
				</main>
			HTML;
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
	$grem->title()->serve("Placed Quotations");
	$grem->article()->open();

	echo <<<HTML
		<div class="table local02">
		<header>
			<div>#</div>
			<div>Value</div>
			<div>Terms</div>
			<div>Posted By</div>
		</header>
	HTML;



	$sequence = new InvoiceSequence($app);
	foreach ($sequence->children($read->id) as $node) {
		$uri_get = "{$fs(234)->dir}/?id={$node->id}&document={$app->branding->formatId(System\Finance\Invoice\enums\Purchase::Quotation, $node->serialNumber, "-" . $read->costCenter->id . "-")}";
		echo "	<a href=\"$uri_get\" data-href=\"$uri_get\">
				<div>" . $app->branding->formatId(System\Finance\Invoice\enums\Purchase::Quotation, $node->serialNumber, "-" . $read->costCenter->id . "-") . "</div>
				<div>" . $node->currency->shortname . " " . number_format($node->totalValue, 2) . "
				<br />" . number_format($node->discountRate, 2) . "%</div>
				<div>" . (empty($node->paymentTerm) ? "-" : $node->paymentTerm->toString()) . "
				<br />" . (empty($node->shippingTerm) ? "-" : $node->shippingTerm->toString()) . "</div>
				<div>{$node->issuedBy->fullName()}<br/>{$node->issuingDate->format("Y-m-d")}<br />{$node->issuingDate->format("H:i")}</div>
			</a>
		";
	}
	echo <<<HTML
		</div>
	HTML;

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
				grid-template-columns: 40px minmax(130px, 1fr) minmax(10px, 3fr) 1fr 60px;
			}

			&.local02 {
				grid-template-columns: 120px 1fr 1fr 1fr;
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
	$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>{$e->getMessage()}</h1><cite>$id</cite>");
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
	$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>Invalid input</h1><cite>$id</cite>");
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