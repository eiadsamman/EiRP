<?php
$database=array(
'table'=>'labour_shifts',
'tableselect'=>'labour_shifts',
'tablename'=>'Labor Shifts',


'fields'=>array(
	'lsf_id'  		=>array(null,'ID'					,true	,null	,'primary'	,'int'		,true	,null	,null),
	'lsf_name'		=>array(null,'Name'					,true	,null	,'text'		,'string'	,true	,null	,null ,'<b>char(32)</b> shift name'),
	'lsf_wstart'	=>array(null,'Start time'	,true	,null	,'text'		,'int'		,true	,null	,null ,'<b>int(6)</b> shift start time in minutes `0=00:00, 1439=23:59`'),
	//'lsf_sstart'	=>array(null,'Summer start time'	,true	,null	,'text'		,'int'		,true	,null	,null ,'<b>int(6)</b> shift summber start time in minutes `0=00:00, 1439=23:59`'),
	'lsf_hours'		=>array(null,'Hours'				,true	,null	,'text'		,'int'		,true	,null	,null ,'<b>int(4)</b> shift period in hours'),
	'lsf_system'	=>array(null,'System Shift'			,true	,"100%"	,'text'		,'int'		,true	,null	,null ,'<b>int(1)</b> define as a system shift, <br />system shift controls system related time <br />functions (Attendance, production)'),
)
);


include("website-contents/major.editor.php");
?>