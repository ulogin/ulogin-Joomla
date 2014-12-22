CREATE TABLE IF NOT EXISTS `#__ulogin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `identity` varchar(255) NOT NULL,
  `network` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX (`user_id`)
) DEFAULT CHARSET=utf8;

