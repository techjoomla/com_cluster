ALTER TABLE `#__tj_clusters` ENGINE = InnoDB;
ALTER TABLE `#__tj_cluster_nodes` ENGINE = InnoDB;

ALTER TABLE `#__tj_clusters` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__tj_cluster_nodes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
