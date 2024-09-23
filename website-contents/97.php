<?php
$database=array(
'table'=>'acc_accounts',
'tableselect'=>"
				acc_accounts
					JOIN companies ON comp_id=prt_company_id 
					LEFT JOIN currencies ON cur_id = prt_currency
					",
'tablename'=>'Accounts',

//AND comp_id = {$_XUXSER['company']['id']}
//JOIN user_company ON prt_company_id=urc_usr_comp_id AND urc_usr_id={$_USEXR['id']}

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
9:	null|string					Feild description
10:	string						Default value
*/
'fields'=>array(
	'prt_id'=>array(null,'ID'					,true	,null	,'primary'	,'int'		,true	,null	,null		),
	
	
	'comp_name'=>array(null,'Company'			,true	,null	,'slo'		,'string'	,false	,'COMPANIES'	,'prt_company_id'	,'<b>list</b> Company name'),
	'prt_company_id'=>array(null,''				,false	,null	,'sloref'	,'int'		,true	,null	,null		),
	
	'prt_name'=>array(null,'Name'				,true	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(32)</b> Account name'),
	
	'cur_shortname'=>array(null,'Currency'		,true	,null	,'slo'		,'string'	,false	,'CURRENCY'	,'prt_currency'	,'<b>list</b> Account currency'),
	'prt_currency'	=>array(null,''				,false	,null	,'sloref'	,'int'		,true	,null	,null		),
	
	
	'prt_color'=>array(null,'Color Code'		,false	,null	,'text'		,'string'	,true	,null	,null		,'<b>char(6)</b> Account color code'),
	'prt_lbr_perc'=>array(null,'Employement calculating'			 ,false	 ,null	,'text'		 ,'float'	 ,true	 ,null	,null		,'<b>float(6,2)</b> Employement attendance calculating as a percentage'),
	'prt_remarks'=>array(null,'Description'		,true	,"100%"	,'textarea' ,'string'	,true	,null	,null		,'<b>text</b> Account description'),
	'prt_date'=>array("prt_date",'Creation date',false  ,null ,'hidden'	,'string'   ,false  ,null   ,null		,null,'',false),
),
'order'=>array('prt_company_id','prt_name'),
'flexablewidth'=>array(),
'search'=>array('prt_id','comp_name','prt_name'),
'perpage'=>20
);

include("website-contents/major.editor.php");
?>