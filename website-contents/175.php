<?php
$database=array(
'table'=>'labour_method',
'tableselect'=>'labour_method',
'tablename'=>'Payments Methods',


'fields'=>array(
	'lbr_mth_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'	,true	,null	,null	),
	'lbr_mth_name'=>array(null,'Name'			,true	,'100%'	,'text'		,'string'	,true	,null	,null	,'<b>char(32)</b> salary payment method name'),
),
'order'=>array('lbr_mth_id'),
'readonly'=>true,
'disable-delete'=>true,
'disable-add'=>true
);

include("website-contents/major.editor.php");
?>