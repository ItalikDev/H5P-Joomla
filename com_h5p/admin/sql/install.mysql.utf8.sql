DROP TABLE IF EXISTS `#__h5p_contents`;

CREATE TABLE `#__h5p_contents` (
  `id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_id` int(10) UNSIGNED NOT NULL,
  `parameters` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `filtered` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `embed_type` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disable` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `content_type` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authors` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(2083) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_from` int(10) UNSIGNED DEFAULT NULL,
  `year_to` int(10) UNSIGNED DEFAULT NULL,
  `license` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_version` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_extras` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_comments` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changes` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_language` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `a11y_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_contents_libraries`;

CREATE TABLE `#__h5p_contents_libraries` (
  `content_id` int(10) UNSIGNED NOT NULL,
  `library_id` int(10) UNSIGNED NOT NULL,
  `dependency_type` varchar(31) NOT NULL,
  `weight` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `drop_css` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__h5p_contents_tags`;

CREATE TABLE `#__h5p_contents_tags` (
  `content_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_contents_user_data`;

CREATE TABLE `#__h5p_contents_user_data` (
  `content_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `sub_content_id` int(10) UNSIGNED NOT NULL,
  `data_id` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `preload` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `invalidate` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_counters`;

CREATE TABLE `#__h5p_counters` (
  `type` varchar(63) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_version` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_events`;

CREATE TABLE `#__h5p_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  `type` varchar(63) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_type` varchar(63) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `content_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_version` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_libraries`;

CREATE TABLE `#__h5p_libraries` (
  `id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `major_version` int(10) UNSIGNED NOT NULL,
  `minor_version` int(10) UNSIGNED NOT NULL,
  `patch_version` int(10) UNSIGNED NOT NULL,
  `runnable` int(10) UNSIGNED NOT NULL,
  `restricted` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `fullscreen` int(10) UNSIGNED NOT NULL,
  `embed_types` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preloaded_js` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preloaded_css` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drop_library_css` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semantics` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tutorial_url` varchar(1023) COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_icon` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `metadata_settings` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `add_to` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_libraries_cachedassets`;

CREATE TABLE `#__h5p_libraries_cachedassets` (
  `library_id` int(10) UNSIGNED NOT NULL,
  `hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_libraries_hub_cache`;

CREATE TABLE `#__h5p_libraries_hub_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `machine_name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `major_version` int(10) UNSIGNED NOT NULL,
  `minor_version` int(10) UNSIGNED NOT NULL,
  `patch_version` int(10) UNSIGNED NOT NULL,
  `h5p_major_version` int(10) UNSIGNED DEFAULT NULL,
  `h5p_minor_version` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(511) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  `updated_at` int(10) UNSIGNED NOT NULL,
  `is_recommended` int(10) UNSIGNED NOT NULL,
  `popularity` int(10) UNSIGNED NOT NULL,
  `screenshots` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `example` varchar(511) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tutorial` varchar(511) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categories` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner` varchar(511) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_libraries_languages`;

CREATE TABLE `#__h5p_libraries_languages` (
  `library_id` int(10) UNSIGNED NOT NULL,
  `language_code` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_libraries_libraries`;

CREATE TABLE `#__h5p_libraries_libraries` (
  `library_id` int(10) UNSIGNED NOT NULL,
  `required_library_id` int(10) UNSIGNED NOT NULL,
  `dependency_type` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_results`;

CREATE TABLE `#__h5p_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `content_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `score` int(10) UNSIGNED NOT NULL,
  `max_score` int(10) UNSIGNED NOT NULL,
  `opened` int(10) UNSIGNED NOT NULL,
  `finished` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_settings`;

CREATE TABLE `#__h5p_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_name` varchar(191) NOT NULL,
  `setting_value` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `#__h5p_tags`;

CREATE TABLE `#__h5p_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_tmpfiles`;

CREATE TABLE `#__h5p_tmpfiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `#__h5p_content_hub_metadata_cache`;

CREATE TABLE #__h5p_content_hub_metadata_cache (
  language varchar(31) DEFAULT NULL COMMENT 'Language of metadata',
  json longtext DEFAULT NULL COMMENT 'Metadata as JSON',
  last_checked int(11) DEFAULT NULL COMMENT 'Last time metadata was checked.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='JSON representation of the content hub metadata cache';


ALTER TABLE `#__h5p_contents`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `#__h5p_contents_libraries`
  ADD PRIMARY KEY (`content_id`,`library_id`,`dependency_type`) USING BTREE;

ALTER TABLE `#__h5p_contents_tags`
  ADD PRIMARY KEY (`content_id`,`tag_id`);

ALTER TABLE `#__h5p_contents_user_data`
  ADD PRIMARY KEY (`content_id`,`user_id`,`sub_content_id`,`data_id`);

ALTER TABLE `#__h5p_counters`
  ADD PRIMARY KEY (`type`,`library_name`,`library_version`);

ALTER TABLE `#__h5p_events`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `#__h5p_libraries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name_version` (`name`,`major_version`,`minor_version`,`patch_version`),
  ADD KEY `runnable` (`runnable`);

ALTER TABLE `#__h5p_libraries_cachedassets`
  ADD PRIMARY KEY (`library_id`,`hash`);

ALTER TABLE `#__h5p_libraries_hub_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name_version` (`machine_name`,`major_version`,`minor_version`,`patch_version`);

ALTER TABLE `#__h5p_libraries_languages`
  ADD PRIMARY KEY (`library_id`,`language_code`);

ALTER TABLE `#__h5p_libraries_libraries`
  ADD PRIMARY KEY (`library_id`,`required_library_id`);

ALTER TABLE `#__h5p_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_user` (`content_id`,`user_id`);

ALTER TABLE `#__h5p_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

ALTER TABLE `#__h5p_tags`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `#__h5p_tmpfiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`);


ALTER TABLE `#__h5p_contents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_libraries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_libraries_hub_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `#__h5p_tmpfiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
  
INSERT INTO `#__h5p_settings` (`id`, `setting_name`, `setting_value`) VALUES
(1, 'h5p_frame', '1'),
(2, 'h5p_export', '1'),
(3, 'h5p_embed', '1'),
(4, 'h5p_copyright', '1'),
(5, 'h5p_icon', '1'),
(6, 'h5p_track_user', '1'),
(7, 'h5p_save_content_state', '0'),
(8, 'h5p_save_content_frequency', '30'),
(9, 'h5p_site_key', ''),
(10, 'h5p_show_toggle_view_others_h5p_contents', '0'),
(11, 'h5p_content_type_cache_updated_at', ''),
(12, 'h5p_check_h5p_requirements', '0'),
(13, 'h5p_hub_is_enabled', '0'),
(14, 'h5p_send_usage_statistics', '0'),
(15, 'h5p_has_request_user_consent', '0')
