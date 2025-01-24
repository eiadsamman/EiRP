<?php
use System\Timeline\Module;

$database = array(
	'table' => 'companies',
	'tableselect' => '
	companies 
		LEFT JOIN business_field ON comp_field=bisfld_id
		LEFT JOIN countries ON cntry_id = comp_country
		',
	'tablename' => 'Companies',

	'fields' => array(
		'comp_id' => [null, 'ID', true, null, 'primary', 'int', true, null, null],
		'comp_name' => [null, 'Name', true, null, 'text', 'string', true, null, null],


		'file_logo' => [null, 'Logo', true, null, 'file', System\Attachment\Type::CompanyLogo->value, true, null, null],

		'comp_tellist' => [null, 'Contact numbers', false, null, 'textarea', 'string', true, null, null],
		'comp_emaillist' => [null, 'Contact Emails', false, null, 'textarea', 'string', true, null, null],

		'cntry_name' => [null, 'Country', true, null, 'slo', 'string', false, 'COUNTRIES', 'comp_country'],
		'comp_country' => [null, '', false, null, 'sloref', 'int', true, null, null],
		'comp_city' => [null, 'City', true, '100%', 'text', 'string', true, null, null],
		'comp_address' => [null, 'Address', false, null, 'text', 'string', true, null, null],

		'comp_latitude' => [null, 'Latitude', false, null, 'text', 'string', true, null, null],
		'comp_longitude' => [null, 'Longitude', false, null, 'text', 'string', true, null, null],


		'bisfld_name' => [null, 'Business field', false, null, 'slo', 'string', false, 'BUSINESS_FIELD', 'comp_field'],
		'comp_field' => [null, '', false, null, 'sloref', 'int', true, null, null],
		'comp_date' => ["comp_date", 'Creation date', false, null, 'hidden', 'string', false, null, null, null, '', false],

	),
	'order' => ['comp_name'],
	'timeline' => [Module::Company]
);


include("website-contents/major.editor.php");
?>