<?php
use System\Template\Gremium;

$perpage_val = 20;
$id          = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
$statement   = new System\Finance\Transaction\Statement($app);
$read        = $statement->read($id ?? 0);

if ($read) {

	if (empty($read->debitor) && empty($read->creditor)) {
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev("href=\"{$fs(214)->dir}\" data-href=\"{$fs(214)->dir}\"")->status(Gremium\Status::Exclamation)->serve("<h1>{$fs()->title}</h1><cite>" . ($read ? $read->id : "") . "</cite>");
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
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev("href=\"{$fs(214)->dir}\" data-href=\"{$fs(214)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite>{$app->prefixList[13][0]}" . str_pad($read->id, $app->prefixList[13][1], "0", STR_PAD_LEFT) . "</cite>");
		$grem->menu()->open();

		echo "<button type=\"button\" data-href=\"{$fs(91)->dir}/\" class=\"standard edge-left\" tabindex=\"-1\">{$fs(91)->title}</button>";
		echo "<button type=\"button\" data-href=\"{$fs(95)->dir}/\" class=\"standard edge-right\" tabindex=\"-1\">{$fs(95)->title}</button>";


		echo "<span class=\"flex\"></span>";
		if ($fs(101)->permission->edit) {
			echo "<input type=\"button\" data-href=\"{$fs(101)->dir}/?id={$read->id}\" id=\"js-input_edit\" value=\"Edit\" class=\"edge-left\" tabindex=\"-1\" />";
			echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs(142)->dir}\" id=\"js-input_print\" class=\"edge-right\" tabindex=\"-1\">Print</button>";
		} else {
			echo "<button data-key=\"{$read->id}\" data-ploturl=\"{$fs(142)->dir}\" id=\"js-input_print\" class=\"edge-right edge-left\" tabindex=\"-1\">Print</button>";
		}

		$grem->getLast()->close();
		$grem->title()->serve("<span class=\"flex\">Statement details</span>");
		$grem->article()->open(); ?>
		<iframe id="plot-iframe" name="plot-iframe" style="display:block;width:0;height:0px;visibility: hidden"></iframe>

		<div class="form">
			<label>
				<h1>Statement ID</h1>
				<div class="btn-set">
					<span>
						<?= $app->prefixList[13][0] . str_pad($read->id, $app->prefixList[13][1], "0", STR_PAD_LEFT); ?>
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

		<div class="form">
			<label>
				<h1>Value</h1>
				<div class="btn-set">
					<span>
						<?= $read->currency->shortname . " " . number_format($read->value, 2); ?>
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
		
		<div class="form">
			<label>
				<h1>Issuer</h1>
				<div class="btn-set">
					<span class="at"><a href="<?= $fs(182)->dir ?>/?id=<?= $read->editor->id ?>"
							title="<?= $read->editor->fullName() ?>"><?= $read->editor->fullName() ?></a></span>
				</div>
			</label>
		</div>

		<div class="form">
			<label>
				<h1>Beneficiary</h1>
				<div class="btn-set">
					<?= is_null($read->party) ? "<span>-</span>" : "<span>{$read->party->name}</span>"; ?>
				</div>
			</label>
			<label>
				<h1>Attention</h1>
				<div class="btn-set">
					<?= is_null($read->individual) ? "<span>{$read->beneficiary}</span>" : "<span class=\"at\"><a href=\"{$fs(182)->dir}/?id={$read->individual->id}\" title=\"{$read->individual->fullName()}\">{$read->individual->fullName()}</a></span>"; ?>
				</div>
			</label>
		</div>

		<div class="form">
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

							if (explode("/", $file->mime)[0] == "image") {
								echo "<a title=\"{$file->name}\" href=\"{$fs(187)->dir}?id={$file->id}&pr=v\">";
								echo "<img src=\"{$fs(187)->dir}?id={$file->id}&pr=t\" />";
								echo "</a>";
							} else {
								echo "<a title=\"{$file->name}\" href=\"{$fs(187)->dir}?id={$file->id}\" data-attachment=\"force\" target=\"_blank\">";
								$extpos = strrpos($file->name, ".", -1);
								if ($extpos !== false) {
									$ext      = strtolower(substr($file->name, $extpos + 1));
									$clr      = crc32($ext) % 360;
									$filename = substr($file->name, 0, $extpos);
								} else {
									$ext      = ".";
									$clr      = 0;
									$filename = "";
								}
								echo "<span>";
								echo "<span style=\"color: hsl($clr, 50%, 50%);\">{$ext}</span>";
								echo "<div>" . (number_format($file->size / 1024, 0)) . "KB</div>";
								echo "<div>{$filename}</div>";
								echo "</span>";
								echo "</a>";
							}
						}
						?>
					</div>
				</label>
			</div>
		<?php } ?>

		<div class="form">
			<label>
				<h1>Category</h1>
				<div class="btn-set">
					<span><?= $read->category->group . ": " . $read->category->name ?></span>
				</div>
			</label>
		</div>
		
		<div class="form">
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
	$grem->header()->prev("href=\"{$fs(214)->dir}\" data-href=\"{$fs(214)->dir}\"")->serve("<h1>{$fs()->title}</h1>");

	$grem->title()->serve("<span>Bad request</span>");
	$grem->article()->serve(
		<<<HTML
			<ul>
				<li>Empty request or invalid request</li>
				<li>Document is locked or out of scope</li>
				<li>Your account doesn't have premissions for neither `Creditor` and `Debitor` accounts</li>
				<li>Contact system administrator for further assistance</li>
				<li>Back to <a href="{$fs(214)->dir}" data-href="{$fs(214)->dir}">statements list</a></li>
			</ul>
			HTML
	);
	unset($grem);
} else {
	$grem = new Gremium\Gremium(true);
	$grem->header()->prev("href=\"{$fs(214)->dir}\" data-href=\"{$fs(214)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite>$id</cite>");
	$grem->title()->serve("<span class=\"small-media-hide\">Requested document is not available</span>");
	$grem->article()->serve(
		<<<HTML
			<ul>
				<li>Document is locked or out of scope</li>
				<li>Your account doesn't have premissions for neither `Creditor` and `Debitor` accounts</li>
				<li>Contact system administrator for further assistance</li>
				<li>Back to <a href="{$fs(214)->dir}" data-href="{$fs(214)->dir}">statements list</a></li>
			</ul>
			HTML
	);
	unset($grem);
}
?>