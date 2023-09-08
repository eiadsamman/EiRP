<?php
$database=array(
'table'=>'inv_costcenter',
'tableselect'=>'inv_costcenter',
'tablename'=>'Cost Center',

/*
Crit
0:	id|null 					Set id field
1:	STR 						Field title
2:	true|false					Display field column
3:	null|#px|#%					Field width
4:	hidden|test|slo|sloref		Input type
5:	int|string					Table column type
6:	true|false					Allow field value updating
7:	null|string					SLO reference field
8:	null|string					SLO field ID
*/
'fields'=>array(
	'ccc_id'  		=>array(null,'ID'		,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'ccc_name'		=>array(null,'Name'		,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(1024)</b> cost center name'),
	'ccc_vat'		=>array(null,'VAT'		,true	,null	,'text'		,'float'	,true	,null	,null		,'<b>char(1024)</b> cost center VAT Rate'),
	'ccc_default'	=>array(null,'Default'	,true	,"100%"	,'bool'		,'int'		,true	,null	,null		,'<b>bool</b> system default'),
),
'order'=>array('ccc_id'),
'pre_submit_functions'=>array(
	"check_default_costcenter"=>function($input,$sql,$user){
		if(isset($input[md5("MEdH265".'ccc_default')])){
			$sql->query("UPDATE inv_costcenter SET ccc_default=0");
		}
	}
),
);

include("website-contents/major.editor.php");
?>