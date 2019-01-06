DROP TABLE IF EXISTS `akr_dbtest`;
DROP TABLE IF EXISTS `akr_dbtest_innodb`;
DROP TABLE IF EXISTS `foo_dbtest`;

CREATE TABLE IF NOT EXISTS `akr_dbtest` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`       varchar(50)      NOT NULL,
  `start_date`  datetime         NOT NULL,
  `description` varchar(255)     NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;


CREATE TABLE IF NOT EXISTS `akr_dbtest_innodb` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`       varchar(50)      NOT NULL,
  `start_date`  datetime         NOT NULL,
  `description` varchar(255)     NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `foo_dbtest` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`       varchar(50)      NOT NULL,
  `start_date`  datetime         NOT NULL,
  `description` varchar(255)     NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8mb4_unicode_520_ci;