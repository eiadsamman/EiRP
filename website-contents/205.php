<?php
$database=array(
'table'=>' system_prefix ',
'tableselect'=>'system_prefix',
'tablename'=>'Documents naming',

'fields'=>array(
	'prx_id'  =>array(null,'ID'			,true	,null	,'primary'	,'int'	,true	,null	,null		),
	'prx_name'=>array(null,'Name'		,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(32)</b> document name',null,true),
	'prx_value'=>array(null,'Prefix'	,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>inchar(8)</b> document prefix'),
	'prx_placeholder'=>array(null,'Padding',true,'100%'	,'text'		,'int'	,true	,null	,null		,'<b>int(3)</b> zero padding count',null,false),
),
'order'=>array('prx_id'),
'disable-delete'=>true,

);

include("website-contents/major.editor.php");
?>