-- 1.0-beta1

-- remove default timezone
ALTER TABLE  `playsms_tblUser` CHANGE  `datetime_timezone`  `datetime_timezone` VARCHAR( 30 ) NOT NULL DEFAULT  '';

-- remove default language
ALTER TABLE  `playsms_tblUser` CHANGE  `language_module`  `language_module` VARCHAR( 10 ) NOT NULL DEFAULT  '';

-- phonebook
DROP TABLE IF EXISTS `playsms_toolsPhonebook_group_contacts`;
CREATE TABLE IF NOT EXISTS `playsms_toolsPhonebook_group_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gpid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `playsms_toolsPhonebook` DROP `gpid` ;

-- core config
INSERT INTO `playsms_tblRegistry` (`uid`, `registry_group`, `registry_family`, `registry_key`, `registry_value`) VALUES ('1', 'core', 'config', 'playsms_version', '1.0-beta1');


-- 1.0

-- core config
DELETE FROM `playsms_tblRegistry` WHERE `registry_group` = 'core' AND `registry_family` = 'config' AND `registry_key` = 'playsms_version' ;
INSERT INTO `playsms_tblRegistry` (`uid`, `registry_group`, `registry_family`, `registry_key`, `registry_value`) VALUES ('1', 'core', 'config', 'playsms_version', '1.0') ;
