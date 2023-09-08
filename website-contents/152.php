<?php
$database=array(
'table'=>'calendar',
'tableselect'=>'calendar
				LEFT JOIN users AS users_editor ON cal_editor = users_editor.usr_id
				LEFT JOIN users AS users_owner ON cal_owner = users_owner.usr_id',
'tablename'=>'Calendar',
'fields'=>array(
	'cal_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null		),



	
	'cal_date'=>array(null,'Date'			,true	,null	,'slo'		,'string'	,true	,'WIDE_DATE'	,'cal_date'		,'<b>date</b> holiday start date'),
	



	'cal_op'=>array(null,''					,false	,null	,'default'	,'int'		,true	,null	,null,	null,	'1',	false),
	'cal_details'=>array(null,'Holiday name',true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(255)</b> holiday name'),
	'cal_period'=>array(null,'Period'		,true	,null	,'text'		,'int'		,true	,null	,null		,'<b>int(4)</b> holiday period'),
	'cal_yearly'=>array(null,'Yearly'		,true	,"100%"	,'bool'		,'int'		,true	,null	,null		,'<b>bool</b> yearly holiday'),
	'cal_owner'=>array(null,''				,false 	,null	,'default'	,'int'		,true	,null	,null		,null,'0'	,false),
	'cal_editor'=>array(null,'Editor'		,false	,null	,'default'	,'int'		,true	,null	,null		,null,$USER->info->id,false),
),
'order'=>array('cal_date')
);

include("website-contents/major.editor.php");
?>