CREATE TABLE IF NOT EXISTS `#__tj_clusters` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `params` text NOT NULL DEFAULT '',
  `client` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `client_id` int(11) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `checked_out` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE = MyISAM CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `#__tj_cluster_nodes` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to the #__tj_clusters table.',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `checked_out` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cluster_id`) REFERENCES `#__tj_clusters` (`id`)
) ENGINE = MyISAM CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

--
-- Indexes for table `__tj_cluster_nodes`
--

ALTER TABLE `#__tj_cluster_nodes`
  ADD UNIQUE `unqk_cluster_user_pair` (`cluster_id`, `user_id`);
