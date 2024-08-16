<?php
$database = array(
	'table' => 'companies_legal',
	'tableselect' => '
	companies_legal
		JOIN companies ON comp_id = commercial_companyId',
	'tablename' => 'Commercial Registrations',

	'fields' => array(
		'commercial_id' => array(null, 'ID', true, null, 'primary', 'int', true, null, null),
		'commercial_default' => [null, 'Default', true, null, 'bool', 'int', true, null, null],

		'comp_name' => [null, 'Company', true, null, 'slo', 'string', false, 'COMPANIES_ALL', 'commercial_companyId'],
		'commercial_companyId' => [null, '', false, null, 'sloref', 'int', true, null, null],

		'commercial_legalName' => [null, 'Legal name', true, null, 'text', 'string', true, null, null],
		'commercial_registrationNumber' => [null, 'Registration number', true, null, 'text', 'string', true, null, null],
		'file_doc' => [null, 'Documents', false, null, 'file', \System\Attachment\Type::CompanyCommercialDoc->value, true, null, null],

		'commercial_taxNumber' => [null, 'Tax number', true, null, 'text', 'string', true, null, null],
		'file_tax' => [null, 'Documents', false, null, 'file', \System\Attachment\Type::CompanyTaxDoc->value, true, null, null],

		'commercial_vatNumber' => [null, 'VAT number', true, "100%", 'text', 'string', true, null, null],
		'file_vat' => [null, 'Documents', false, null, 'file', \System\Attachment\Type::CompanyVatDoc->value, true, null, null],

	),
	'order' => array('commercial_companyId', 'commercial_id'),

	'pre_submit_functions' => array(
		"check_default_currency" => function ($input, $app) {
			if (
				isset($input[md5("MEdH265" . 'commercial_default')])
				&& isset($input[md5("MEdH265" . 'comp_name')])
				&& isset($input[md5("MEdH265" . 'comp_name')][1])
			) {
				$app->db->query("UPDATE companies_legal SET commercial_default = 0 WHERE commercial_companyId = {$input[md5('MEdH265comp_name')][1]}; ");
			}
		}
	),
);


include ("website-contents/major.editor.php");
?>