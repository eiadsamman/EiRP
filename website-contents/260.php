<?php
$database = array(
	'table' => 'brands',
	'tableselect' => 'brands',
	'tablename' => 'Brands',

	'fields' => array(
		'brand_id' => array(null, 'ID', true, null, 'primary', 'int', true, null, null),
		'file_logo' => [null, 'Logo', true, null, 'file', System\Attachment\Type::BrandLogo->value, true, null, null],
		'brand_name' => array(null, 'Name', true, "100%", 'text', 'string', true, null, null, '<b>char(12)</b> Brand name'),

	),
	'order' => array('brand_id'),
);


include("website-contents/major.editor.php");
?>