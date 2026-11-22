SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `tg_bot_users` (
	`user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
	`reg_int` int(11) UNSIGNED NOT NULL DEFAULT 0,
	`f_name` text CHARACTER SET utf8mb4 NOT NULL,
	`f_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
	`m_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
	`secret` varchar(50) NOT NULL DEFAULT '',
	`trusted` enum('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `userbot_bind` (
	`platform` enum('vk','tg') NOT NULL DEFAULT 'tg',
	`id_user` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
	`code` varchar(8) NOT NULL DEFAULT '',
	`date` int(11) UNSIGNED NOT NULL DEFAULT 0,
	`id_chat` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (`platform`,`id_user`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
