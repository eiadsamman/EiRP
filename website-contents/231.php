<?php
$database=array(
	'table'=>'mat_category ',
	'tableselect'=>'mat_category LEFT JOIN mat_categorygroup ON matcatgrp_id = matcat_matcatgrp_id',
	'tablename'=>'Category Group',

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
		'matcat_id'  			=>array(null,'ID'				,true	,null	,'primary'	,'int'	,true	,null	,null),
		'matcatgrp_name'		=>array(null,'Category Group'	,true	,null	,'slo'		,'string'	,false		,'MAT_CATEGORY_GROUP'	,'matcat_matcatgrp_id'	,'<b>list</b> Group'				),
		'matcat_matcatgrp_id'	=>array(null,''					,false	,null	,'sloref'	,'int'		,true		,null			,null													),
		'matcat_name'			=>array(null,'Name'				,true	,"100%"	,'text'		,'string'	,true	,null	,null,'<b>char(32)</b> category group name'),
	)
);
include("website-contents/major.editor.php");
?>