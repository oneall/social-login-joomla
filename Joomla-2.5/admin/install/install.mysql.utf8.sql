CREATE TABLE IF NOT EXISTS `#__oasl_settings` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`setting` varchar(255) NOT NULL,
	`value` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `setting` (`setting`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__oasl_user_mapping` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` int(11) NOT NULL,
	`token` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	KEY `user_id` (`user_id`),
	UNIQUE KEY `token` (`token`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


INSERT IGNORE INTO `#__oasl_settings` SET
	`setting` = 'api_connection_handler',
	`value` =  'curl';

INSERT IGNORE INTO `#__oasl_settings` SET
	`setting` = 'link_verified_accounts',
	`value` =  '1';

INSERT IGNORE INTO `#__oasl_settings` SET
	`setting` = 'mod_caption',
	`value` =  'Sign in with a social network:';
	
INSERT IGNORE INTO `#__oasl_settings` SET
	`setting` = 'providers',
	`value` =  'a:4:{i:0;s:8:"facebook";i:1;s:7:"twitter";i:2;s:6:"google";i:3;s:8:"linkedin";}';
