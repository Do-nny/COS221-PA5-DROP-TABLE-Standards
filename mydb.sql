-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: May 13, 2026 at 09:21 AM
-- Server version: 8.0.46
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mydb`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodation`
--

CREATE TABLE `accommodation` (
  `item_id` int UNSIGNED NOT NULL,
  `accommodation_name` varchar(150) NOT NULL,
  `accommodation_price` decimal(10,2) NOT NULL,
  `checkin_date_time` datetime NOT NULL,
  `checkout_date_time` datetime NOT NULL,
  `price_per_night_pp` decimal(10,2) NOT NULL,
  `accommodation_street` varchar(150) NOT NULL,
  `accommodation_number` varchar(20) NOT NULL,
  `accommodation_town` varchar(100) NOT NULL
) ;

--
-- Dumping data for table `accommodation`
--

INSERT INTO `accommodation` (`item_id`, `accommodation_name`, `accommodation_price`, `checkin_date_time`, `checkout_date_time`, `price_per_night_pp`, `accommodation_street`, `accommodation_number`, `accommodation_town`) VALUES
(4, 'The Table Bay Hotel', 9000.00, '2025-07-01 14:00:00', '2025-07-10 11:00:00', 100.00, 'Quay 6', '6', 'Cape Town'),
(5, 'Park Hyatt Tokyo', 16000.00, '2025-08-15 15:00:00', '2025-08-25 12:00:00', 160.00, 'Nishi-Shinjuku', '3-7', 'Tokyo'),
(6, 'Hotel Le Marais', 7200.00, '2025-09-05 15:00:00', '2025-09-12 11:00:00', 90.00, 'Rue de Bretagne', '12', 'Paris');

-- --------------------------------------------------------

--
-- Table structure for table `agency_phone_number`
--

CREATE TABLE `agency_phone_number` (
  `user_id` int UNSIGNED NOT NULL,
  `agency_cellphone_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `agency_phone_number`
--

INSERT INTO `agency_phone_number` (`user_id`, `agency_cellphone_number`) VALUES
(1, '+27821230001'),
(2, '+81901230002'),
(3, '+33671230003');

-- --------------------------------------------------------

--
-- Table structure for table `agency_review`
--

CREATE TABLE `agency_review` (
  `review_id` int UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `comment` text,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `agency_id` int UNSIGNED NOT NULL
) ;

--
-- Dumping data for table `agency_review`
--

INSERT INTO `agency_review` (`review_id`, `rating`, `comment`, `review_date`, `agency_id`) VALUES
(1, 5, 'Absolutely fantastic service!', '2026-05-13 09:20:59', 1),
(2, 4, 'Great packages, minor delays.', '2026-05-13 09:20:59', 2),
(3, 3, 'Average experience overall.', '2026-05-13 09:20:59', 3);

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int UNSIGNED NOT NULL,
  `booking_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `number_of_people` smallint UNSIGNED NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  `package_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `booking_date`, `number_of_people`, `total_price`, `booking_status`, `payment_status`, `package_id`, `user_id`) VALUES
(1, '2026-05-13 09:20:59', 2, 3000.00, 'confirmed', 'paid', 1, 1),
(2, '2026-05-13 09:20:59', 3, 6600.00, 'confirmed', 'paid', 2, 2),
(3, '2026-05-13 09:20:59', 1, 1800.00, 'completed', 'paid', 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `destination`
--

CREATE TABLE `destination` (
  `destination_id` int UNSIGNED NOT NULL,
  `city` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `destination`
--

INSERT INTO `destination` (`destination_id`, `city`, `country`) VALUES
(1, 'Paris', 'France'),
(2, 'Tokyo', 'Japan'),
(3, 'Cape Town', 'South Africa');

-- --------------------------------------------------------

--
-- Table structure for table `flight`
--

CREATE TABLE `flight` (
  `item_id` int UNSIGNED NOT NULL,
  `airline` varchar(100) NOT NULL,
  `flight_price` decimal(10,2) NOT NULL,
  `departure_date_time` datetime NOT NULL,
  `arrival_date_time` datetime NOT NULL,
  `departure_airport` varchar(100) NOT NULL,
  `arrival_airport` varchar(100) NOT NULL
) ;

--
-- Dumping data for table `flight`
--

INSERT INTO `flight` (`item_id`, `airline`, `flight_price`, `departure_date_time`, `arrival_date_time`, `departure_airport`, `arrival_airport`) VALUES
(1, 'South African Airways', 850.00, '2025-07-01 08:00:00', '2025-07-01 10:30:00', 'OR Tambo (JNB)', 'Cape Town Intl (CPT)'),
(2, 'Japan Airlines', 1400.00, '2025-08-15 11:00:00', '2025-08-15 23:45:00', 'O.R. Tambo (JNB)', 'Narita Intl (NRT)'),
(3, 'Air France', 1100.00, '2025-09-05 06:30:00', '2025-09-05 14:00:00', 'King Shaka Intl (DUR)', 'Charles de Gaulle (CDG)');

-- --------------------------------------------------------

--
-- Table structure for table `group_trip`
--

CREATE TABLE `group_trip` (
  `package_id` int UNSIGNED NOT NULL,
  `group_trip_id` int UNSIGNED NOT NULL,
  `no_of_people` smallint UNSIGNED NOT NULL DEFAULT '0',
  `max_people` smallint UNSIGNED NOT NULL
) ;

--
-- Dumping data for table `group_trip`
--

INSERT INTO `group_trip` (`package_id`, `group_trip_id`, `no_of_people`, `max_people`) VALUES
(1, 1, 8, 20),
(2, 1, 15, 15),
(3, 1, 5, 12);

-- --------------------------------------------------------

--
-- Table structure for table `includes`
--

CREATE TABLE `includes` (
  `package_id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `includes`
--

INSERT INTO `includes` (`package_id`, `item_id`) VALUES
(1, 1),
(2, 4),
(3, 7);

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_day`
--

CREATE TABLE `itinerary_day` (
  `package_id` int UNSIGNED NOT NULL,
  `day_number` tinyint UNSIGNED NOT NULL,
  `day_activity` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `itinerary_day`
--

INSERT INTO `itinerary_day` (`package_id`, `day_number`, `day_activity`) VALUES
(1, 1, 'Arrive in Cape Town, check in, welcome dinner at V&A Waterfront.'),
(2, 1, 'Arrive in Tokyo, check in, explore Shibuya crossing.'),
(3, 1, 'Arrive in Paris, check in, evening stroll along the Seine.');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_day_item`
--

CREATE TABLE `itinerary_day_item` (
  `package_id` int UNSIGNED NOT NULL,
  `day_number` tinyint UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `item_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `itinerary_day_item`
--

INSERT INTO `itinerary_day_item` (`package_id`, `day_number`, `item_id`, `item_time`) VALUES
(1, 1, 1, '08:00:00'),
(2, 1, 2, '11:00:00'),
(3, 1, 3, '06:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `package_destinations`
--

CREATE TABLE `package_destinations` (
  `package_id` int UNSIGNED NOT NULL,
  `destination_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `package_destinations`
--

INSERT INTO `package_destinations` (`package_id`, `destination_id`) VALUES
(1, 3),
(2, 2),
(3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `package_review`
--

CREATE TABLE `package_review` (
  `review_id` int UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `comment` text,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `booking_id` int UNSIGNED NOT NULL
) ;

--
-- Dumping data for table `package_review`
--

INSERT INTO `package_review` (`review_id`, `rating`, `comment`, `review_date`, `booking_id`) VALUES
(1, 5, 'Cape Town was breathtaking!', '2026-05-13 09:20:59', 1),
(2, 4, 'Tokyo blew my mind.', '2026-05-13 09:20:59', 2),
(3, 4, 'Paris was très magnifique.', '2026-05-13 09:20:59', 3);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant`
--

CREATE TABLE `restaurant` (
  `item_id` int UNSIGNED NOT NULL,
  `restaurant_name` varchar(150) NOT NULL,
  `average_price_pp` decimal(10,2) NOT NULL,
  `operational_hours` varchar(100) DEFAULT NULL,
  `restaurant_street` varchar(150) NOT NULL,
  `restaurant_number` varchar(20) NOT NULL,
  `restaurant_town` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `restaurant`
--

INSERT INTO `restaurant` (`item_id`, `restaurant_name`, `average_price_pp`, `operational_hours`, `restaurant_street`, `restaurant_number`, `restaurant_town`) VALUES
(10, 'La Colombe', 85.00, '12:00–22:00', 'Silvermist Wine Estate', '1', 'Cape Town'),
(11, 'Sukiyabashi Jiro', 120.00, '11:30–14:00', 'Tsukamoto Sogyo Bldg', 'B1', 'Tokyo'),
(12, 'Le Jules Verne', 95.00, '12:00–22:00', 'Champ de Mars', '5', 'Paris');

-- --------------------------------------------------------

--
-- Table structure for table `tourist_attraction`
--

CREATE TABLE `tourist_attraction` (
  `item_id` int UNSIGNED NOT NULL,
  `attraction_name` varchar(150) NOT NULL,
  `activity_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `operational_hours` varchar(100) DEFAULT NULL,
  `attraction_street` varchar(150) NOT NULL,
  `attraction_number` varchar(20) NOT NULL,
  `attraction_town` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tourist_attraction`
--

INSERT INTO `tourist_attraction` (`item_id`, `attraction_name`, `activity_fee`, `operational_hours`, `attraction_street`, `attraction_number`, `attraction_town`) VALUES
(7, 'Table Mountain Aerial Cableway', 25.00, '08:00–18:00', 'Tafelberg Road', '1', 'Cape Town'),
(8, 'Senso-ji Temple', 0.00, '06:00–17:00', 'Asakusa 2-chome', '3-1', 'Tokyo'),
(9, 'Eiffel Tower', 28.00, '09:00–23:45', 'Champ de Mars', '5', 'Paris');

-- --------------------------------------------------------

--
-- Table structure for table `traveler`
--

CREATE TABLE `traveler` (
  `user_id` int UNSIGNED NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `residing_country` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `traveler`
--

INSERT INTO `traveler` (`user_id`, `phone_number`, `email`, `password_hash`, `id_number`, `residing_country`, `date_of_birth`, `first_name`, `last_name`) VALUES
(1, '+27821112222', 'alice@email.com', 'hashed_pw_a', 'SA8801015001', 'South Africa', '1988-01-01', 'Alice', 'Mokoena'),
(2, '+27833334444', 'bob@email.com', 'hashed_pw_b', 'SA9203025002', 'South Africa', '1992-03-02', 'Bob', 'Naidoo'),
(3, '+815012345678', 'yuki@email.co.jp', 'hashed_pw_c', 'JP9507077003', 'Japan', '1995-07-07', 'Yuki', 'Tanaka');

-- --------------------------------------------------------

--
-- Table structure for table `travel_agency`
--

CREATE TABLE `travel_agency` (
  `user_id` int UNSIGNED NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `agency_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `travel_agency`
--

INSERT INTO `travel_agency` (`user_id`, `phone_number`, `email`, `password_hash`, `agency_name`) VALUES
(1, '+27110001111', 'info@sunsettravel.co.za', 'hashed_pw_1', 'Sunset Travel'),
(2, '+27110002222', 'hello@tokyoescapes.jp', 'hashed_pw_2', 'Tokyo Escapes'),
(3, '+33140001234', 'contact@parisdreams.fr', 'hashed_pw_3', 'Paris Dreams');

-- --------------------------------------------------------

--
-- Table structure for table `travel_item`
--

CREATE TABLE `travel_item` (
  `item_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `travel_item`
--

INSERT INTO `travel_item` (`item_id`) VALUES
(1),
(2),
(3),
(4),
(5),
(6),
(7),
(8),
(9),
(10),
(11),
(12);

-- --------------------------------------------------------

--
-- Table structure for table `travel_package`
--

CREATE TABLE `travel_package` (
  `package_id` int UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_per_individual` decimal(12,2) NOT NULL,
  `total_price_of_package` decimal(12,2) NOT NULL,
  `package_status` enum('active','inactive','fully_booked','cancelled') NOT NULL DEFAULT 'active',
  `agency_id` int UNSIGNED NOT NULL
) ;

--
-- Dumping data for table `travel_package`
--

INSERT INTO `travel_package` (`package_id`, `start_date`, `end_date`, `price_per_individual`, `total_price_of_package`, `package_status`, `agency_id`) VALUES
(1, '2025-07-01', '2025-07-10', 1500.00, 15000.00, 'active', 1),
(2, '2025-08-15', '2025-08-25', 2200.00, 22000.00, 'active', 2),
(3, '2025-09-05', '2025-09-12', 1800.00, 18000.00, 'fully_booked', 3);

-- --------------------------------------------------------

--
-- Table structure for table `travel_package_images`
--

CREATE TABLE `travel_package_images` (
  `package_id` int UNSIGNED NOT NULL,
  `image_url` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `travel_package_images`
--

INSERT INTO `travel_package_images` (`package_id`, `image_url`) VALUES
(1, 'https://cdn.example.com/packages/capetown_hero.jpg'),
(2, 'https://cdn.example.com/packages/tokyo_hero.jpg'),
(3, 'https://cdn.example.com/packages/paris_hero.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodation`
--
ALTER TABLE `accommodation`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `agency_phone_number`
--
ALTER TABLE `agency_phone_number`
  ADD PRIMARY KEY (`user_id`,`agency_cellphone_number`);

--
-- Indexes for table `agency_review`
--
ALTER TABLE `agency_review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `agency_id` (`agency_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `destination`
--
ALTER TABLE `destination`
  ADD PRIMARY KEY (`destination_id`);

--
-- Indexes for table `flight`
--
ALTER TABLE `flight`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `group_trip`
--
ALTER TABLE `group_trip`
  ADD PRIMARY KEY (`package_id`,`group_trip_id`);

--
-- Indexes for table `includes`
--
ALTER TABLE `includes`
  ADD PRIMARY KEY (`package_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `itinerary_day`
--
ALTER TABLE `itinerary_day`
  ADD PRIMARY KEY (`package_id`,`day_number`);

--
-- Indexes for table `itinerary_day_item`
--
ALTER TABLE `itinerary_day_item`
  ADD PRIMARY KEY (`package_id`,`day_number`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `package_destinations`
--
ALTER TABLE `package_destinations`
  ADD PRIMARY KEY (`package_id`,`destination_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `package_review`
--
ALTER TABLE `package_review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `restaurant`
--
ALTER TABLE `restaurant`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `tourist_attraction`
--
ALTER TABLE `tourist_attraction`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `traveler`
--
ALTER TABLE `traveler`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- Indexes for table `travel_agency`
--
ALTER TABLE `travel_agency`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `travel_item`
--
ALTER TABLE `travel_item`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `travel_package`
--
ALTER TABLE `travel_package`
  ADD PRIMARY KEY (`package_id`),
  ADD KEY `agency_id` (`agency_id`);

--
-- Indexes for table `travel_package_images`
--
ALTER TABLE `travel_package_images`
  ADD PRIMARY KEY (`package_id`,`image_url`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agency_review`
--
ALTER TABLE `agency_review`
  MODIFY `review_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `destination`
--
ALTER TABLE `destination`
  MODIFY `destination_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `package_review`
--
ALTER TABLE `package_review`
  MODIFY `review_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `traveler`
--
ALTER TABLE `traveler`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `travel_agency`
--
ALTER TABLE `travel_agency`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `travel_item`
--
ALTER TABLE `travel_item`
  MODIFY `item_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `travel_package`
--
ALTER TABLE `travel_package`
  MODIFY `package_id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accommodation`
--
ALTER TABLE `accommodation`
  ADD CONSTRAINT `accommodation_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `travel_item` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `agency_phone_number`
--
ALTER TABLE `agency_phone_number`
  ADD CONSTRAINT `agency_phone_number_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `travel_agency` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `agency_review`
--
ALTER TABLE `agency_review`
  ADD CONSTRAINT `agency_review_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `travel_agency` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `travel_package` (`package_id`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `traveler` (`user_id`);

--
-- Constraints for table `flight`
--
ALTER TABLE `flight`
  ADD CONSTRAINT `flight_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `travel_item` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_trip`
--
ALTER TABLE `group_trip`
  ADD CONSTRAINT `group_trip_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `travel_package` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `includes`
--
ALTER TABLE `includes`
  ADD CONSTRAINT `includes_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `travel_package` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `includes_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `travel_item` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_day`
--
ALTER TABLE `itinerary_day`
  ADD CONSTRAINT `itinerary_day_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `travel_package` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_day_item`
--
ALTER TABLE `itinerary_day_item`
  ADD CONSTRAINT `itinerary_day_item_ibfk_1` FOREIGN KEY (`package_id`,`day_number`) REFERENCES `itinerary_day` (`package_id`,`day_number`) ON DELETE CASCADE,
  ADD CONSTRAINT `itinerary_day_item_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `travel_item` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `package_destinations`
--
ALTER TABLE `package_destinations`
  ADD CONSTRAINT `package_destinations_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `travel_package` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_destinations_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destination` (`destination_id`) ON DELETE CASCADE;

--
-- Constraints for table `package_review`
--
ALTER TABLE `package_review`
  ADD CONSTRAINT `package_review_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant`
--
ALTER TABLE `restaurant`
  ADD CONSTRAINT `restaurant_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `travel_item` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `tourist_attraction`
--
ALTER TABLE `tourist_attraction`
  ADD CONSTRAINT `tourist_attraction_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `travel_item` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_package`
--
ALTER TABLE `travel_package`
  ADD CONSTRAINT `travel_package_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `travel_agency` (`user_id`);

--
-- Constraints for table `travel_package_images`
--
ALTER TABLE `travel_package_images`
  ADD CONSTRAINT `travel_package_images_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `travel_package` (`package_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
