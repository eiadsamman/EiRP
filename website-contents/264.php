<?php
$database = array(
	'table' => 'acc_bankaccount',
	'tableselect' => '
	acc_bankaccount 
		LEFT JOIN companies ON bnkacc_type = 1 AND bnkacc_owner_id = comp_id
		LEFT JOIN currencies ON cur_id = bnkacc_currency_id
		',
	'tablename' => 'Company Bank Account',

	'fields' => array(
		'bnkacc_id' => [null, 'ID', false, null, 'primary', 'int', true, null, null],
		'bnkacc_type' => [null, 'Type', false, null, 'hidden', 'string', true, null, null, '', '1', true,],

		'comp_name' => [null, 'Company', true, null, 'slo', 'string', false, 'COMPANIES', 'bnkacc_owner_id', ''],
		'bnkacc_owner_id' => [null, '', false, null, 'sloref', 'int', true, null, null],

		'cur_shortname' => [null, 'Currency', true, null, 'slo', 'string', false, 'CURRENCY', 'bnkacc_currency_id', ''],
		'bnkacc_currency_id' => [null, '', false, null, 'sloref', 'int', true, null, null],

		'bnkacc_bankname' => [null, 'Bank Name', true, null, 'text', 'string', true, null, null, ''],
		'bnkacc_number' => [null, 'Account Number', true, null, 'text', 'string', true, null, null, ''],
		'bnkacc_holdername' => [null, 'Account Name', true, "100%", 'text', 'string', true, null, null, ''],

		'bnkacc_iban' => [null, 'IBAN', false, null, 'text', 'string', true, null, null, ''],
		'bnkacc_swift' => [null, 'Swift', false, null, 'text', 'string', true, null, null, ''],

		'bnkacc_created_at' => ['bnkacc_created_at', 'Swift', false, null, 'hidden', 'string', false, null, null, '', true],

	),
	'order' => ['bnkacc_owner_id', 'bnkacc_bankname'],
	'where' => ' bnkacc_type = 1 '
);


include ("website-contents/major.editor.php");
?>