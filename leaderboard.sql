-- Tabel XP & peringkat untuk leaderboard
-- Jalankan di phpMyAdmin pada database `ambativasi`

CREATE TABLE `user_xp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_xp` int(11) NOT NULL DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_streak_bonus_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `user_xp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

ALTER TABLE `user_xp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
