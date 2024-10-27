<?php
use System\Template\Gremium;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;

$perpage_val = 20;
$id          = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$invoice     = new System\Finance\Invoice\MaterialRequest($app);
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

	$grem->header()->prev("href=\"{$fs(210)->dir}\" data-href=\"{$fs(210)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite>{$app->prefixList[100][0]}" . $read->costCenter->id . str_pad($read->serialNumber, $app->prefixList[100][1], "0", STR_PAD_LEFT) . "</cite>");
	$grem->menu()->sticky(false)->open();
	echo "<span class=\"flex\"></span>";
	echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs()->dir}\" id=\"appPrint\" class=\"edge-right edge-left\" tabindex=\"-1\">Print</button>";
	$grem->getLast()->close();

	if ($query->execute()) {
		$grem->column()->open();
		echo <<<HTML
			<div>
				<h1>History and Feedbacks</h1>
				<div class="links">
					<a href="{$fs(230)->dir}" data-href="{$fs(230)->dir}">New material request</a>
					<a href="{$fs(233)->dir}/?id={$read->id}?" data-href="{$fs(233)->dir}/?id={$read->id}?">Submit request quotation</a>
					<a href="">Terminate material request</a>
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
			<div>
				<?= $app->prefixList[100][0], $read->costCenter->id . str_pad($read->serialNumber, $app->prefixList[100][1], "0", STR_PAD_LEFT) ?>
			</div>
		</label>
		<label>
			<h1>Cost Center</h1>
			<div>
				<?= $read->costCenter->name . " (" . number_format($read->costCenter->vatRate, 2) . "%)" ?>
			</div>
		</label>
	</div>
	<div class="form">
		<label>
			<h1>Order Title</h1>
			<div>
				<?= $read->title ?>
			</div>
		</label>


	</div>

	<div class="form">
		<label>
			<h1>Requested by</h1>
			<div>
				<?= $read->issuedBy->fullName() . " / " . ($read->departement ? $read->departement->name : "<span style=\"color: red\">NA</span>") ?>
			</div>
		</label>
		<label>
			<h1>Requested on</h1>
			<div>
				<?= $read->issuingDate->format("Y-m-d H:s") ?>
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
			<div>Part Number</div>
			<div>Item</div>
			<div class="n">Quantity</div>
			<div>Unit</div>
		</header>
		<?php
		$children      = $invoice->items($read->id);
		$rowNumber     = 0;
		$showRowNumber = true;

		foreach ($children as $item) {
			$cssDefinition = "";

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
			echo <<<HTML
				<main class="{$cssDefinition}">
					<div>{$showRowNumber}</div>
					<div>{$item->material->longId}</div>
					<div class="ellipsis">{$item->material->name}</div>
					<div class="n">{$quantity}</div>
					<div>{$item->material->unit->name}</div>
				</main>
			HTML;


		}
		?>
	</div>
	<?php
	$grem->getLast()->close();
	$grem->title()->serve("Request posted quotations");
	$grem->article()->open();
	
	?>
	No quoations placed yet for this material request
	<?php
	$grem->getLast()->close();
	$grem->terminate();
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
?>