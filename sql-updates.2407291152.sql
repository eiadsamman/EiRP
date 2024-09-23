ALTER TABLE
	`users` DROP `usr_activation_code`,
	DROP `usr_login_date`,
	DROP `usr_login_ip`,
	DROP `usr_identifier`,
	DROP `usr_attrib_s1`,
	DROP `usr_attrib_s2`,
	DROP `usr_attrib_s3`,
	DROP `usr_attrib_i1`,
	DROP `usr_attrib_i2`,
	DROP `usr_attrib_i3`,
	DROP `usr_attrib_i4`,
	DROP `usr_secret_question`,
	DROP `usr_secret_answer`,
	DROP `usr_webpage_list`,
	DROP `usr_images_list`,
	DROP `usr_zipcode`,
	DROP `usr_attrib_i5`;


ALTER TABLE
	`acc_main` CHANGE `acm_party` `acm_party` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL;


UPDATE
	`acc_main`
SET
	`acm_party` = NULL;


ALTER TABLE
	`labour` DROP `lbr_fixedtime`,
	DROP `lbr_workingdays`,
	DROP `lbr_smoker`,
	DROP `lbr_married`,
	DROP `lbr_military`;


ALTER TABLE
	`users` CHANGE `usr_regdate` `usr_registerdate` DATE NULL DEFAULT NULL;


UPDATE
	`users`
	JOIN `labour` ON `lbr_id` = `usr_id`
SET
	`usr_registerdate` = `lbr_registerdate`;


ALTER TABLE
	`labour` DROP `lbr_registerdate`,
	DROP `lbr_permanentdate`,
	DROP `lbr_socialinsurance`;


ALTER TABLE
	`users`
ADD
	`usr_role` BIT(3) NOT NULL DEFAULT 1 AFTER `usr_id`,
ADD
	`usr_entity` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0' AFTER `usr_role`;


UPDATE
	`users`
	JOIN `labour` ON `lbr_id` = `usr_id`
SET
	`usr_role` = `lbr_role`,
	`usr_entity` = `lbr_company`;


ALTER TABLE
	`labour` DROP `lbr_role`,
	DROP `lbr_company`;


ALTER TABLE
	`users`
ADD
	`usr_jobtitle` SMALLINT UNSIGNED NOT NULL AFTER `usr_entity`;


UPDATE
	`users`
	JOIN `labour` ON `usr_id` = `lbr_id`
SET
	`usr_jobtitle` = `lbr_type`;


ALTER TABLE
	`companies`
ADD
	`comp_city` VARCHAR(32) NULL DEFAULT NULL AFTER `comp_country`,
ADD
	`comp_latitude` DECIMAL(12, 8) NULL DEFAULT NULL AFTER `comp_city`,
ADD
	`comp_longitude` DECIMAL(12, 8) NULL DEFAULT NULL AFTER `comp_latitude`;


CREATE TABLE `acc_bankaccount` (
	`bnkacc_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`bnkacc_type` TINYINT UNSIGNED NOT NULL COMMENT '1:company,2:account,3:individual',
	`bnkacc_number` VARCHAR(32) NOT NULL,
	`bnkacc_bankname` VARCHAR(64) NOT NULL,
	`bnkacc_holdername` VARCHAR(64) NOT NULL,
	`bnkacc_currency_id` SMALLINT UNSIGNED NOT NULL,
	`bnkacc_iban` VARCHAR(34) NULL DEFAULT NULL,
	`bnkacc_swift` VARCHAR(16) NULL DEFAULT NULL,
	`bnkacc_created_at` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`bnkacc_owner_id` MEDIUMINT UNSIGNED NOT NULL,
	PRIMARY KEY (`bnkacc_id`)
) ENGINE = InnoDB;


ALTER TABLE
	`acc_bankaccount` AUTO_INCREMENT = 1000;


CREATE TABLE `companies_legal` (
	`commercial_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`commercial_companyId` MEDIUMINT UNSIGNED NOT NULL,
	`commercial_legalName` VARCHAR(128) NOT NULL,
	`commercial_registrationNumber` VARCHAR(64) NOT NULL,
	`commercial_creationDate` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`commercial_issuingDate` DATE NULL,
	`commercial_expirationDate` DATE NULL,
	`commercial_taxNumber` VARCHAR(64) NOT NULL,
	`commercial_taxExpirationDate` DATE NULL,
	`commercial_vatNumber` VARCHAR(64) NOT NULL,
	`commercial_vatExpirationDate` DATE NULL,
	`commercial_default` BOOLEAN NULL,
	PRIMARY KEY (`commercial_id`)
) ENGINE = InnoDB;


ALTER TABLE
	`companies_legal` AUTO_INCREMENT = 1000;


CREATE TABLE `timeline` (
	`tl_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`tl_module` MEDIUMINT UNSIGNED NOT NULL,
	`tl_action` SMALLINT UNSIGNED NOT NULL,
	`tl_owner` INT UNSIGNED NOT NULL,
	`tl_issuer` INT UNSIGNED NOT NULL,
	`tl_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`tl_json` JSON NULL,
	`tl_message` TEXT NULL,
	`tl_parent` INT UNSIGNED NULL DEFAULT NULL AFTER `tl_message`,
	`tl_remind_date` DATETIME NULL DEFAULT NULL AFTER `tl_parent`,
	PRIMARY KEY (`tl_id`)
) ENGINE = InnoDB;


CREATE TABLE `timeline_track` (
	`tlrk_tl_id` INT UNSIGNED NOT NULL,
	`tlrk_type` TINYINT UNSIGNED NOT NULL,
	`tlrk_usr_id` MEDIUMINT UNSIGNED NOT NULL,
	`tlrk_mention_id` MEDIUMINT UNSIGNED NULL,
	`tlrk_isread` BOOLEAN NOT NULL DEFAULT '0'
) ENGINE = InnoDB;


ALTER TABLE
	`timeline`
ADD
	INDEX (`tl_id`, `tl_module`);


ALTER TABLE
	`acc_accounts` DROP `prt_ale`,
	DROP `prt_current`;


DROP TABLE `acc_termgroup`;


UPDATE
	pagefile
SET
	trd_visible = 0,
	trd_enable = 0
WHERE
	trd_id = 262
	OR trd_id = 11;


DROP TABLE `acc_transtypes`;


ALTER TABLE
	`acc_accounts`
ADD
	`prt_term` MEDIUMINT UNSIGNED NULL DEFAULT NULL AFTER `prt_name`;


/* view_financial_accounts VIEW */
SELECT
	`acc_accounts`.`prt_id` AS `prt_id`,
	`acc_accounts`.`prt_name` AS `prt_name`,
	`acc_accounts`.`prt_term` AS `prt_term`,
	`companies`.`comp_name` AS `comp_name`,
	`companies`.`comp_id` AS `comp_id`,
	`currencies`.`cur_id` AS `cur_id`,
	`currencies`.`cur_name` AS `cur_name`,
	`currencies`.`cur_shortname` AS `cur_shortname`,
	`currencies`.`cur_symbol` AS `cur_symbol`
FROM
	`acc_accounts`
	JOIN `currencies` ON (
		`currencies`.`cur_id` = `acc_accounts`.`prt_currency`
	)
	JOIN `companies` ON (
		`acc_accounts`.`prt_company_id` = `companies`.`comp_id`
	);