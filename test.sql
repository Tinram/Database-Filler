/**
    * Test database for Database Filler.
    *
    * Includes a variety of MySQL datatypes and several formatting quirks.
    * Created for MySQL 5.x
    *
    * @author       Martin Latter
    * @copyright    Martin Latter 13/12/2014
    * @version      0.06
    * @license      GNU GPL version 3.0 (GPL v3); http://www.gnu.org/licenses/gpl.html
    * @link         https://github.com/Tinram/Database-Filler.git
*/


CREATE DATABASE IF NOT EXISTS dbfilltest CHARACTER SET utf8 COLLATE utf8_general_ci;


USE dbfilltest;


CREATE TABLE IF NOT EXISTS `test_datatypes`
(
    `td_id`                INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

    `sku`                  CHAR(20) NOT NULL,
    `EAN`                  CHAR(13) DEFAULT NULL,

    `huge_quantity`        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `quantity`             INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `med_quantity`         MEDIUMINT(7) UNSIGNED NOT NULL DEFAULT 0,
    `small_quantity`       SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `tiny_quantity`        TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `negative_quantity`    INT(10) NOT NULL DEFAULT 0,

    `price`                DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `val_float`            FLOAT(20, 2) NOT NULL DEFAULT 0.00,
    `val_double`           DOUBLE(40, 4) NOT NULL DEFAULT 0.0000 COMMENT 'normal schema comment',

    `enumerator`           ENUM('a', 'b', 'c', 'd'),

    `flag`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,

    `code`                 CHAR(2) NOT NULL DEFAULT ''              ,   -- deliberate whitespace

    `notes`                VARCHAR(255) NOT NULL DEFAULT '',
    `tinytxt`              TINYTEXT COMMENT "badly placed schema comment" NOT NULL,

    `added_date`           DATE NOT NULL,
    `added_dtime`          DATETIME NOT NULL,
    `added_time`           TIME NOT NULL,
    `ts`                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY `flag` (`flag`),
    PRIMARY KEY (`td_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `logger`
(
    `id`                   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `message`              VARCHAR(255) NOT NULL,
    `timestamp`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `logger2`
(
    `logger2_id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `counter`              SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `message`              VARCHAR(255) NOT NULL,
    `timestamp`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`logger2_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;
