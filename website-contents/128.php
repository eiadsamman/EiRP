<?php
$database=array(
'table'=>'absence_types',
'tableselect'=>'absence_types',
'tablename'=>'Absence Type',

'fields'=>array(
	'abs_typ_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'abs_typ_name'=>array(null,'Name'				,true	,"100%"	,'text'		,'string'	,true	,null	,null		,'<b>char(32)</b> absence type name'),
),
'order'=>array('abs_typ_id'),


);

include("website-contents/major.editor.php");
?>