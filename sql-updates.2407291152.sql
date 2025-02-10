ALTER TABLE `mat_materials` CHANGE `mat_unt_id` `mat_unt_id` SMALLINT(3) UNSIGNED NULL DEFAULT NULL;



UPDATE `mat_materials`
SET
	`mat_unt_id`=1010;



ALTER TABLE `mat_bom` ADD `mat_bom_unitsystem` SMALLINT UNSIGNED NOT NULL AFTER `mat_bom_level`,
ADD `mat_bom_unit` SMALLINT UNSIGNED NOT NULL AFTER `mat_bom_unit_id`,
ADD `mat_bom_tolerance` DECIMAL(6, 3) NOT NULL AFTER `mat_bom_unitmeas_id`;



ALTER TABLE `mat_materials` CHANGE `mat_unt_id` `mat_unitsystem` SMALLINT(3) UNSIGNED NULL DEFAULT NULL;



ALTER TABLE `inv_records` ADD `pols_unitsystem` SMALLINT UNSIGNED NOT NULL AFTER `pols_price`,
ADD `pols_unit` SMALLINT UNSIGNED NOT NULL AFTER `pols_unitsystem`;





