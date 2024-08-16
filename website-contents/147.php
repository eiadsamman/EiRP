<?php
$database=array(
'table'=>'companies',
'tableselect'=>'
	companies 
		LEFT JOIN business_field ON comp_field=bisfld_id
		LEFT JOIN (
			SELECT COUNT(prt_id) AS assigned_accounts, prt_company_id
			FROM `acc_accounts`
			GROUP BY prt_company_id
		) AS assacc ON assacc.prt_company_id = comp_id
		',
'tablename'=>'Companies',

'fields'=>array(
	'comp_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'		,true	,null	,null),
	'comp_name'=>array(null,'Name'				,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(12)</b> Name'),
	'file_logo'=>array(null,'Logo'				,true	,null	,'file'		,'242'		,true	,null	,null,'<b>file</b> Company Logo image'),
	'comp_tellist'=>array(null,'Telephone List'	,false	,null	,'text'		,'string'	,true	,null	,null,'<b>text</b> Telephone list'),
	'comp_emaillist'=>array(null,'Email list'	,false	,null	,'text'		,'string'	,true	,null	,null,'<b>text</b> Emaillist'),
	'comp_address'=>array(null,'Address'		,false	,null	,'textarea'	,'string'	,true	,null	,null,'<b>text</b> Address'),
	'comp_country'=>array(null,'Country'		,true	,null	,'text'		,'string'	,true	,null	,null,'<b>list</b> Country'),
	'bisfld_name'	=>array(null,'Business field',false	,null	,'slo'		,'string'	,false	,'BUSINESS_FIELD'	,'comp_field','<b>list</b> Business field name'),
	'comp_field'	=>array(null,''	 			,false	,null	,'sloref'	,'int'		,true	,null	,null),
	'comp_date'=>array("comp_date",'Creation date'		,false   ,null ,'hidden'	,'string'   ,false  ,null   ,null		,null,'',false),
	
	'assigned_accounts'=>array("assigned_accounts",'accounts'		,true   ,"100%" ,'hidden'	,'string'   ,false  ,null   ,null		,null,'',false),

),
'order'=>array('comp_name'),
);


include("website-contents/major.editor.php");
?>