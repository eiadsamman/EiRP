<?php


if ($app->user->account) {
	echo "
<div class=\"widgetWQY\">
	<div style=\"padding-right:10px;\">
		<div>";
	$accounting = new \System\Finance\Accounting($app);
	if ($app->user->account->role->view) {
		if ($app->user->account) {
			echo "
			<div><span style=\"color:#999;\">{$app->user->company->name}<br />{$app->user->account->type->name}</span><br />
			<span style=\"display:block;font-size:1.3em;padding-top:6px;color:var(--root-font-color)\">{$app->user->account->name}</span></div>
			<div style=\"padding-top:10px;font-size:1.8em;font-weight:bold;color:dodgerblue;\">" .

				($app->user->account->balance < 0 ? "(" . number_format(abs($app->user->account->balance), 2, ".", ",") . ")" : number_format($app->user->account->balance, 2, ".", ",")) .
				"</div>
			<div style=\"color:#777;font-size:1em;\">{$app->user->account->currency->shortname}</div>
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
		<div><span style=\"color:#999;\">{$app->user->company->name}<br />{$app->user->account->type->name}</span><br />
		<span style=\"display:block;font-size:1.3em;padding-top:6px;color:var(--root-font-color)\">{$app->user->account->name}</span></div>
		<div style=\"padding-top:10px;font-size:1.8em;font-weight:bold;color:dodgerblue;\"><cite>[Restricted]</cite></div>
		<div style=\"color:#777;font-size:1em;\">{$app->user->account->currency->shortname}</div>
	";
	}
	echo "
		</div>
	</div>
</div>";
}