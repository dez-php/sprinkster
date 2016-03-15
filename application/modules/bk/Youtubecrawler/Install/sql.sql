--
-- Table structure for table `crawler_youtube_links`
--

CREATE TABLE IF NOT EXISTS `crawler_youtube_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `youtube_search_id` int(11) NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `youtube_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `pin_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `indexing` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `youtube_search_id` (`youtube_search_id`),
  KEY `user_id` (`user_id`),
  KEY `youtube_id` (`youtube_id`),
  KEY `pin_id` (`pin_id`),
  KEY `category_id` (`category_id`),
  KEY `indexing` (`indexing`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crawler_youtube_search`
--

CREATE TABLE IF NOT EXISTS `crawler_youtube_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `limit` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `indexing` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `indexing` (`indexing`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;