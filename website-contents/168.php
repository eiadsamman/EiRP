<?php
$database=array(
'table'=>'absence_calc',
'tableselect'=>'absence_calc LEFT JOIN absence_types ON abs_typ_id=abscal_type LEFT JOIN absence_starts ON abscal_start_base=abs_srt_id',
'tablename'=>'Absence Conditions',


'fields'=>array(
	'abscal_id'		=>array(null,'ID'		,true	,null	,'primary'	,'int'		,true	,null	,null		),
	
	'abs_typ_name'		=>array(null,'Type'		,true	,null	,'slo'		,'string'		,false	,'ABSENCE_TYPE'	,'abscal_type'	,'<b>list</b> Absence type'),
	'abscal_type'		=>array(null,''		,false	,null	,'sloref'		,'int'		,true	,null	,null		),
	
	'abscal_period_from'=>array(null,'Strating Period',true	,null	,'text'		,'int'		,true	,null	,null		,'<b>int: months</b> => Starting work period'),
	'abscal_period_to'	=>array(null,'Ending Period'	,true	,null	,'text'		,'int'		,true	,null	,null		,'<b>int: months</b> < Ending work period'),

	
	'abscal_allowed'	=>array(null,'Allowed Days-off'	,true	,null	,'text'		,'float'		,true	,null	,null		,'<b>int</b> Allowed days-off for given condition'),
	'abscal_over'		=>array(null,'Over Period',true	,null	,'text'		,'int'		,true	,null	,null		,'<b>int: months</b> Allowed days-off over given period'),


	'abs_srt_name'		=>array(null,'Start Base',true	,null	,'slo'		,'string'		,false	,'ABS_STARTS'	,'abscal_start_base'	,'<b>list</b> Calculation starting base'),
	'abscal_start_base'	=>array(null,''		,false	,null	,'sloref'		,'int'		,true	,null	,null		),


	'abscal_payment_perc'=>array(null,'Payment Percentage'	,true	,'100%'	,'text'		,'float'		,true	,null	,null		,'<b>float</b> Payment pertentage if condition is true'),
	
),
'flexablewidth'=>array(),
'order'=>array('abs_typ_name'),
);

include("website-contents/major.editor.php");
?>