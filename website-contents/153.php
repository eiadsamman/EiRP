<?php
$database=array(
'table'=>'labour_transportation',
'tableselect'=>'labour_transportation',
'tablename'=>'Transportation',


'fields'=>array(
	'trans_id' 			=>array(null,'ID'			,true	,null	,'primary'	,'int'	,true	,null	,null),
	'trans_name'			=>array(null,'Name'			,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(64)</b> Name'),
	'trans_capacity'		=>array(null,'Capacity'		,true	,null	,'text'		,'int'	,true	,null	,null,'<b>int</b> Capacity'),
	'trans_paymentmethod'	=>array(null,'Payement Method',true	,null	,'text'		,'int'	,true	,null	,null,'<b>int</b> 1: hour, 2: day, 3: week, 4: month, 5: year'),
	'trans_cost'			=>array(null,'Cost'			,true	,null	,'text'		,'float'	,true	,null	,null,'<b>float</b> Cost'),
	'trans_plate'			=>array(null,'License Plate'	,true	,null	,'text'		,'text'	,true	,null	,null,'<b>char(12)</b> License plate number'),
	'trans_active'			=>array(null,'Active'		,true	,"100%"	,'bool'		,'int'	,true	,null	,null,'<b>bool</b> Active'),
	
	
),
'order'=>array('trans_id'),
);





include("website-contents/major.editor.php");
?>