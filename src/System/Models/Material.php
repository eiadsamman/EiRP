<?php

declare(strict_types=1);

namespace System\Models;

use System\Profiles\BrandProfile;
use System\Profiles\MaterialGategoryProfile;
use System\Profiles\MaterialGroupProfile;
use System\Profiles\MaterialProfile;
use System\Profiles\UnitProfile;


class Material extends MaterialProfile
{
	public function __construct(protected \System\App $app)
	{
	}

	public function load(int $material_id): MaterialProfile|bool
	{
		$r = $this->app->db->execute_query(
			"SELECT 
				mat_id,mat_name, mat_long_id,mat_longname,
				unt_id, unt_name, unt_category,unt_decim,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				brand_id, brand_name, 
				COUNT(mat_bom_id) AS _sub_materials
			FROM 
				mat_materials 
					JOIN mat_unit ON mat_unt_id = unt_id
					JOIN mat_materialtype ON mat_mattyp_id = mattyp_id
					LEFT JOIN brands ON brand_id = mat_brand_id
					LEFT JOIN mat_bom ON mat_bom_mat_id = mat_id
					JOIN 
						(SELECT matcatgrp_name, matcatgrp_id, matcat_name, matcat_id FROM mat_category JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id) 
						AS _category ON mat_matcat_id=_category.matcat_id
			WHERE
				mat_id = ?
			GROUP BY 
				mat_id
			; ",
			[$material_id]
		);

		if ($r && $r->num_rows > 0) {
			if ($row = $r->fetch_assoc()) {
				$output                    = new MaterialProfile();
				$output->id                = (int) $row['mat_id'];
				$output->longId            = (int) $row['mat_long_id'];
				$output->name              = $row['mat_name'];
				$output->longName          = $row['mat_longname'];
				$output->subMaterialsCount = is_null($row['_sub_materials']) ? 0 : (int) $row['_sub_materials'];
				$output->unit              = new UnitProfile((int) $row['unt_id'], $row['unt_name'] ?? "", $row['unt_category'], (int) $row['unt_decim']);
				$output->category = new MaterialGategoryProfile(
					(int) $row['matcat_id'],
					$row['matcat_name'],
					new MaterialGroupProfile((int) $row['matcatgrp_id'], $row['matcatgrp_name'])
				);
				$output->brand    = is_null($row['brand_id']) ? null : new BrandProfile((int) $row['brand_id'], $row['brand_name']);
				return $output;
			}
		}
		return false;
	}

	public function children(int $mat_id): \Generator
	{
		$r = $this->app->db->execute_query(
			"SELECT 
				mat_id,mat_name, mat_long_id,mat_longname,
				unt_id, unt_name, unt_category,unt_decim,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				brand_id, brand_name, mat_bom_quantity
			FROM 
				mat_materials 
					JOIN mat_unit ON mat_unt_id = unt_id
					JOIN mat_materialtype ON mat_mattyp_id = mattyp_id
					JOIN mat_bom ON mat_bom_part_id=mat_id
					LEFT JOIN brands ON brand_id = mat_brand_id
					JOIN (SELECT matcatgrp_name, matcatgrp_id, matcat_name, matcat_id FROM mat_category JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id) 
						AS _category ON mat_matcat_id=_category.matcat_id
			WHERE
				mat_bom_mat_id = ?
			ORDER BY
				mat_bom_level;",
			[
				$mat_id
			]
		);
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				$output             = new MaterialProfile();
				$output->id         = (int) $row['mat_id'];
				$output->longId     = (int) $row['mat_long_id'];
				$output->name       = $row['mat_name'];
				$output->longName   = $row['mat_longname'];
				$output->unit       = new UnitProfile((int) $row['unt_id'], $row['unt_name'] ?? "", $row['unt_category'], (int) $row['unt_decim']);
				$output->bomPortion = (float) $row['mat_bom_quantity'];
				$output->category = new MaterialGategoryProfile(
					(int) $row['matcat_id'],
					$row['matcat_name'],
					new MaterialGroupProfile((int) $row['matcatgrp_id'], $row['matcatgrp_name'])
				);
				$output->brand    = is_null($row['brand_id']) ? null : new BrandProfile((int) $row['brand_id'], $row['brand_name']);
				yield $output;
			}
		}
	}
}
