CREATE TABLE IF NOT EXISTS `chat_session` (
	`token` CHAR(40) COLLATE utf8_unicode_ci NOT NULL,
	`user_id` INT(11) NOT NULL,
	`created_at` DATETIME NOT NULL,
	`modifed_at` DATETIME NULL,
	`expiration` DATETIME NOT NULL,
	`ip` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
	`valid` BOOLEAN NOT NULL DEFAULT 1,

	PRIMARY KEY (`token`),
	INDEX `idx_user_id` (`user_id`),
	INDEX `idx_valid` (`valid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;