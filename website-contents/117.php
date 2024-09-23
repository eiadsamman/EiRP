<?php
$database=array(
'table'=>'partitionlabour',
'tableselect'=>'
	partitionlabour AS _main
		LEFT JOIN
		(
			SELECT 
				prt_id, CONCAT_WS(": ", comp_name, prt_name) AS prt_name
			FROM
				`acc_accounts` 
				JOIN companies ON comp_id = prt_company_id
				JOIN currencies ON cur_id = prt_currency
		
		) AS _sub ON _main.prtlbr_prt_id = _sub.prt_id
		',
'tablename'=>'Default Attendance Partitions',

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
	'prtlbr_id'  =>array(null,'ID'						,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'prtlbr_name'=>array(null,'Operation Name'			,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(255)</b> Operation name'),
	'prt_name'		=>array(null,'Company / Location'	,true	,null	,'slo'		,'string'	,false	,'D002'	,'prtlbr_prt_id'	,'<b>list</b> '),
	'prtlbr_prt_id'	=>array(null,''						,false	,null	,'sloref'	,'int'		,true	,null	,null		),
	
	'prtlbr_op'=>array(null,'Operation'					,true	,"100%"	,'text'		,'int'	,true	,null	,null		,'<b>int(1-3)</b> 1: Check-in, 2: Internal, 3: Check-out'),
),
'order'=>array('prtlbr_id'),

);

include("website-contents/major.editor.php");
?>