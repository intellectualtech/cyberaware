-- Create contact requests table

CREATE TABLE `contact_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `company` varchar(150) NOT NULL,
  `team_size` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','contacted','converted','rejected') DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `contact_requests` ADD PRIMARY KEY (`id`);
ALTER TABLE `contact_requests` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
