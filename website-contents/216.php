<?php

use System\App;
use Finance\Accounting;

echo "
<div style=\"min-width:300px\">
	<div class=\"widgetWQY\" style=\"padding-right:10px;\">
		<div>";
if (App::$_user->account) {
	include_once("admin/class/accounting.php");
	$accounting = new Accounting();
	$__workingaccount = $accounting->account_information(App::$_user->account->id);
}
if (App::$_user->account->role->view) {
	if (App::$_user->account->id && $__workingaccount) {
		echo "
			<div style=\"color:#666;\"><b>{$USER->company->name}</b><br />{$__workingaccount['group']}<br />{$__workingaccount['name']}</div>
			<div style=\"padding-top:10px;font-size:1.8em;font-weight:bold;color:#06c;\">" . ($__workingaccount['balance'] < 0 ? "(" . number_format(abs($__workingaccount['balance']), 2, ".", ",") . ")" : number_format(abs($__workingaccount['balance']), 2, ".", ",")) . "</div>
			<div style=\"color:#777;font-size:1em;\">{$__workingaccount['currency']['shortname']}</div>
		";
	} else {
		echo "
			<div style=\"color:#666;\">[No<br />selected account]</div>
			<div style=\"padding-top:10px;font-size:1.6em;font-weight:bold;\">0.00</div>
			<div style=\"color:#777;font-size:1em;\">N/A</div>
		";
	}
} else {
	echo "
		<div style=\"color:#666;\"><b>{$USER->company->name}</b><br />{$__workingaccount['group']}<br />{$__workingaccount['name']}</div>
		<div style=\"padding-top:10px;font-size:1.8em;font-weight:bold;color:#888;text\"><cite>[Restricted]</cite></div>
		<div style=\"color:#777;font-size:1em;\">{$__workingaccount['currency']['shortname']}</div>
	";
}
//echo "<div><span style=\"background-color:#{$arrout[$cotk][2][$valk]};\"></span><b>$valv</b> $valk</div>";
echo "
		</div>
	</div>
</div>";
