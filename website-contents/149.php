<?php
$database=array(
'table'=>'calendar_operations',
'tableselect'=>'calendar_operations',
'tablename'=>'Calendar Operations',


'fields'=>array(
	'cop_id'  =>array(null,'ID'			,true	,null	,'primary'		,'int'	,true	,null	,null		),
	'cop_name'=>array(null,'Operation name'			,true	,'100%'	,'text'	,'string'	,true	,null	,null		,'<b>char(255)</b> calendar operation name'),
	
),
'order'=>array('cop_id')
);

include("website-contents/major.editor.php");
?>