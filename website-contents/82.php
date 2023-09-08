<?php
$database=array(
'table'=>'acc_categorygroups',
'tableselect'=>'acc_categorygroups',
'tablename'=>'Category Groups',

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
	'accgrp_id'  =>array(null,'ID'			,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'accgrp_name'=>array(null,'Name'			,true	,"100%"	,'text'		,'string'	,true	,null	,null		,'<b>char(1024)</b> accounting group name'),
),
'order'=>array('accgrp_name')
);

include("website-contents/major.editor.php");
?>