<?php

echo "
<div class=\"widgetWQY\">
	<div style=\"padding-right:10px;\">
		<div>";
if ($app->user->account) {
	$accounting = new \System\Finance\Accounting($app);
	$__workingaccount = $accounting->account_information($app->user->account->id);
}
if ($app->user->account->role->view) {
	if ($app->user->account->id && $__workingaccount) {
		echo "
			<div><span style=\"color:#999;\">{$app->user->company->name}<br />{$__workingaccount['group']}</span><br /><span style=\"display:block;font-size:1.3em;padding-top:6px\">{$__workingaccount['name']}</span></div>
			<div style=\"padding-top:10px;font-size:1.8em;font-weight:bold;color:dodgerblue;\">" . ($__workingaccount['balance'] < 0 ? "(" . number_format(abs($__workingaccount['balance']), 2, ".", ",") . ")" : number_format(abs($__workingaccount['balance']), 2, ".", ",")) . "</div>
			<div style=\"color:#777;font-size:1em;\">{$__workingaccount['currency']['shortname']}</div>
		";
	} else {
		echo "
			<div>[No<br />selected account]</div>
			<div style=\"padding-top:10px;font-size:1.6em;font-weight:bold;\">0.00</div>
			<div style=\"color:#777;font-size:1em;\">N/A</div>
		";
	}
} else {
	echo "
		<div><span style=\"color:#999;\">{$app->user->company->name}<br />{$__workingaccount['group']}</span><br /><span style=\"display:block;font-size:1.3em;padding-top:6px\">{$__workingaccount['name']}</span></div>
		<div style=\"padding-top:10px;font-size:1.8em;font-weight:bold;color:dodgerblue;text\"><cite>[Restricted]</cite></div>
		<div style=\"color:#777;font-size:1em;\">{$__workingaccount['currency']['shortname']}</div>
	";
}
echo "
		</div>
	</div>
</div>";