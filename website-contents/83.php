<?php
$database=array(
'table'=>'acc_categories',
'tableselect'=>'acc_categories JOIN acc_categorygroups ON accgrp_id=acccat_group',
'tablename'=>'Accounting Categories',

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
	'acccat_id'  =>array(null,'ID'			,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'acccat_name'=>array(null,'Name'		,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(1024)</b> category name'),
	'accgrp_name'=>array(null,'Category'	,true	,"100%"	,'slo'		,'string'	,false	,'ACC_CATGRP'	,'acccat_group'	,'<b>list</b> category group'),
	'acccat_group'=>array(null,''			,false	,null	,'sloref'	,'int'		,true	,null	,null		),
),
'order'=>array('acccat_group','acccat_name')
);

include("website-contents/major.editor.php");
?>