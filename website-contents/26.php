<?php
$database=array(
'table'=>'labour_residentail',
'tableselect'=>'labour_residentail',
'tablename'=>'Labor Residential',

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
	'ldn_id'  		=>array(null,'ID'		,true	,null	,'primary'	,'int'		,true	,null	,null),
	'ldn_name'		=>array(null,'Name'		,true	,null	,'text'		,'string'	,true	,null	,null,'<b>char(32)</b> location name'),
	'ldn_distance'		=>array(null,'Distance'	,true	,"100%"	,'text'		,'int'	,true	,null	,null ,'<b>int(4)</b> distance from factory'),
)
);


include("website-contents/major.editor.php");
?>