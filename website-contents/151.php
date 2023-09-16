<?php
$database = array(
	'table' => 'calendar',
	'tableselect' => 'calendar
				LEFT JOIN calendar_operations ON cal_op=cop_id
				LEFT JOIN users AS users_editor ON cal_editor = users_editor.usr_id
				LEFT JOIN users AS users_owner ON cal_owner = users_owner.usr_id',
	'tablename' => 'Calendar',
	'fields' => array(
		'cal_id'  	=> array(null, 'ID', true, null, 'primary', 'int', true, null, null),
		'cal_date'	=> array(null, 'Date', true, null, 'slo', 'string', true, 'WIDE_DATE', 'cal_date', '<b>date</b> calendar start date'),
		'cop_name'	=> array(null, 'Type', true, null, 'slo', 'string', false, 'CALENDAR_OPERATIONS', 'cal_op', '<b>list</b> material type'),
		'cal_op'	=> array(null, '', false, null, 'sloref', 'int', true, null, null),
		'cal_details' => array(null, 'Details', true, null, 'text', 'string', true, null, null, '<b>char(255)</b> calendar record details'),
		'cal_period' => array(null, 'Period', true, null, 'text', 'int', true, null, null, '<b>int(4)</b> calendar record period in days'),
		'cal_yearly' => array(null, 'Yearly', true, null, 'bool', 'int', true, null, null, '<b>bool</b> repeat calendar record yearly in georgian calendar'),
		'usr_id'	=> array(" IF( users_owner.usr_id IS NULL, '[system]' , CONCAT_WS(' ',COALESCE(users_owner.usr_firstname,''),IF(NULLIF(users_owner.usr_lastname, '') IS NULL, NULL, users_owner.usr_lastname)) ) ", 'Owner', true, null, 'slo', 'string', false, 'USERS', 'cal_owner', '<b>int(9)</b> calendar record owner [0:system, #:user]'),
		'cal_owner'	=> array(null, '', false, null, 'sloref', 'int', true, null, null),

		'cal_editor' => array(null, 'Editor', false, null, 'default', 'int', true, null, null, null, $app->user->info->id, false),
		'usr_editor' => array("CONCAT_WS(' ',COALESCE(users_editor.usr_firstname,''),IF(NULLIF(users_editor.usr_lastname, '') IS NULL, NULL, users_editor.usr_lastname))", 'Editor', true, "100%", 'hidden', 'string', false, null, null, null, '', false),

	),
	'order' => array('cal_date')
);

include("website-contents/major.editor.php");
