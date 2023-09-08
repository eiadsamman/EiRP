<?php
$database=array(
'table'=>'workingtimes',
'tableselect'=>'workingtimes',
'tablename'=>'Working Times',


'fields'=>array(
	'lwt_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'	,true	,null	,null	),
	'lwt_name'=>array(null,'Name'				,true	,null	,'text'		,'string'	,true	,null	,null	,'<b>char(32)</b> working time name'),
	'lwt_value'=>array(null,'Working Time'		,true	,'100%'	,'text'		,'int'	,true	,null	,null	,'<b>int(5)</b> working time period (minutes)<br />This field is required to calculate emplyee salary <br />based on his/her job title salary as a percentage'),
),
'order'=>array('lwt_id')
);

include("website-contents/major.editor.php");
?>