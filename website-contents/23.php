<?php
$database=array(
'table'=>'labour_section',
'tableselect'=>'labour_section',
'tablename'=>'Labor Sections',

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
	'lsc_id'  =>array(null,'ID'				,true	,null	,'primary'	,'int'	,true	,null	,null),
	'lsc_name'=>array(null,'Name'				,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(32)</b> section name'),
	'lsc_color'=>array(null,'Color'			,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(6)</b> section color representation'),
	'lsc_restriction'=>array(null,'Restriction'	,true	,"100%"	,'text'		,'int'	,true	,null	,null,'<b>int(1)</b> set section restriction behaviour, this will <br />limit access to this section according to user permissions<br />1: full access to all, 9: limit to adminstrators'),	
)
);


include("website-contents/major.editor.php");
?>