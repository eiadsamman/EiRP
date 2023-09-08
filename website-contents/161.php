<?php
$database=array(
'table'=>'mat_bom_schematic',
'tableselect'=>'mat_bom_schematic 	
				LEFT JOIN mat_materialtype ON mat_sch_type_id = mattyp_id
					',
'tablename'=>'BOM Schematic',

'fields'=>array(
	'mat_sch_id' 		=>array(null,'ID'			,true	,null	,'primary'	,'int'	,true	,null	,null		),
	'mat_sch_level' 	=>array(null,'Level'		,true	,null	,'text'		,'int'	,true	,null	,null	,'<b>int</b><br />BOM Level: <br />0:Root/Finished Goods<br />1:Semi-finished<br />2:Raw/Consumables'),
	
	'mattyp_description'		=>array(null,'Type'			,true	,'100%'	,'slo'		,'string'	,false	,'MAT_TYPE'	,'mat_sch_type_id'	,'<b>list</b> Allowed meterial type'),
	'mat_sch_type_id'	=>array(null,''			,false	,null	,'sloref'		,'int'	,true	,null	,null		),
	
),
'readonly'=>false,
);

include("website-contents/major.editor.php");
?>