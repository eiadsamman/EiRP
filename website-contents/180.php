<?php
if ($app->user->account) {
	$accounting = new System\Finance\Accounting($app);

	echo json_encode(array(
		'company' => $app->user->company ? $app->user->company->name : false,
		'group' => isset($app->user->account) ? $app->user->account->type->name : false,
		'name' => isset($app->user->account) ? $app->user->account->name : false,
		'currency' => isset($app->user->account) ? $app->user->account->currency->name : false,
		'value' => (isset($app->user->account) && $app->user->account->balance != null ?
			($app->user->account->balance < 0 ?
				"(" . number_format(abs($app->user->account->balance), 2, ".", ",") . ")" :
				number_format(abs($app->user->account->balance), 2, ".", ","))
			: false),
	));
} else {
	echo json_encode(array(
		'company' => "N/A",
		'group' => "",
		'name' => "N/A",
		'currency' => "N/A",
		'value' => "0.00",
	));
}
