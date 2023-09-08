<?php
// CONCAT(IF(comp_sys_default=1,110,310),mattyp_id,LPAD(mat_id,6,'0'),0) AS unique_id

$database=array(
'table'=>'mat_materials',
'tableselect'=>"mat_materials 
						LEFT JOIN mat_unit ON mat_unt_id=unt_id
						LEFT JOIN mat_materialtype ON mat_mattyp_id=mattyp_id
						LEFT JOIN brands ON brand_id = mat_brand_id
						
						LEFT JOIN 
							(
								SELECT 
									CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
								FROM 
									mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
							) AS _category ON mat_matcat_id=_category.matcat_id
					",
'tablename'=>'Materials List',


'fields'=>array(
	'mat_id'=>array(null,'ID'					,true	,null	,'primary'	,'int'		,true		,null			,null													),
	'mat_long_id'=>array(null,'Part Number'		,true	,null	,'text'	,'string'	,false		,null			,null			,'<b>char(32)</b> part number'	),
	
	'file_image'=>array(null,'image'			,true	,null	,'file'		,'243'		,true	,null	,null,'<b>file</b> Product images'),
	
	'mattyp_name'=>array(null,'Type'			,true	,null	,'slo'		,'string'	,false		,'MAT_TYPE'		,'mat_mattyp_id'		,'<b>list</b> type'				),
	'mat_mattyp_id'=>array(null,''				,false	,null	,'sloref'	,'int'		,true		,null			,null													),
	
	
	'cat_alias'=>array(null,'Category'			,true	,null	,'slo'		,'string'	,false		,'MAT_CATEGORY'	,'mat_matcat_id'	,'<b>list</b> category'			),
	'mat_matcat_id'=>array(null,''				,false	,null	,'sloref'	,'int'		,true		,null			,null													),

	'brand_name'=>array(null,'Brand'			,true	,null	,'slo'		,'string'	,false		,'BRANDS'		,'mat_brand_id'		,'<b>list</b> type'				),
	'mat_brand_id'=>array(null,''				,false	,null	,'sloref'	,'int'		,true		,null			,null													),
	
	
	'mat_name'=>array(null,'Name'				,true	,null	,'text' 	,'string'	,true		,null			,null			,'<b>text</b> material short name'),
	
	'unt_name'=>array(null,'Unit'				,true	,null	,'slo'		,'string'	,false		,'UNITS'		,'mat_unt_id'	,'<b>list</b> type'			),
	'mat_unt_id'=>array(null,''					,false	,null	,'sloref'	,'int'		,true		,null			,null													),
	
	'mat_date'=>array("DATE_FORMAT(mat_date, '%Y-%m-%d')",'Date',false   ,null 	,'hidden'	,'string'   ,false  	,null  	 		,null			,null,'',false),
	
	'mat_dim_w'=>array(null,'Width'				,false	,null	,'text' 	,'string'	,true		,null			,null			,'<b>float</b> width'),
	'mat_dim_l'=>array(null,'Length'			,false	,null	,'text' 	,'string'	,true		,null			,null			,'<b>float</b> length'),
	'mat_dim_h'=>array(null,'Height'			,false	,null	,'text' 	,'string'	,true		,null			,null			,'<b>float</b> height'),
	
	'mat_wight_net'=>array(null,'Net weight'	,false	,null	,'text' 	,'string'	,true		,null			,null			,'<b>float</b> net weight'),
	'mat_wight_gross'=>array(null,'Gross weight',false	,null	,'text' 	,'string'	,true		,null			,null			,'<b>float</b> gross weight'),
	
	'mat_longname'=>array(null,'Description'	,true	,"100%"	,'textarea' ,'string'	,true		,null			,null			,'<b>text</b> material long name'),
	
),
'order'=>array('mat_id'),
'flexablewidth'=>array("mat_longname"),
'search'=>array(),
'perpage'=>25,
'post_submit_functions'=>array(
	"update_part_number"=>function($input,$sql,$user,$row){
		$sql->query("UPDATE mat_materials 
						LEFT JOIN mat_materialtype ON mat_mattyp_id=mattyp_id
						LEFT JOIN mat_category ON mat_matcat_id=matcat_id
						
					SET mat_long_id=CONCAT(LPAD(mattyp_id,2,'0'), LPAD(matcat_id,3,'0'), LPAD(mat_id,6,'0'), '0') WHERE mat_id=$row");
	}
),
);

include("website-contents/major.editor.php");
	

?>

