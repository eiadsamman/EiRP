<?php
$database=array(
'table'=>'labour_type',
'tableselect'=>'labour_type LEFT JOIN labour_section ON lsc_id=lty_section',
'tablename'=>'Labor Types',

'fields'=>array(
	'lty_id'  				=>array(null,'ID'		,true	,null	,'primary'	,'int'		,true	,null	,null),
	
	'lty_name'				=>array(null,'Name'	,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(255)</b> type name'),
	
	'lsc_name'				=>array(null,'Section'	 	,true	,"100%"	,'slo'		,'string'	,false	,'E001'	,'lty_section','<b>list</b> job section'),
	'lty_section'			=>array(null,''				,false	,null	,'sloref'	,'int'		,true	,null	,null),
/*	
	'lty_salarybasic'		=>array(null,'Basic salary'	,true	,null	,'text'		,'float'	,true	,null	,null,'<b>float(7,2)</b> monthly basic salary'),
	
	'lwt_name'				=>array(null,'Working time',true	,null	,'slo'		,'string'	,false	,'WORKING_TIMES'	,'lty_time','<b>list</b> Working time group'),
	'lty_time'				=>array(null,''	 			,false	,null	,'sloref'	,'int'		,true	,null	,null),
	
	'lty_workingdays'		=>array(null,'Working days'	,true	,"100%"	,'text'		,'string'	,true	,null	,null,'<b>char(7)</b> working days, 1 represent work while 0 represent <br />non-workring day staring from saturday `smtwtf`, ex: `1111110`'),
*/	
),
'order'=>array('lsc_name ASC','lty_name ASC')
);


include("website-contents/major.editor.php");
?>