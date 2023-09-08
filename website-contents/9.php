<?php
//cobjecttype


$sql->query("ALTER TABLE `cobjecttype` CHANGE `cot_init` `cot_init` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");

$database=array(
'table'=>'cobjecttype',
'tableselect'=>'cobjecttype ',
'tablename'=>'Materials',

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
9:	null|string					Feild description
10:	string						Default value
11: bool						Disable field on editing 
*/
'fields'=>array(
	'cot_id' 	 	=>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'cot_name'		=>array(null,'Pool Name'	,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(255)</b> Pool name'),
	'cot_mastercarton'=>array(null,'Available'	,true	,null	,'text'		,'int'	,true	,null	,null		,'<b>int(0-1)</b> 1: Opened, 0: Closed'),
	'cot_init'		=>array(null,'Prefix'	,true	,null	,'text'		,'text'	,true	,null	,null		,'<b>char(2)</b> Prefix'),
	
	 	
	'cot_capacity'	=>array(null,'Capacity'			,true	,"100%"	,'text'		,'int'	,true	,null	,null		,'<b>int</b> Capacity'),
	
),
'order'=>array('cot_name'),

);

include("website-contents/major.editor.php");
?>