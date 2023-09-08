<?php
$database=array(
'table'=>'acc_termgroup ',
'tableselect'=>'acc_termgroup ',
'tablename'=>'Accounting Terms',

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
	'trmgrp_id'  =>array(null,'ID'		,true	,null	,'primary'	,'int'		,true	,null	,null),
	'trmgrp_name'=>array(null,'Name'	,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(32)</b> term name'),
	'trmgrp_order'=>array(null,'Order'	,true	,"100%"	,'text'		,'int'		,true	,null	,null,'<b>char(32)</b> viewing order / negative goes on left and positive goes on right'),
),

'readonly'=>true,


);





include("website-contents/major.editor.php");
?>