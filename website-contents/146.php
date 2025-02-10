<?php
// CONCAT(IF(comp_sys_default=1,110,310),mattyp_id,LPAD(mat_id,6,'0'),0) AS unique_id

$database = array(
	'table' => 'mat_materials',
	'tableselect' => "mat_materials 
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
	'tablename' => 'Materials List',


	'fields' => array(
		'mat_id' => array(null, 'ID', true, null, 'primary', 'int', true, null, null),
		'mat_name' => array(null, 'Name', true, null, 'text', 'string', true, null, null, '<b>text</b> material short name'),
		'mat_long_id' => array(null, 'Part Number', true, null, 'text', 'string', false, null, null, '<b>char(32)</b> part number'),

		'file_image' => array(null, 'image', true, null, 'file', \System\Lib\Upload\Type::Material->value, true, null, null, '<b>file</b> Product images'),

		'mattyp_name' => array(null, 'Type', true, null, 'slo', 'string', false, 'MAT_TYPE', 'mat_mattyp_id', '<b>list</b> type'),
		'mat_mattyp_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),


		'cat_alias' => array(null, 'Category', true, null, 'slo', 'string', false, 'MAT_CATEGORY', 'mat_matcat_id', '<b>list</b> category'),
		'mat_matcat_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),

		'brand_name' => array(null, 'Brand', true, null, 'slo', 'string', false, 'BRANDS', 'mat_brand_id', '<b>list</b> type'),
		'mat_brand_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),

		'mat_unitsystem' => array(null, 'Unit', true, null, 'enum', 'int', true, \System\Enum\UnitSystem::class, '_/Units/slo/' . md5($app->id . $app->user->company->id) . '/slo_Units.a', ''),

		'mat_featured' => array(null, 'Featured', true, null, 'bool', 'int', true, null, null, '<b>bool</b> repeat calendar record yearly in georgian calendar'),
		'mat_ispackage' => array(null, 'Packing', true, null, 'bool', 'int', true, null, null, '<b>bool</b> repeat calendar record yearly in georgian calendar'),

		//'mat_perbox' => array(null, 'Units per box', false, null, 'text', 'string', true, null, null, '<b>float</b> Units per box'),

		'mat_ean' => array(null, 'EAN Code', false, null, 'text', 'string', true, null, null, '<b>float</b> EAN Code'),

		'mat_date' => array("DATE_FORMAT(mat_date, '%Y-%m-%d')", 'Date', false, null, 'hidden', 'string', false, null, null, null, '', false),

		'mat_dim_w' => array(null, 'Width', false, null, 'text', 'string', true, null, null, '<b>float</b> width'),
		'mat_dim_l' => array(null, 'Length', false, null, 'text', 'string', true, null, null, '<b>float</b> length'),
		'mat_dim_h' => array(null, 'Height', false, null, 'text', 'string', true, null, null, '<b>float</b> height'),

		'mat_wight_net' => array(null, 'Net weight', false, null, 'text', 'string', true, null, null, '<b>float</b> net weight'),
		'mat_wight_gross' => array(null, 'Gross weight', false, null, 'text', 'string', true, null, null, '<b>float</b> gross weight'),

		'mat_longname' => array(null, 'Description', true, "100%", 'textarea', 'string', true, null, null, '<b>text</b> material long name'),

	),
	'order' => array('mat_id'),
	'flexablewidth' => array("mat_longname"),
	'search' => array(),
	'perpage' => 25,
	'post_submit_functions' => array(
		"update_part_number" => function ($input, &$app, $row) {
			$app->db->query("UPDATE mat_materials 
						LEFT JOIN mat_materialtype ON mat_mattyp_id=mattyp_id
						LEFT JOIN mat_category ON mat_matcat_id=matcat_id
						
					SET mat_long_id=CONCAT(LPAD(mattyp_id,2,'0'), LPAD(matcat_id,3,'0'), LPAD(mat_id,6,'0'), '0') WHERE mat_id=$row");
		}
	),
);



include("website-contents/major.editor.php");

//Search for materials by ID and their owner packing material
/*
SELECT b.mat_long_id, b.mat_name, a.mat_long_id, a.mat_name FROM 
	
	`mat_bom`  
		JOIN `mat_materials` a ON a.mat_id = mat_bom_mat_id AND a.mat_ispackage = 1 AND a.mat_reversable = 1
		JOIN `mat_materials` b ON b.mat_id = `mat_bom_part_id` 
WHERE
	b.mat_long_id='121270000010' 

UNION 

SELECT b.mat_long_id, b.mat_name, NULL, NULL
FROM
	`mat_materials` b
WHERE
	b.mat_long_id='121270000010' 
	
	*/


//Search for featured materials and their owner packing material
/* SELECT b.mat_long_id, b.mat_name, a.mat_long_id, a.mat_name FROM 
   
   `mat_bom`  
	   JOIN `mat_materials` a ON a.mat_id = mat_bom_mat_id AND a.mat_ispackage = 1 AND a.mat_reversable = 1
	   JOIN `mat_materials` b ON b.mat_id = `mat_bom_part_id` 
WHERE
   b.mat_featured=1

UNION 

SELECT b.mat_long_id, b.mat_name, NULL, NULL
FROM
   `mat_materials` b
WHERE
   b.mat_featured=1 */


/* 
When printing labels I can use the mat_ispacking to print masters
OR
Print individual material labels (Different template)
 */