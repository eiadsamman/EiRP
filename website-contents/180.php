<?php
include_once("admin/class/accounting.php");
$accounting=new Accounting();
$__workingaccount=$accounting->account_information($USER->account->id);



$output=array(
	'company'=>isset($USER->company)?$USER->company->name:false,
	'group'=>isset($__workingaccount['group'])?$__workingaccount['group']:false,
	'name'=>isset($__workingaccount['name'])?$__workingaccount['name']:false,
	'currency'=>isset($__workingaccount['currency'])?$__workingaccount['currency']['shortname']:false,
	'value'=>isset($__workingaccount['balance']) && $__workingaccount['balance']!=false?         $__workingaccount['balance'] < 0 ? "(".number_format(abs($__workingaccount['balance']),2,".",",").")":number_format(abs($__workingaccount['balance']),2,".",",")      :false,
);
echo json_encode($output);

?>