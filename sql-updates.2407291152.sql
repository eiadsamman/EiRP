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
	JOIN `labour` on `lbr_id` = `usr_id`
SET
	`usr_registerdate` = `lbr_registerdate`;

ALTER TABLE
	`labour` DROP `lbr_registerdate`,
	DROP `lbr_permanentdate`,
	DROP `lbr_socialinsurance`;

ALTER TABLE
	`users`
ADD
	`usr_role` BIT(3) NOT NULL DEFAULT 1
AFTER
	`usr_id`,
ADD
	`usr_entity` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0'
AFTER
	`usr_role`;

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
	`usr_jobtitle` SMALLINT UNSIGNED NOT NULL
AFTER
	`usr_entity`;

UPDATE
	`users`
	JOIN `labour` ON `usr_id` = `lbr_id`
SET
	`usr_jobtitle` = `lbr_type`;

/* ALTER TABLE `companies`
 DROP `comp_comercialregnumber`,
 DROP `comp_taxnumber`,
 DROP `comp_vatnumber`;
 */
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