--
-- Table structure for table `pin_tag`
--

CREATE TABLE IF NOT EXISTS `pin_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pin_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pin_id` (`pin_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  
-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `letter_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tag` (`tag`),
  KEY `letter_id` (`letter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  
-- --------------------------------------------------------

--
-- Table structure for table `tag_letter`
--

CREATE TABLE IF NOT EXISTS `tag_letter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `letter` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `in_menu` tinyint(1) DEFAULT '0',
  `sort_order` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `letter` (`letter`),
  KEY `in_menu` (`in_menu`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;