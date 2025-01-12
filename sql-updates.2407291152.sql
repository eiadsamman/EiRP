ALTER TABLE `system_prefix` CHANGE `prx_name` `prx_company` MEDIUMINT UNSIGNED NULL DEFAULT NULL;



ALTER TABLE `system_prefix`
DROP INDEX `prx_sector`,
ADD UNIQUE `prx_sector` (`prx_sector`, `prx_enumid`, `prx_company`) USING BTREE;


ALTER TABLE `system_prefix` DROP `prx_id`;