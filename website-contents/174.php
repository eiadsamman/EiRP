<?php
$database=array(
'table'=>'business_field',
'tableselect'=>'business_field',
'tablename'=>'Business Fields',


'fields'=>array(
	'bisfld_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null),
	'bisfld_name'=>array(null,'Field Name'		,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(255)</b> Name'),
	'bisfld_category'=>array(null,'Category'	,true	,"100%"	,'text'	,'string'	,true	,null	,null,'NA'),
),
'order'=>array('bisfld_name'),
);





include("website-contents/major.editor.php");
?>