-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2025 at 08:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel`
--

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_11_01_100946_create_tracks_table', 1),
(6, '2025_11_01_100947_create_teacher_settings_table', 1),
(7, '2025_11_04_000000_create_student_settings_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_settings`
--

CREATE TABLE `student_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `track_id` bigint(20) UNSIGNED NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `certificate_bg` varchar(255) NOT NULL COMMENT 'Relative path to background image',
  `positions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Position map including optional photo' CHECK (json_valid(`positions`)),
  `style` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Font, sizes, colors, weights, alignment per field' CHECK (json_valid(`style`)),
  `print_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Print flags: arabic_only, english_only, per-field on/off' CHECK (json_valid(`print_defaults`)),
  `date_type` enum('duration','end') NOT NULL DEFAULT 'duration',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_settings`
--

INSERT INTO `student_settings` (`id`, `track_id`, `gender`, `certificate_bg`, `positions`, `style`, `print_defaults`, `date_type`, `notes`, `created_at`, `updated_at`) VALUES
(20, 2027, 'male', 'images/templates/student/s_the_village-male.png', '{\"cert_date\":{\"left_pct\":0.549,\"top_pct\":0.10891089108910891,\"width_pct\":0.263,\"height_pct\":0.04950495049504951},\"ar_name\":{\"left_pct\":0.657,\"top_pct\":0.371994342291372,\"width_pct\":0.303,\"height_pct\":0.04950495049504951},\"en_name\":{\"left_pct\":0.044,\"top_pct\":0.371994342291372,\"width_pct\":0.404,\"height_pct\":0.04950495049504951},\"ar_track\":{\"left_pct\":0.657,\"top_pct\":0.4667609618104668,\"width_pct\":0.303,\"height_pct\":0.04950495049504951},\"en_track\":{\"left_pct\":0.044,\"top_pct\":0.4667609618104668,\"width_pct\":0.404,\"height_pct\":0.04950495049504951},\"ar_from\":{\"left_pct\":0.697,\"top_pct\":0.5615275813295615,\"width_pct\":0.152,\"height_pct\":0.04950495049504951},\"en_from\":{\"left_pct\":0.168,\"top_pct\":0.5431400282885431,\"width_pct\":0.202,\"height_pct\":0.04950495049504951},\"photo\":{\"left_pct\":0.101,\"top_pct\":0.1669024045261669,\"width_pct\":0.101,\"height_pct\":0.14285714285714285}}', '{\"font_per\":[],\"size_per\":{\"cert_date\":\"5\",\"ar_name\":\"7\",\"ar_track\":\"6\",\"ar_from\":\"6\",\"en_name\":\"6\",\"en_track\":\"5.5\",\"en_from\":\"5.2\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"ar_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_name\":\"#0f172a\",\"en_track\":\"#0891b2\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"ar_track\":true,\"ar_from\":true,\"en_name\":true,\"en_track\":true,\"en_from\":true}', 'duration', NULL, '2025-11-10 03:14:50', '2025-11-10 03:15:29'),
(21, 2027, 'female', 'images/templates/student/s_the_village-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-10 03:14:50', '2025-11-10 03:14:50'),
(22, 18, 'male', 'images/templates/student/s_laravel_fundamentals-male.jpg', '{\"cert_date\":{\"left_pct\":0.723,\"top_pct\":0.10198300283286119,\"width_pct\":0.101,\"height_pct\":0.049575070821529746},\"ar_name\":{\"left_pct\":0.666,\"top_pct\":0.37677053824362605,\"width_pct\":0.296,\"height_pct\":0.049575070821529746},\"ar_track\":{\"left_pct\":0.675,\"top_pct\":0.46175637393767704,\"width_pct\":0.296,\"height_pct\":0.049575070821529746},\"ar_from\":{\"left_pct\":0.7,\"top_pct\":0.5679886685552408,\"width_pct\":0.148,\"height_pct\":0.049575070821529746},\"en_name\":{\"left_pct\":0.094,\"top_pct\":0.3909348441926346,\"width_pct\":0.397,\"height_pct\":0.049575070821529746},\"en_track\":{\"left_pct\":0.094,\"top_pct\":0.47592067988668557,\"width_pct\":0.397,\"height_pct\":0.049575070821529746},\"en_from\":{\"left_pct\":0.094,\"top_pct\":0.5623229461756374,\"width_pct\":0.202,\"height_pct\":0.049575070821529746},\"photo\":{\"left_pct\":0.101,\"top_pct\":0.1671388101983003,\"width_pct\":0.101,\"height_pct\":0.14305949008498584}}', '{\"font_per\":{\"cert_date\":\"DejaVu Sans\",\"ar_name\":\"DejaVu Sans\",\"ar_track\":\"DejaVu Sans\",\"ar_from\":\"DejaVu Sans\",\"en_name\":\"DejaVu Sans\",\"en_track\":\"DejaVu Sans\",\"en_from\":\"DejaVu Sans\"},\"size_per\":{\"cert_date\":\"4\",\"ar_name\":\"4\",\"ar_track\":\"4\",\"ar_from\":\"4\",\"en_name\":\"4\",\"en_track\":\"4\",\"en_from\":\"4\"},\"colors\":{\"cert_date\":\"#ff0000\",\"ar_name\":\"#0f1675\",\"ar_track\":\"#e70d0d\",\"ar_from\":\"#e70d0d\",\"en_name\":\"#c61515\",\"en_track\":\"#320de7\",\"en_from\":\"#ff1a1a\"}}', '{\"arabic_only\":true,\"english_only\":false,\"ar_name\":true,\"ar_track\":true,\"ar_from\":true,\"en_name\":false,\"en_track\":true,\"en_from\":false}', 'duration', NULL, '2025-11-10 03:24:24', '2025-11-10 03:24:24');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_settings`
--

CREATE TABLE `teacher_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `track_id` bigint(20) UNSIGNED NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `certificate_bg` varchar(255) NOT NULL COMMENT 'Relative path to background image',
  `positions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Position map including optional photo' CHECK (json_valid(`positions`)),
  `style` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Font, sizes, colors, weights, alignment per field' CHECK (json_valid(`style`)),
  `print_defaults` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Print flags: arabic_only, english_only, per-field on/off' CHECK (json_valid(`print_defaults`)),
  `date_type` enum('duration','end') NOT NULL DEFAULT 'duration',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_settings`
--

INSERT INTO `teacher_settings` (`id`, `track_id`, `gender`, `certificate_bg`, `positions`, `style`, `print_defaults`, `date_type`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'male', 'images/templates/teacher/t_laravel_fundamentals-male.jpg', '{\"cert_date\":{\"top\":21.14021479263633,\"left\":165.9069920838046,\"width\":78,\"font\":6},\"ar_name\":{\"top\":77.20542151041538,\"left\":195.52708333101145,\"width\":90,\"font\":6},\"ar_track\":{\"top\":97.99284775935473,\"left\":194.99791666435107,\"width\":90,\"font\":6},\"ar_from\":{\"top\":117.9975552107786,\"left\":207.52593911242414,\"width\":45,\"font\":6},\"en_name\":{\"top\":78.26375080651486,\"left\":13.524594370363484,\"width\":120,\"font\":6},\"en_track\":{\"top\":96.40755359614649,\"left\":11.410156249864505,\"width\":120,\"font\":6},\"en_from\":{\"top\":113.73114946548193,\"left\":49.19925791364102,\"width\":60,\"font\":6},\"photo\":{\"top\":36.85205049471009,\"left\":150.6493316632501,\"width\":30,\"height\":30,\"font\":6}}', '{\"font_per\":{\"cert_date\":\"DejaVu Sans\",\"ar_name\":\"DejaVu Sans\",\"ar_track\":\"DejaVu Sans\",\"ar_from\":\"DejaVu Sans\",\"en_name\":\"DejaVu Sans\",\"en_track\":\"DejaVu Sans\",\"en_from\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0a12ff\",\"ar_name\":\"#0d3cc9\",\"ar_track\":\"#6600ff\",\"ar_from\":\"#ff00d0\",\"en_name\":\"#1e00ff\",\"en_track\":\"#004cff\",\"en_from\":\"#004cff\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'duration', NULL, '2025-11-02 02:36:33', '2025-11-03 02:49:53'),
(2, 1, 'female', 'images/templates/teacher/t_laravel_fundamentals-female.jpg', '{\"cert_date\":{\"top\":23,\"left\":163,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"left\":195,\"width\":90,\"font\":5},\"ar_track\":{\"top\":98,\"left\":195,\"width\":90,\"font\":5},\"ar_from\":{\"top\":118,\"left\":207,\"width\":45,\"font\":5},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":5},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"font\":6}}', '{\"font_per\":{\"cert_date\":\"IBMPlexSansArabic\",\"ar_name\":\"IBMPlexSansArabic\",\"ar_track\":\"IBMPlexSansArabic\",\"ar_from\":\"IBMPlexSansArabic\",\"en_name\":\"IBMPlexSansArabic\",\"en_track\":\"IBMPlexSansArabic\",\"en_from\":\"IBMPlexSansArabic\"},\"colors\":{\"cert_date\":\"#00ff1e\",\"ar_name\":\"#1eff00\",\"ar_track\":\"#1eff00\",\"ar_from\":\"#4fff1f\",\"en_name\":\"#00ffb3\",\"en_track\":\"#0fff37\",\"en_from\":\"#11ff00\"}}', '{\"arabic_only\":true,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'end', NULL, '2025-11-03 02:56:54', '2025-11-03 02:56:54'),
(7, 7, 'male', 'images/templates/teacher/t_yanqil_development-male.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":88,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":88,\"left\":13,\"width\":120,\"font\":5.5},\"ar_duration\":{\"top\":98,\"right\":12,\"width\":90,\"font\":5},\"en_duration\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_duration\":\"#64748b\",\"en_duration\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'duration', NULL, '2025-11-03 06:46:49', '2025-11-03 06:46:49'),
(8, 7, 'female', '', '{\"cert_date\":{\"top\":23,\"left\":163,\"width\":78,\"font\":5},\"ar_name\":{\"top\":131.44500686171526,\"left\":147.6374999982468,\"width\":90,\"font\":7},\"ar_track\":{\"top\":118.95226165312337,\"left\":147.3729166649166,\"width\":90,\"font\":6},\"ar_from\":{\"top\":148.7972604416026,\"left\":151.97224832990105,\"width\":90,\"font\":6},\"en_name\":{\"top\":129.5929235284039,\"left\":57.44985249769017,\"width\":120,\"font\":6},\"en_track\":{\"top\":117.89392831980261,\"left\":58.77276916434114,\"width\":120,\"font\":5.5},\"en_from\":{\"top\":145.3576771083101,\"left\":57.51599833102273,\"width\":120,\"font\":5.5},\"photo\":{\"top\":80.27017710908301,\"left\":143.50558166333494,\"width\":30,\"height\":30,\"font\":6}}', '{\"font_per\":[],\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"ar_track\":\"#0891b2\",\"ar_from\":\"#0891b2\",\"en_name\":\"#0f172a\",\"en_track\":\"#0891b2\",\"en_from\":\"#0f172a\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'end', NULL, '2025-11-03 06:46:49', '2025-11-04 02:34:16'),
(9, 8, 'male', '', '{\"cert_date\":{\"top\":23,\"left\":163,\"width\":78,\"font\":20},\"ar_name\":{\"top\":95.72625282491813,\"left\":194.99791666435107,\"width\":90,\"font\":5},\"ar_track\":{\"top\":109.42726165323649,\"left\":196.05624999767184,\"width\":90,\"font\":5},\"ar_from\":{\"top\":122.07434983108516,\"left\":196.41785583262848,\"width\":90,\"font\":5},\"en_name\":{\"top\":100.75333615819177,\"left\":18.556102498152043,\"width\":120,\"font\":5},\"en_track\":{\"top\":112.86684498652897,\"left\":18.556102498152043,\"width\":120,\"font\":5},\"en_from\":{\"top\":124.4555998310569,\"left\":18.084264373564547,\"width\":120,\"font\":5},\"photo\":{\"top\":61.220173072087945,\"left\":81.58865686955522,\"width\":100,\"height\":30,\"font\":6}}', '{\"font_per\":{\"cert_date\":\"Tasees\",\"ar_name\":\"Tasees\",\"ar_track\":\"Tasees\",\"ar_from\":\"Tasees\",\"en_name\":\"Tasees\",\"en_track\":\"Tasees\",\"en_from\":\"Tasees\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#000000\",\"ar_track\":\"#000000\",\"ar_from\":\"#1b2527\",\"en_name\":\"#0f172a\",\"en_track\":\"#000000\",\"en_from\":\"#0f172a\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'end', NULL, '2025-11-04 02:48:16', '2025-11-04 02:53:27'),
(10, 8, 'female', 'images/templates/teacher/t_aqarat_bahla-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":88,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":88,\"left\":13,\"width\":120,\"font\":5.5},\"ar_duration\":{\"top\":98,\"right\":12,\"width\":90,\"font\":5},\"en_duration\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_duration\":\"#64748b\",\"en_duration\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'duration', NULL, '2025-11-04 02:48:16', '2025-11-04 02:48:16'),
(15, 11, 'male', 'images/templates/teacher/t_mohamed_ali-male.png', '{\"cert_date\":{\"top\":102.10711940008346,\"left\":49.754876454080375,\"width\":78,\"font\":5},\"ar_name\":{\"top\":153.67000282423004,\"left\":197.1145833309926,\"width\":90,\"font\":7},\"ar_track\":{\"top\":169.96392508940727,\"left\":195.79166666434165,\"width\":90,\"font\":6},\"ar_from\":{\"top\":183.08726003747327,\"left\":206.7322133357612,\"width\":45,\"font\":6},\"en_name\":{\"top\":153.14083615756968,\"left\":60.36026916432229,\"width\":120,\"font\":6},\"en_track\":{\"top\":165.9951750894544,\"left\":60.095685830992096,\"width\":120,\"font\":5.5},\"en_from\":{\"top\":190.9894827978978,\"left\":51.05576604146729,\"width\":60,\"font\":5.2},\"photo\":{\"top\":101.14580049394661,\"left\":150.12016499658972,\"width\":30,\"height\":30,\"font\":6}}', '{\"font_per\":[],\"weight_per\":{\"ar_name\":\"700\",\"ar_track\":\"700\",\"en_name\":\"700\",\"en_track\":\"700\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"ar_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_name\":\"#0f172a\",\"en_track\":\"#0891b2\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":true,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'end', NULL, '2025-11-04 04:17:00', '2025-11-04 04:45:08'),
(16, 11, 'female', 'images/templates/teacher/t_mohamed_ali-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-04 04:17:00', '2025-11-04 04:17:00'),
(17, 12, 'male', 'images/templates/teacher/t_backend_development-male.png', '{\"cert_date\":{\"top\":23,\"left\":163,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"left\":195,\"width\":90,\"font\":7},\"ar_track\":{\"top\":98,\"left\":195,\"width\":90,\"font\":6},\"ar_from\":{\"top\":118,\"left\":207,\"width\":45,\"font\":6},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"font\":6}}', '{\"font_per\":[],\"weight_per\":{\"ar_name\":\"700\",\"ar_track\":\"700\",\"en_name\":\"700\",\"en_track\":\"700\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"ar_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_name\":\"#0f172a\",\"en_track\":\"#0891b2\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":true,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'end', NULL, '2025-11-06 04:39:50', '2025-11-06 04:40:13'),
(18, 12, 'female', 'images/templates/teacher/t_backend_development-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-06 04:39:50', '2025-11-06 04:39:50'),
(19, 22, 'male', 'images/templates/teacher/t_the_village-male.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-09 06:50:27', '2025-11-09 06:50:27'),
(20, 22, 'female', 'images/templates/teacher/t_the_village-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-09 06:50:27', '2025-11-09 06:50:27'),
(21, 2023, 'male', 'images/templates/teacher/t_hussain-male.png', '{\"cert_date\":{\"top\":23,\"left\":163,\"width\":78,\"font\":4},\"ar_name\":{\"top\":134.35458984215455,\"left\":189.97083333107744,\"width\":90,\"font\":4},\"ar_track\":{\"top\":146.41793619617795,\"left\":190.2354166644076,\"width\":90,\"font\":4},\"ar_from\":{\"top\":156.88964843563693,\"left\":235.57425129928592,\"width\":45,\"font\":4},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":4},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":4},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":4},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"font\":6}}', '{\"font_per\":[],\"weight_per\":{\"ar_name\":\"700\",\"ar_track\":\"700\",\"en_name\":\"700\",\"en_track\":\"700\"},\"colors\":{\"cert_date\":\"#004cff\",\"ar_name\":\"#006aff\",\"ar_track\":\"#00ccff\",\"ar_from\":\"#006aff\",\"en_name\":\"#004cff\",\"en_track\":\"#00ccff\",\"en_from\":\"#006aff\"}}', '{\"arabic_only\":true,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_duration\":true,\"en_duration\":true}', 'end', NULL, '2025-11-09 06:58:39', '2025-11-09 07:08:07'),
(22, 2023, 'female', 'images/templates/teacher/t_hussain-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-09 06:58:39', '2025-11-09 06:58:39'),
(23, 2024, 'male', 'images/templates/teacher/t_hussain_1-male.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-09 07:00:00', '2025-11-09 07:00:00'),
(24, 2024, 'female', 'images/templates/teacher/t_hussain_1-female.png', '{\"cert_date\":{\"top\":23,\"right\":56,\"width\":78,\"font\":5},\"ar_name\":{\"top\":78,\"right\":12,\"width\":90,\"font\":7},\"en_name\":{\"top\":78,\"left\":13,\"width\":120,\"font\":6},\"ar_track\":{\"top\":98,\"right\":12,\"width\":90,\"font\":6},\"en_track\":{\"top\":98,\"left\":13,\"width\":120,\"font\":5.5},\"ar_from\":{\"top\":118,\"right\":45,\"width\":45,\"font\":6},\"en_from\":{\"top\":114,\"left\":50,\"width\":60,\"font\":5.2},\"photo\":{\"top\":35,\"left\":30,\"width\":30,\"height\":30,\"radius\":6,\"border\":0.6,\"border_color\":\"#1f2937\"}}', '{\"font\":{\"ar\":\"Amiri\",\"en\":\"DejaVu Sans\"},\"colors\":{\"cert_date\":\"#0f172a\",\"ar_name\":\"#334155\",\"en_name\":\"#0f172a\",\"ar_track\":\"#0891b2\",\"en_track\":\"#0891b2\",\"ar_from\":\"#64748b\",\"en_from\":\"#64748b\"}}', '{\"arabic_only\":false,\"english_only\":false,\"ar_name\":true,\"en_name\":true,\"ar_track\":true,\"en_track\":true,\"ar_from\":true,\"en_from\":true}', 'duration', NULL, '2025-11-09 07:00:00', '2025-11-09 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE `tracks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL COMMENT 'e.g. t_full_stack_web',
  `name_ar` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tracks`
--

INSERT INTO `tracks` (`id`, `key`, `name_ar`, `name_en`, `active`, `created_at`, `updated_at`) VALUES
(1, 't_laravel_fundamentals', 'أساسيات لارافيل (معلم)', 'Laravel Fundamentals (Teacher)', 1, '2025-11-02 02:23:06', '2025-11-02 02:23:06'),
(2, 't_full_stack_web', 'تطوير الويب المتكامل (معلم)', 'Full-Stack Web (Teacher)', 1, '2025-11-02 02:23:06', '2025-11-02 02:23:06'),
(3, 't_data_analysis_python', 'تحليل البيانات ببايثون', 'Data Analysis with Python', 1, '2025-11-02 02:23:06', '2025-11-02 02:23:06'),
(4, 't_cybersecurity_basics', 'مبادئ الأمن السيبراني', 'Cybersecurity Basics', 1, '2025-11-02 02:23:06', '2025-11-02 02:23:06'),
(7, 't_yanqil_development', 'تطوير ينقل', 'Yanqil Development', 1, '2025-11-03 06:46:49', '2025-11-03 06:46:49'),
(8, 't_aqarat_bahla', 'عقارات بهلا', 'Aqarat Bahla', 1, '2025-11-04 02:48:16', '2025-11-04 02:48:16'),
(11, 't_mohamed_ali', 'محمد علي', 'Mohamed Ali', 1, '2025-11-04 04:17:00', '2025-11-04 04:17:00'),
(12, 't_backend_development', 'تطوير خلفية المواقع', 'Backend-development', 1, '2025-11-06 04:39:50', '2025-11-06 04:39:50'),
(18, 's_laravel_fundamentals', 'أساسيات لارافيل (طلاب)', 'Laravel Fundamentals (Students)', 1, '2025-11-06 07:00:08', '2025-11-06 07:00:08'),
(22, 't_the_village', 'القرية', 'The Village', 1, '2025-11-09 06:50:27', '2025-11-09 06:50:27'),
(2023, 't_hussain', 'حسين', 'Hussain', 1, '2025-11-09 06:58:39', '2025-11-09 06:58:39'),
(2024, 't_hussain_1', 'حسين', 'Hussain', 1, '2025-11-09 07:00:00', '2025-11-09 07:00:00'),
(2026, 's_data_analysis_python', 'تحليل البيانات ببايثون (طلاب)', 'Data Analysis with Python (Students)', 1, '2025-11-09 08:44:26', '2025-11-09 08:44:26'),
(2027, 's_the_village', 'القرية', 'The Village', 1, '2025-11-10 03:14:50', '2025-11-10 03:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `student_settings`
--
ALTER TABLE `student_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_settings_track_id_gender_unique` (`track_id`,`gender`);

--
-- Indexes for table `teacher_settings`
--
ALTER TABLE `teacher_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_settings_track_id_gender_unique` (`track_id`,`gender`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracks_key_unique` (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_settings`
--
ALTER TABLE `student_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `teacher_settings`
--
ALTER TABLE `teacher_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tracks`
--
ALTER TABLE `tracks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2028;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student_settings`
--
ALTER TABLE `student_settings`
  ADD CONSTRAINT `student_settings_track_id_foreign` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_settings`
--
ALTER TABLE `teacher_settings`
  ADD CONSTRAINT `teacher_settings_track_id_foreign` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
