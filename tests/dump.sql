CREATE TABLE IF NOT EXISTS `author` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `author` (`id`, `name`) VALUES
	(1, 'Terry Pratchett'),
	(2, 'Ray Bradburry'),
	(3, 'Douglas Adams');

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tag` (`id`, `name`) VALUES
    (1, 'Discworld'),
    (2, 'Escarina'),
    (3, 'Cooking');

CREATE TABLE IF NOT EXISTS `book` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `BOOK_AUTHOR` (`author_id`),
  CONSTRAINT `BOOK_AUTHOR` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `book` (`id`, `name`, `author_id`) VALUES
	(1, 'Equal rites', 1);

CREATE TABLE IF NOT EXISTS `book_tag` (
  `book_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`book_id`,`tag_id`),
  KEY `TAG_TAG` (`tag_id`),
  CONSTRAINT `TAG_BOOK` FOREIGN KEY (`book_id`) REFERENCES `book` (`id`),
  CONSTRAINT `TAG_TAG` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `book_tag` (`book_id`, `tag_id`) VALUES
	(1, 1),
	(1, 2);
