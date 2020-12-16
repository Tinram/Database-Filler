/**
    * Basketball League test database.
    *
    * Created for MySQL 8.0.19
    *
    * @author       Martin Latter
    * @copyright    Martin Latter 09/12/2020
    * @version      0.03
    * @license      GNU GPL version 3.0 (GPL v3); http://www.gnu.org/licenses/gpl.html
    * @link         https://github.com/Tinram/Database-Filler.git
*/


CREATE DATABASE basketball CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


USE basketball;


CREATE TABLE `country`
(
    `country_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_code`    CHAR(3) NOT NULL DEFAULT '' COMMENT '3-digit ISO code',
    `country_name`    VARCHAR(25) NOT NULL DEFAULT '',

    UNIQUE KEY `uidx_country_code` (`country_code`),

    PRIMARY KEY (`country_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `league`
(
    `league_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_code`    CHAR(3) NOT NULL DEFAULT '',
    `name`            VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'e.g. LSBES – Liga Superior de Baloncesto de El Salvador',
    `acronym`         CHAR(3) NOT NULL DEFAULT '',
    `gender`          ENUM('M', 'F', 'O', '-') NOT NULL DEFAULT '-',
    `tier`            TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'first tier, second tier, zero for unassigned',
    `details`         VARCHAR(255) NOT NULL DEFAULT '',
    `img_src`         VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'server path for image file',
    `active`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `deleted`         TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'soft delete',

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    UNIQUE KEY `uidx_acronym` (`acronym`),
    KEY `idx_active` (`active`),
    KEY `idx_deleted` (`deleted`),

    CONSTRAINT `fk_country_code` FOREIGN KEY (`country_code`) REFERENCES country (`country_code`) ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (`league_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `season`
(
    `season_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(30) NOT NULL DEFAULT '',
    `duration`        TINYINT UNSIGNED NOT NULL DEFAULT 7 COMMENT 'number of months in season',
    `start_date`      DATE NOT NULL,
    `end_date`        DATE NOT NULL,
    `number_games`    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '82 max games in NBA',
    `number_teams`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `archived`        TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    KEY `idx_archived` (`archived`),

    PRIMARY KEY (`season_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `team`
(
    `team_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `league_id`       INT UNSIGNED NOT NULL,
    `name`            VARCHAR(30) NOT NULL DEFAULT '',
    `acronym`         CHAR(3) NOT NULL DEFAULT '',
    `founded`         SMALLINT UNSIGNED NOT NULL,
    `captain`         VARCHAR(20) NOT NULL DEFAULT '',
    `coach`           VARCHAR(20) NOT NULL DEFAULT '',
    `logo_src`        VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'server path for image file',
    `active`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `deleted`         TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    UNIQUE KEY `uidx_acronym` (`acronym`),
    KEY `idx_captain` (`captain`),
    KEY `idx_active` (`active`),
    KEY `idx_deleted` (`deleted`),

    CONSTRAINT `fk_league` FOREIGN KEY (`league_id`) REFERENCES league (`league_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,

    PRIMARY KEY (`team_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `player`
(
    `player_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id`         INT UNSIGNED NOT NULL,
    `first_name`      VARCHAR(35) NOT NULL,
    `last_name`       VARCHAR(35) NOT NULL,
    `age`             TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `img_src`         VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'server path for image file',
    `active`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `deleted`         TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    KEY `idx_last_name` (`last_name`),
    KEY `idx_name` (first_name, `last_name`),
    KEY `idx_active` (`active`),
    KEY `idx_deleted` (`deleted`),

    CONSTRAINT `fk_p_team` FOREIGN KEY (`team_id`) REFERENCES team (`team_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,

    PRIMARY KEY (`player_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `game`
(
    `game_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `date`            DATE NOT NULL,
    `venue`           VARCHAR(40) NOT NULL DEFAULT '',
    `details`         VARCHAR(512) NOT NULL DEFAULT '',
    `home`            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `away`            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `deleted`         TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    KEY `idx_date` (`date`),
    KEY `idx_venue` (`venue`),
    KEY `idx_deleted` (`deleted`),

    PRIMARY KEY (`game_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT 'table game = matches';


CREATE TABLE `game_stats`
(
    `game_stats_id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,

    `game_id`                     INT UNSIGNED NOT NULL,
    `player_id`                   INT UNSIGNED NOT NULL,
    `team_id`                     INT UNSIGNED NOT NULL,
    `season_id`                   INT UNSIGNED NOT NULL,

    `points_per_game`             SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `shots_on_goal`               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_missed`                SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `field_goals`                 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `field_goal_attempts`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `field_goal_pct`              SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `free_throws_made`            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `free_throws_attempts`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `free_throw_pct`              TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `rebounds`                    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `offensive_rebounds`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `offensive_rebound_pct`       TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `defensive_rebounds`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `defensive_rebound_pct`       TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `attempts_in_paint`           SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `assists`                     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `steals`                      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `blocks`                      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `saves`                       SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `fouls`                       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `turnovers`                   SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `rating`                      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `efficiency`                  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `personal_fouls`              SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    `minutes`                     TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `performance_index_rating`    TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `deleted`                     TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `added`                       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`                    CHAR(16) NOT NULL DEFAULT '',
    `updated`                     DATETIME DEFAULT NULL,
    `updated_by`                  CHAR(16) NOT NULL DEFAULT '',

    KEY `idx_deleted` (`deleted`),

    CONSTRAINT `fk_gs_game`       FOREIGN KEY (`game_id`) REFERENCES game (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE, -- if game deleted, delete stats
    CONSTRAINT `fk_gs_player`     FOREIGN KEY (`player_id`) REFERENCES player (`player_id`) ON DELETE NO ACTION ON UPDATE CASCADE, -- if player deleted, keep stats
    CONSTRAINT `fk_gs_team`       FOREIGN KEY (`team_id`) REFERENCES team (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE, -- if team deleted, delete stats
    CONSTRAINT `fk_gs_season`     FOREIGN KEY (`season_id`) REFERENCES season (`season_id`) ON DELETE NO ACTION ON UPDATE CASCADE, -- if season deleted, keep stats

    PRIMARY KEY (`game_stats_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT 'catch-all table for team, player results';


CREATE TABLE `article`
(
    `article_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(40) NOT NULL DEFAULT '',
    `body`            VARCHAR(8192) NOT NULL DEFAULT '' COMMENT 'inline, avoid TEXT',
    `upload_date`     DATE NOT NULL,
    `category`        ENUM('news', 'feature', 'archived', 'historical') NOT NULL DEFAULT 'news',
    `active`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'default invisible until deployed',
    `archived`        TINYINT UNSIGNED NOT NULL DEFAULT 0,

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    UNIQUE KEY `uidx_title` (`title`),
    FULLTEXT KEY `idx_body` (`body`),
    KEY `idx_upload_date` (`upload_date`),
    KEY `idx_act_cat_title` (`active`, `category`, `title`),
    KEY `idx_category` (`category`),
    KEY `idx_active` (`active`),
    KEY `idx_archived` (`archived`),
    KEY `idx_added` (`added`),

    PRIMARY KEY (`article_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `user`
(
    `user_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name`      VARCHAR(30) NOT NULL,
    `last_name`       VARCHAR(30) NOT NULL,
    `user_name`       VARCHAR(20) NOT NULL DEFAULT '',
    `email`           VARCHAR(50) NOT NULL DEFAULT '',
    `password_hash`   CHAR(60) NOT NULL COMMENT 'hash storage for bcrypt',
    `active`          TINYINT UNSIGNED NOT NULL DEFAULT 1,

    `added`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by`        CHAR(16) NOT NULL DEFAULT '',
    `updated`         DATETIME DEFAULT NULL,
    `updated_by`      CHAR(16) NOT NULL DEFAULT '',

    KEY `idx_first_name` (`first_name`),
    KEY `idx_last_name` (`last_name`),
    UNIQUE KEY `uidx_user_name` (`user_name`),
    UNIQUE KEY `uidx_email` (`email`),
    KEY `idx_active` (`active`),

    PRIMARY KEY (`user_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `administration`
(
    `administration_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_name`            CHAR(16) NOT NULL DEFAULT '',
    `password_hash`        CHAR(60) NOT NULL,
    `email`                VARCHAR(50) NOT NULL DEFAULT '',
    `admin_level`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `active`               TINYINT UNSIGNED NOT NULL DEFAULT 1,

    UNIQUE KEY `uidx_user_name` (`user_name`),
    KEY `idx_active` (`active`),

    PRIMARY KEY (`administration_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT 'for internal administation';
