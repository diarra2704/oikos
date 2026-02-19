-- Création de la table membre_formations (équivalent de la migration Laravel)
-- À exécuter dans la base oikos si php artisan migrate ne s'exécute pas.

CREATE TABLE IF NOT EXISTS `membre_formations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `membre_id` bigint unsigned NOT NULL,
  `type_formation` varchar(50) NOT NULL,
  `statut_formation` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `membre_formations_membre_id_type_formation_unique` (`membre_id`,`type_formation`),
  KEY `membre_formations_membre_id_foreign` (`membre_id`),
  CONSTRAINT `membre_formations_membre_id_foreign` FOREIGN KEY (`membre_id`) REFERENCES `membres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensuite, marquer la migration comme exécutée (dans phpMyAdmin ou MySQL) :
-- INSERT INTO migrations (migration, batch) VALUES ('2026_02_20_000000_create_membre_formations_table', XX);
-- (Remplacez XX par : SELECT MAX(batch)+1 FROM migrations; puis utilisez ce nombre.)
