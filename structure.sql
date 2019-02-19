SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `audit_logs` (
  `user` int(10) UNSIGNED NOT NULL,
  `community` int(10) UNSIGNED DEFAULT NULL,
  `action` tinyint(1) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `bans` (
  `user` int(10) UNSIGNED NOT NULL,
  `ip` varchar(19) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `banned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `length` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` int(10) UNSIGNED DEFAULT NULL,
  `target` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `communities` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(127) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `icon` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `banner` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `owner` int(10) UNSIGNED DEFAULT NULL,
  `permissions` tinyint(1) DEFAULT NULL,
  `privacy` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `community_admins` (
  `user` int(10) UNSIGNED NOT NULL,
  `community` int(10) UNSIGNED NOT NULL,
  `level` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `community_bans` (
  `user` int(10) UNSIGNED NOT NULL,
  `ip` varchar(19) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `community` int(10) UNSIGNED NOT NULL,
  `banned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `length` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `community_favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `community` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `community_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `community` int(10) UNSIGNED NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `empathies` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` int(10) UNSIGNED DEFAULT NULL,
  `target` int(10) UNSIGNED DEFAULT NULL,
  `type` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `follows` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` int(10) UNSIGNED NOT NULL,
  `target` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` int(10) UNSIGNED DEFAULT NULL,
  `target` int(10) UNSIGNED DEFAULT NULL,
  `origin` int(10) UNSIGNED DEFAULT NULL,
  `type` tinyint(1) DEFAULT '0',
  `admin_type` tinyint(1) DEFAULT NULL,
  `admin_reason` int(10) UNSIGNED DEFAULT NULL,
  `merged` int(10) UNSIGNED DEFAULT NULL,
  `seen` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `community` int(10) UNSIGNED DEFAULT NULL,
  `feeling` tinyint(1) DEFAULT '0',
  `body` varchar(2000) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `image` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `yt` varchar(11) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sensitive_content` tinyint(1) DEFAULT '0',
  `tags` varchar(1183) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `post` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `feeling` tinyint(1) DEFAULT '0',
  `body` varchar(2000) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `image` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sensitive_content` tinyint(1) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `reports` (
  `source` int(10) UNSIGNED NOT NULL,
  `target` int(10) UNSIGNED NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `body` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `community` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` int(10) UNSIGNED DEFAULT NULL,
  `value` char(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(32) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `nickname` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `password` binary(60) DEFAULT NULL,
  `avatar` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `has_mh` tinyint(1) DEFAULT NULL,
  `nnid` varchar(16) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mh` varchar(13) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `level` tinyint(1) DEFAULT '0',
  `organization` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `profile_comment` varchar(2000) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `favorite_post` int(10) UNSIGNED DEFAULT NULL,
  `yeah_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT '0',
  `ip` varchar(39) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


ALTER TABLE `bans`
  ADD PRIMARY KEY (`user`);

ALTER TABLE `blocks`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `community_admins`
  ADD UNIQUE KEY `community_admins_uk_1` (`user`,`community`);

ALTER TABLE `community_favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_favorites_ibfk_1` (`user`),
  ADD KEY `community_favorites_ibfk_2` (`community`);

ALTER TABLE `community_members`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `empathies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source` (`source`,`target`,`type`);

ALTER TABLE `follows`
  ADD PRIMARY KEY (`source`,`target`),
  ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_ibfk_1` (`merged`);

ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `replies`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `reports`
  ADD UNIQUE KEY `reports_uk_1` (`source`,`target`,`type`);

ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `communities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `community_favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `community_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `empathies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `follows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;


ALTER TABLE `community_favorites`
  ADD CONSTRAINT `community_favorites_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `community_favorites_ibfk_2` FOREIGN KEY (`community`) REFERENCES `communities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`merged`) REFERENCES `notifications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
