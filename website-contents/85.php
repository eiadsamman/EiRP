<?php

$database = array(
	'table' => 'acc_predefines',
	'tableselect' => '
	acc_predefines 

		LEFT JOIN ( 
			SELECT 
				prt_id , CONCAT ("[", cur_shortname ,"]: ", comp_name,": ", prt_name) AS  __inbound_account
			FROM 
				view_financial_accounts
			)
			AS AliasInbound ON AliasInbound.prt_id=accdef_in_acc_id 
		
		LEFT JOIN ( 
				SELECT 
					prt_id , CONCAT ("[", cur_shortname ,"]: ", comp_name, ": ", prt_name) AS  __outbound_account
				FROM 
					view_financial_accounts
				)
				AS AliaOutbound ON AliaOutbound.prt_id = accdef_out_acc_id 
				
		LEFT JOIN (
			SELECT 
				acccat_id,CONCAT_WS(" : ",acccat_name,accgrp_name) AS acc_catdet 
			FROM 
				acc_categories 
					JOIN acc_categorygroups ON acccat_group=accgrp_id
				) AS _cats ON _cats.acccat_id=accdef_category

		LEFT JOIN companies ON companies.comp_id = accdef_company 
		',
	'tablename' => 'Default accounting settings',



	'fields' => array(
		'accdef_id' => array(null, 'ID', true, null, 'primary', 'int', true, null, null),


		'comp_name' => array(null, 'Company', true, null, 'slo', 'string', false, 'COMPANIES', 'accdef_company', '<b>list</b> Company name', null, null, "<i>[All companies]</i>"),
		'accdef_company' => array(null, '', false, null, 'sloref', 'int', true, null, null),

		'accdef_operation' => array(null, 'Type', true, null, 'text', 'string', true, null, null, '<b>int(1-4)</b> transaction type [1: income, 2: payment]'),


		'accdef_name' => array(null, 'Name', true, null, 'text', 'string', true, null, null, '<b>char(32)</b> operation name, NOTICE: editing or deleteing any <br />entry in this page may cause fatal errors in the system', null, null),

		'__inbound_account' => array(null, 'Inbound account', true, null, 'slo', 'string', false, "ACC_ALL", 'accdef_in_acc_id', '<b>list</b> default inbound account', null, null, "<i>[Registered account]</i>"),
		'accdef_in_acc_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),

		'__outbound_account' => array(null, 'Outbound account', true, null, 'slo', 'string', false, 'ACC_ALL', 'accdef_out_acc_id', '<b>list</b> default outbound account', null, null, "<i>[Registered account]</i>"),
		'accdef_out_acc_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),


		'acc_catdet' => array(null, 'Default Categroy', true, '100%', 'slo', 'string', false, 'ACC_CAT', 'accdef_category', '<b>list</b> default category'),
		'accdef_category' => array(null, '', false, null, 'sloref', 'int', true, null, null),


		//'accdef_cda'=>array(null,'Default Category Action',true,'100%'	,'text'		,'int'	,true	,null	,null		,'<b>int(0-1)</b> specify if selected category should be linked with selected account',null,false),
	),
	'order' => array('accdef_id'),
	'flexablewidth' => array('__inbound_account', '__outbound_account')
	// 'disable-delete'=>true,

);

include ("website-contents/major.editor.php");
use System\SmartListObject;

$SmartListObject = new SmartListObject($app);
?>
<datalist id="js-statement-type">
	<?= $SmartListObject->financialTransactionNature(); ?>
</datalist>