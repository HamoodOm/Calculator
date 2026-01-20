-- Student Settings Table Migration
-- Run this SQL on your database if you can't use php artisan migrate

CREATE TABLE IF NOT EXISTS `student_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `track_id` bigint(20) unsigned NOT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificate_bg` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Relative path to background image',
  `positions` json NOT NULL COMMENT 'Position map including optional photo',
  `style` json NOT NULL COMMENT 'Font, sizes, colors, weights, alignment per field',
  `print_defaults` json NOT NULL COMMENT 'Print flags: arabic_only, english_only, per-field on/off',
  `date_type` enum('duration','end') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'duration',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_settings_track_id_gender_unique` (`track_id`,`gender`),
  KEY `student_settings_track_id_foreign` (`track_id`),
  CONSTRAINT `student_settings_track_id_foreign` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add entry to migrations table (so Laravel knows it's been run)
INSERT INTO `migrations` (`migration`, `batch`)
VALUES ('2025_11_04_000000_create_student_settings_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS temp));
