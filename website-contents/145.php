<?php
$database=array(
'table'=>'mat_materialtype',
'tableselect'=>'mat_materialtype',
'tablename'=>'Material Types',

/*
Crit
0:	id|null 					Set id field
1:	STR 						Field title
2:	true|false					Display field column
3:	null|#px|#%					Field width
4:	hidden|test|slo|sloref		Input type
5:	int|string					Table column type
6:	true|false					Allow field value updating
7:	null|string					SLO reference field
8:	null|string					SLO field ID
*/
'fields'=>array(
	'mattyp_id'  =>array(null,'ID'		,true	,null	,'primary'	,'int'		,true	,null	,null),
	'mattyp_name'=>array(null,'Name'	,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(12)</b> Type name'),
	'mattyp_description'=>array(null,'Description'	,true	,"100%"	,'text'		,'string'	,true	,null	,null,'<b>char(255)</b> Type description'),
),
'order'=>array('mattyp_name'),
);





include("website-contents/major.editor.php");
?>