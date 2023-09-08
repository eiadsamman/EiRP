<?php
//cobjecttype



$database=array(
'table'=>'cobject',
'tableselect'=>'cobject ',
'tablename'=>'Production Stations',

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
	'cob_id' 	 	=>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'cob_serial'		=>array(null,'Section Name'	,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(255)</b> Section name'),
	'cob_checked'=>array(null,'Available'	,true	,null	,'text'		,'int'	,true	,null	,null		,'<b>int(0-1)</b> 1: Opened, 0: Closed'),
	'cob_type'		=>array(null,'Multiplier'	,true	,null	,'text'		,'int'	,true	,null	,null		,'<b>int</b> Type'),
	'cob_hash'	=>array(null,'Prefix'			,true	,"100%"	,'text'		,'tetx'	,true	,null	,null		,'<b>char(32)</b> Prefix'),
),
'order'=>array('cob_serial'),

);

include("website-contents/major.editor.php");
?>