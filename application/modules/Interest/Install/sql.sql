--
-- Table structure for table `interest`
--

CREATE TABLE IF NOT EXISTS `interest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `show` tinyint(1) DEFAULT '1',
  `query` varchar(255) DEFAULT NULL,
  `followers` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `title` (`title`),
  KEY `query` (`query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `interest_tag`
--

CREATE TABLE IF NOT EXISTS `interest_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) DEFAULT NULL,
  `interest_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_id` (`interest_id`),
  KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_related`
--

CREATE TABLE IF NOT EXISTS `interest_related` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interest_id` int(11) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_id` (`interest_id`),
  KEY `related_id` (`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_pin`
--

CREATE TABLE IF NOT EXISTS `interest_pin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interest_id` int(11) DEFAULT NULL,
  `pin_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_id` (`interest_id`),
  KEY `pin_id` (`pin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_follow`
--

CREATE TABLE IF NOT EXISTS `interest_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interest_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_id` (`interest_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;