<?php
$predefined=new \System\Finance\PredefinedRules($app);
$defines   =$predefined->paymentRules();

if (sizeof($defines) > 0) {
	echo <<<HTML
		<div class="quickstatement rowclicks">
			<span>
				<div class="btn-set" style="text-align: left;">
					<span style="font-family: glyphs;font-size: 1.5em;color: red">&#xe91c;</span>
					<input type="text" class="flex" placeholder="Quick payment..." id="js-defines_payment" data-slo=":LIST" tabindex="-1" data-list="quick-defines-payment" />
				</div>
			</span>
			<div>
	HTML;

	$slo = "";
	foreach ($defines as $rule) {
		echo "<a href=\"{$fs(95)->dir}/?quick={$rule->id}\"><span>{$rule->name}</span></a>";
		$slo .="<option data-id=\"{$rule->id}\" data-account_bound=\"{$rule->outbound_account}\" data-category=\"{$rule->category}\" >{$rule->name}</option>";
	}

	echo <<<HTML
			</div>
		</div>
		<datalist id="quick-defines-payment">{$slo}</datalist>
		<script type="text/javascript">
			$("#js-defines_payment").slo();
		</script>
	HTML;
}