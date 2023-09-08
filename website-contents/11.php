<?php
$database=array(
'table'=>'acc_accounttype',
'tableselect'=>'acc_accounttype LEFT JOIN acc_termgroup ON ptp_termgroup_id = trmgrp_id ',
'tablename'=>'Partition Types',

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
	'ptp_id'  =>array(null,'ID'			,true	,null	,'primary'	,'int'		,true	,null	,null),
	'ptp_name'=>array(null,'Name'		,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(32)</b> partition type name'),
	'trmgrp_name'=>array(null,'Term'	,true	,"100%"	,'slo'		,'string'	,false	,'ACCOUNTING_TERM'	,'ptp_termgroup_id'	,'<b>list</b> Accounting term name'),
	'ptp_termgroup_id'=>array(null,''		,false	,null	,'sloref'	,'int'		,true	,null	,null		),
	
)
);





include("website-contents/major.editor.php");
?>