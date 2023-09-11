<?php
if ($app->user->account) {
	$accounting = new System\Finance\Accounting($app);
	$__workingaccount = $accounting->account_information($app->user->account->id);

	echo json_encode(array(
		'company' => $app->user->company ? $app->user->company->name : false,
		'group' => isset($__workingaccount['group']) ? $__workingaccount['group'] : false,
		'name' => isset($__workingaccount['name']) ? $__workingaccount['name'] : false,
		'currency' => isset($__workingaccount['currency']) ? $__workingaccount['currency']['shortname'] : false,
		'value' => (isset($__workingaccount['balance']) && $__workingaccount['balance'] != false ?
			($__workingaccount['balance'] < 0 ?
				"(" . number_format(abs($__workingaccount['balance']), 2, ".", ",") . ")" :
				number_format(abs($__workingaccount['balance']), 2, ".", ","))
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
