<?php
$database=array(
'table'=>'candas_materials ',
'tableselect'=>'candas_materials',
'tablename'=>'Plastic materials',

'fields'=>array(
	'cndmat_id'  	=>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null),
	'cndmat_name'	=>array(null,'Name'				,true	,null	,'text'		,'string'	,true	,null	,null	,'<b>char(64)</b> Material Name'),
	'cndmat_young'	=>array(null,'Young\'s modulus'	,true	,null	,'text'		,'float'	,true	,null	,null	,'<b>int</b> Young\'s modulus (MPa)'),
	'cndmat_creep'	=>array(null,'Creep\'s modulus'	,true	,"100%"	,'text'		,'float'	,true	,null	,null	,'<b>int</b> Creep modulus'),
),
'order'=>array('cndmat_id'),
);


include("website-contents/major.editor.php");
?>