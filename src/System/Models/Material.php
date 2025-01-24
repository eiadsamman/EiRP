<?php

declare(strict_types=1);

namespace System\Models;

use System\Profiles\BrandProfile;
use System\Profiles\MaterialGategoryProfile;
use System\Profiles\MaterialGroupProfile;
use System\Profiles\MaterialPartProfile;
use System\Profiles\MaterialProfile;
use System\Profiles\MaterialTypeProfile;
use System\Profiles\UnitProfile;


class Material
{
	public function __construct(protected \System\App $app)
	{
	}

	public function load(int $material_id): MaterialProfile|bool
	{
		$r = $this->app->db->execute_query(
			"SELECT 
				mat_id,mat_name, mat_long_id,mat_longname,mat_perbox,mat_ean,mat_unitsystem,mat_date,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				mattyp_name,mattyp_description,
				brand_id, brand_name, 
				COUNT(mat_bom_id) AS _sub_materials
			FROM 
				mat_materials 
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

		if ($r && $r->num_rows > 0 && $row = $r->fetch_assoc()) {
			$output                    = new MaterialProfile();
			$output->id                = (int) $row['mat_id'];
			$output->longId            = (int) $row['mat_long_id'];
			$output->name              = $row['mat_name'] ?? "";
			$output->longName          = $row['mat_longname'] ?? "";
			$output->subMaterialsCount = is_null($row['_sub_materials']) ? 0 : (int) $row['_sub_materials'];
			$output->unitSystem        = \System\enums\UnitSystem::tryFrom((int) $row['mat_unitsystem']);
			$output->creationDate      = new \DateTime((string) $row['mat_date']);
			$output->type              = new MaterialTypeProfile((string) $row['mattyp_name'], (string) $row['mattyp_description']);

			$output->unitsPerBox = $row['mat_perbox'] === null ? 0 : (float) $row['mat_perbox'];
			$output->eanCode     = $row['mat_ean'] ?? "";
			$output->category    = new MaterialGategoryProfile(
				(int) $row['matcat_id'],
				$row['matcat_name'],
				new MaterialGroupProfile((int) $row['matcatgrp_id'], $row['matcatgrp_name'])
			);
			$output->brand       = null;
			if ($row['brand_id'] !== null) {
				$output->brand = new BrandProfile((int) $row['brand_id'], $row['brand_name']);
				//$this->loadBrandLogos($output->brand);
			}
			return $output;
		}
		return false;
	}

	private function loadBrandLogos(BrandProfile $brandProfile)
	{
		$exec = $this->app->db->execute_query(
			"SELECT 
					up_id, up_name, up_size, up_date, up_pagefile, up_mime, up_rel 
				FROM 
					uploads 
				WHERE 
					up_rel=?
					AND up_deleted = 0 
					AND up_pagefile = ?
				ORDER BY 
					up_rel DESC, up_date DESC;",
			[
				$brandProfile->id,
				\System\Attachment\Type::BrandLogo->value
			]
		);
		if ($exec) {
			$brandProfile->attachments = [];
			while ($row = $exec->fetch_assoc()) {
				//$brandProfile->attachments[$ro['']]=new \System\Attachment\File();
			}
		}
	}

	public function parts(int $mat_id): \Generator
	{
		$r = $this->app->db->execute_query(
			"SELECT 
				mat_id,mat_name, mat_long_id,mat_longname,mat_unitsystem,mat_date,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				brand_id, brand_name, 
				mattyp_name,mattyp_description,
				mat_bom_quantity, mat_bom_unitsystem, mat_bom_unit, mat_bom_tolerance
			FROM 
				mat_materials 
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
				$output               = new MaterialPartProfile();
				$output->id           = (int) $row['mat_id'];
				$output->longId       = (int) $row['mat_long_id'];
				$output->bomPortion   = (float) $row['mat_bom_quantity'];
				$output->name         = $row['mat_name'] ?? "";
				$output->longName     = $row['mat_longname'] ?? "";
				$output->quantity     = (float) $row['mat_bom_quantity'];
				$output->unitSystem   = \System\enums\UnitSystem::tryFrom((int) $row['mat_bom_unitsystem']);
				$output->creationDate = new \DateTime((string) $row['mat_date']);
				$output->type         = new MaterialTypeProfile((string) $row['mattyp_name'], (string) $row['mattyp_description']);
				$output->unit         = $this->app->unit->getUnit((int) $row['mat_bom_unitsystem'], (int) $row['mat_bom_unit']);
				;

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
