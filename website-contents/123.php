<?php
$predefined = new \System\Finance\PredefinedRules($app);
$defines    = $predefined->incomeRules();

if (sizeof($defines) > 0) {
	echo <<<HTML
		<div class="quickstatement rowclicks">
			<span>
				<div class="btn-set" style="text-align: left;">
					<span style="font-family: glyphs;font-size: 1.5em;color: hsl(140, 100%, 40%)">&#xe91b;</span>
					<input type="text" class="flex" placeholder="Quick receipt..." id="js-defines_receipt" data-slo=":LIST" tabindex="-1" data-list="quick-defines-receipt" />
				</div>
			</span>
			<div>
	HTML;

	$slo = "";
	foreach ($defines as $rule) {
		echo "<a href=\"{$fs(91)->dir}/?quick={$rule->id}\"><span>{$rule->name}</span></a>";
		$slo .= "<option data-id=\"{$rule->id}\" data-account_bound=\"{$rule->outbound_account}\" data-category=\"{$rule->category}\" >{$rule->name}</option>";
	}

	echo <<<HTML
			</div>
		</div>
		<datalist id="quick-defines-receipt">{$slo}</datalist>
		<script type="text/javascript">
			$("#js-defines_receipt").slo();
		</script>
	HTML;
}