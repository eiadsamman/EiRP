<?php
use System\Template\Gremium;

$perpage_val = 20;
$id          = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$statement   = new System\Finance\Transaction\Statement($app);
$read        = $statement->read($id ?? 0);

if ($read) {
	$grem = new Gremium\Gremium(true);
	if (empty($read->debitor) && empty($read->creditor)) {
		$grem->header()->status(Gremium\Status::Exclamation)->serve("<h1>{$fs()->title}</h1><cite>" . ($read ? $read->id : "") . "</cite>");
		$grem->menu()->serve("<span class=\"small-media-hide\">Requested document is forbidden</span>");
		$grem->article()->serve(
			<<<HTML
				<ul>
					<li>Permission denied or not enough privileges to proceed with this document</li>
					<li>Document is locked or out of scope</li>
					<li>Your account doesn't have premissions for neither `Creditor` and `Debitor` accounts</li>
					<li>Contact system administrator for further assistance</li>
				</ul>
				HTML
		);
		unset($grem);
	} else {
		//$fs(214)->dir
		$grem->header()->serve("<h1>{$fs()->title}</h1><cite>{$app->prefixList[13][0]}" . str_pad($read->id, $app->prefixList[13][1], "0", STR_PAD_LEFT) . "</cite>");
		$grem->menu()->open();
		echo "<span class=\"small-media-hide flex\"></span>";
		if ($fs(101)->permission->edit) {
			echo "<input type=\"button\" data-targettitle=\"{$fs(101)->title}\" data-href=\"{$fs(101)->dir}\" data-targetid=\"{$read->id}\" id=\"js-input_edit\" value=\"Edit\" class=\"edge-left\" tabindex=\"-1\" />";
			echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs(142)->dir}\" id=\"js-input_print\" class=\"edge-right\" tabindex=\"-1\">Print</button>";
		} else {
			echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs(142)->dir}\" id=\"js-input_print\" class=\"edge-right edge-left\" tabindex=\"-1\">Print</button>";
		}
		$grem->getLast()->close();
		$grem->title()->serve("<span class=\"flex\">Statement details</span>");
		$grem->article()->open(); ?>
		<iframe id="plot-iframe" name="plot-iframe" style="display:block;width:0;height:0px;visibility: hidden"></iframe>
		<div class="form predefined">
			<label>
				<h1>Statement ID</h1>
				<div class="btn-set">
					<span>
						<?= $app->prefixList[13][0] . str_pad($read->id, $app->prefixList[13][1], "0", STR_PAD_LEFT); ?>
					</span>
				</div>
			</label>
			<label>
				<h1>Post Date</h1>
				<div class="btn-set">
					<span>
						<?= $read->dateTime->format("Y-m-d"); ?>
					</span>
				</div>
			</label>
		</div>
		<div class="form predefined">
			<label>
				<h1>Value</h1>
				<div class="btn-set">
					<span>
						<?= $read->currency->shortname . " " . number_format($read->value, 2); ?>
					</span>
				</div>
			</label>
			<label>
				<h1>Type</h1>
				<div class="btn-set">
					<span>
						<?= $read->type->name; ?>
					</span>
				</div>
			</label>
		</div>
		<div class="form predefined">
			<label>
				<h1>Beneficiary</h1>
				<div class="btn-set">
					<span>
						<?= $read->beneficiary; ?>
					</span>
				</div>
			</label>
		</div>
		<div class="form predefined">
			<label>
				<h1>Creditor</h1>
				<div class="btn-set">
					<span <?= ($app->user->account && $read->creditor && $app->user->account->id != $read->creditor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
						<?= ($read->creditor ? "[" . $read->creditor->currency->shortname . "] " . $read->creditor->company->name . ": " . $read->creditor->name : "-"); ?>
					</span>
				</div>
				<?php if ($read->creditor && $read->debitor && $read->creditor->currency->id != $read->debitor->currency->id) { ?>
					<div class="btn-set">
						<span <?= ($app->user->account && $read->creditor && $app->user->account->id != $read->creditor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
							<?= $read->creditor->currency->shortname . " " . ($read->creditor ? number_format($read->creditAmount, 2) : "-"); ?>
						</span>
					</div>
				<?php } ?>
			</label>
			<label>
				<h1>Debitor</h1>
				<div class="btn-set">
					<span <?= ($app->user->account && $read->debitor && $app->user->account->id != $read->debitor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
						<?= ($read->debitor ? "[" . $read->debitor->currency->shortname . "] " . $read->debitor->company->name . ": " . $read->debitor->name : "-"); ?>
					</span>
				</div>
				<?php if ($read->creditor && $read->debitor && $read->creditor->currency->id != $read->debitor->currency->id) { ?>
					<div class="btn-set">
						<span <?= ($app->user->account && $read->debitor && $app->user->account->id != $read->debitor->id ? "style=\"color: var(--root-font-lightcolor)\"" : "") ?>>
							<?= $read->debitor->currency->shortname . " " . ($read->debitor ? number_format($read->debitAmount, 2) : "-"); ?>
						</span>
					</div>
				<?php } ?>
			</label>
		</div>
		<?php if (sizeof($read->attachments) > 0) { ?>
			<div class="form predefined">
				<label>
					<h1>Attachments</h1>
					<div style="padding:5px 10px;" class="attachments-view" id="transAttachementsList">
						<?php
						foreach ($read->attachments as $file) {
							echo "<a title=\"{$file->name}\" href=\"{$fs(187)->dir}?id={$file->id}&pr=v\" ><img src=\"{$fs(187)->dir}?id={$file->id}&pr=t\" /></a>";
						}
						?>
					</div>
				</label>
			</div>
		<?php } ?>
		<div class="form predefined">
			<label>
				<h1>Description</h1>
				<div style="padding:5px 10px;line-height:1.7em">
					<?= nl2br($read->description ?? ""); ?>
				</div>
			</label>
		</div>
		<?php
		$grem->getLast()->close();
		$grem->terminate();
		unset($grem);
	}
} elseif ($id == null) {
	$grem = new Gremium\Gremium(true);
	$grem->header()->serve("<h1>{$fs()->title}</h1>");
	$grem->menu()->serve("<span class=\"small-media-hide\">No selected documents</span>");
	unset($grem);
} else {
	$grem = new Gremium\Gremium(true);
	$grem->header()->serve("<h1>{$fs()->title}</h1><cite>$id</cite>");
	$grem->menu()->serve("<span class=\"small-media-hide\">Requested document is not available</span>");
	$grem->article()->serve(
		<<<HTML
		<ul>
			<li>Document `$id` is not valid or doesn't exists on the current company scope</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<li>Contact system administrator for further assistance</li>
		</ul>
		HTML
	);
	unset($grem);
}
?>