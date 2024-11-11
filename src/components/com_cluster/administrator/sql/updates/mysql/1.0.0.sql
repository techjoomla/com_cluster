ALTER TABLE #__tj_clusters ADD INDEX client_id_idx (client_id);
ALTER TABLE #__tj_cluster_nodes ADD INDEX cluster_id_idx (cluster_id);
ALTER TABLE #__tj_cluster_nodes ADD INDEX user_id_idx (user_id);

