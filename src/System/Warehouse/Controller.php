<?php

namespace System\Warehouse;

use System\Warehouse\Goods\Brand;
use System\Warehouse\Goods\Category;
use System\Warehouse\Goods\Goods;
use System\Warehouse\Goods\Group;
use System\Warehouse\Goods\Material;
use System\Warehouse\Goods\Unit;

class Controller
{
	protected \System\App $app;
	public function __construct(\System\App $app)
	{
		$this->app = $app;
	}
	public function create(Goods $good)
	{
	}
	public function loadMaterial(int $material_id): Material|bool
	{

		$r = $this->app->db->query(
			"SELECT 
				mat_id,mat_name ,mat_long_id,mat_longname,
				unt_id, unt_name ,unt_category,unt_decim,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				brand_id, brand_name
			FROM 
				mat_materials 
					JOIN mat_unit ON mat_unt_id = unt_id
					JOIN mat_materialtype ON mat_mattyp_id = mattyp_id
					LEFT JOIN brands ON brand_id = mat_brand_id
					
					JOIN 
						(
							SELECT 
								matcatgrp_name, matcatgrp_id, matcat_name, matcat_id 
							FROM 
								mat_category 
									JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
						) AS _category ON mat_matcat_id=_category.matcat_id
			WHERE
				mat_id = $material_id; "
		);
		if ($r && $r->num_rows > 0) {
			if ($row = $r->fetch_assoc()) {
				$output =  new Material();
				$output->id = $row['mat_id'];
				$output->long_id = $row['mat_long_id'];
				$output->name = $row['mat_name'];
				$output->long_name = $row['mat_longname'];
				$output->unit = new Unit((int)$row['unt_id'], $row['unt_name'], $row['unt_category'], $row['unt_decim']);

				$output->category = new Category((int)$row['matcat_id'], $row['matcat_name']);
				$output->group = new Group((int)$row['matcatgrp_id'], $row['matcatgrp_name']);
				$output->brand = is_null($row['brand_id']) ? null : new Brand((int)$row['brand_id'], $row['brand_name']);
				return $output;
			}
		}

		return false;
	}


	public function WOMaterials($wo_id)
	{
		/* $wo_id = (int)$wo_id;
		$output = array();
		$r = $this->query("
			SELECT 
				mat_id,mat_description,mat_long_id,mat_pn,mattyp_name,mat_date,unt_name,unt_decim,comp_name,wol_qty
			FROM 
			
				mat_materials
					JOIN mat_materialtype ON mattyp_id=mat_type 
					JOIN companies ON comp_id=mat_vendor
					JOIN mat_unit ON mat_unit_id=unt_id
					JOIN mat_wo_list ON wol_item_id = mat_id AND wol_wo_id = $wo_id
			ORDER BY
				mat_bom_level
			");
		if ($r) {
			while ($row = $this->fetch_assoc($r)) {
				$output[$row['mat_id']] = $row;
			}
			return $output;
		} else {
			return false;
		} */
	}

	public function BOMGetNodes($mat_id)
	{
		/* $mat_id = (int)$mat_id;
		$output = array();
		$r = $this->query("
			SELECT 
				mat_id,mat_name,mat_long_id,mattyp_name,mat_date,unt_name,unt_decim,mat_bom_quantity
			FROM 
				mat_materials
					JOIN mat_materialtype ON mattyp_id=mat_mattyp_id 
					JOIN mat_unit ON mat_unt_id=unt_id
					JOIN mat_bom ON mat_bom_part_id=mat_id
			WHERE
				mat_bom_mat_id=$mat_id
			ORDER BY
				mat_bom_level
			;
			");
		if ($r) {
			while ($row = $this->fetch_assoc($r)) {
				$output[$row['mat_id']] = $row;
			}
			return $output;
		} else {
			return false;
		} */
	}
}
