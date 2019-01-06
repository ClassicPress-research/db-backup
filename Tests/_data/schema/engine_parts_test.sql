DROP TABLE IF EXISTS `tst_table1`;
DROP TABLE IF EXISTS `tst_table2`;
DROP TABLE IF EXISTS `tst_table3`;
DROP TABLE IF EXISTS `tst_nontext`;
DROP TABLE IF EXISTS `tst_userfiltered`;
DROP TABLE IF EXISTS `tst_partial`;
DROP TABLE IF EXISTS `tst_large`;
DROP TABLE IF EXISTS `nontst_table1`;

CREATE TABLE IF NOT EXISTS `tst_table1` (
  `id`    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50)      NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;


CREATE TABLE IF NOT EXISTS `tst_table2` (
  `foo`   varchar(50) NOT NULL,
  `title` varchar(50) NOT NULL,
  PRIMARY KEY (`foo`, `title`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `tst_table3` (
  `key`        varchar(50)    NOT NULL,
  `serialized` varchar(5000) NOT NULL,
  PRIMARY KEY (`key`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `tst_nontext` (
  `id`  int(10) unsigned NOT NULL AUTO_INCREMENT,
  `baz` int(5)           NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;


CREATE TABLE IF NOT EXISTS `tst_userfiltered` (
  `id`    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50)      NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;


CREATE TABLE IF NOT EXISTS `tst_partial` (
  `id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`     varchar(50)      NOT NULL,
  `something` varchar(5000)   NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `tst_large` (
  `id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`      varchar(255)     NOT NULL,
  `something` varchar(5000)   NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;


CREATE TABLE IF NOT EXISTS `nontst_table1` (
  `id`    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50)      NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;

CREATE OR REPLACE VIEW `tst_view` AS
  SELECT * FROM `tst_table1`;