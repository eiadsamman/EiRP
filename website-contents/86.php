<?php
$database=array(
'table'=>'currencies',
'tableselect'=>'currencies',
'tablename'=>'Currencies',

'fields'=>array(
	'cur_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null		),
	'cur_name'=>array(null,'Name'				,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(32)</b> currency name'),
	'cur_shortname'=>array(null,'Short name'	,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(4)</b> currency short name'),
	'cur_symbol'=>array(null,'Symbol'			,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(6)</b> currency symbol'),
	'cur_default'=>array(null,'Default'		,true	,"100%"	,'bool'		,'int'	,true	,null	,null		,'<b>bool</b> system default currency'),
),
'order'=>array('cur_name'),
'pre_submit_functions'=>array(
	"check_default_currency"=>function($input,$sql,$user){
		if(isset($input[md5("MEdH265".'cur_default')])){
			$sql->query("UPDATE currencies SET cur_default=0");
		}
	}
),
);

include("website-contents/major.editor.php");
?>