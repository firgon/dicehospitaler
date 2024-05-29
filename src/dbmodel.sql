
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- DiceHospitalER implementation : © <firgon> <emmanuel.albisser@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

ALTER TABLE `player` ADD `nb_stetos` INT UNSIGNED NOT NULL DEFAULT '1',    ADD `used_stetos` INT UNSIGNED NOT NULL DEFAULT '0',
    ADD `nb_bloods` INT UNSIGNED NOT NULL DEFAULT '0',    ADD `used_bloods` INT UNSIGNED NOT NULL DEFAULT '0',
    ADD `nb_nurses` INT UNSIGNED NOT NULL DEFAULT '0',    ADD `nb_deads` INT UNSIGNED NOT NULL DEFAULT '0',
    ADD `score_wards` INT(4) NOT NULL DEFAULT '0',    ADD `score_critical` INT(4) NOT NULL DEFAULT '0',
    ADD `score_nurses` INT(4) NOT NULL DEFAULT '0',    ADD `score_cardiologist_1` INT(4) NOT NULL DEFAULT '0',
    ADD `score_cardiologist_2` INT(4) NOT NULL DEFAULT '0',    ADD `score_radiologist` INT(4) NOT NULL DEFAULT '0',
    ADD `score_epidemiologist` INT(4) NOT NULL DEFAULT '0',     ADD `score_dead` INT(4) NOT NULL DEFAULT '0',
    ADD `epidemiologist_rooms` JSON ;

CREATE TABLE IF NOT EXISTS `alicat` (    `player_score` INT NOT NULL DEFAULT '0',   `nb_stetos` INT UNSIGNED NOT NULL DEFAULT '1',   `used_stetos` INT UNSIGNED NOT NULL DEFAULT '0',
   `nb_bloods` INT UNSIGNED NOT NULL DEFAULT '0',   `used_bloods` INT UNSIGNED NOT NULL DEFAULT '0',   `nb_nurses` INT UNSIGNED NOT NULL DEFAULT '0',
   `nb_deads` INT UNSIGNED NOT NULL DEFAULT '0',   `score_wards` INT UNSIGNED NOT NULL DEFAULT '0',   `score_critical` INT UNSIGNED NOT NULL DEFAULT '0',
   `score_nurses` INT UNSIGNED NOT NULL DEFAULT '0',   `score_cardiologist_1` INT UNSIGNED NOT NULL DEFAULT '0',   `score_cardiologist_2` INT UNSIGNED NOT NULL DEFAULT '0',
   `score_radiologist` INT UNSIGNED NOT NULL DEFAULT '0',   `score_epidemiologist` INT UNSIGNED NOT NULL DEFAULT '0',   `score_dead` INT NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `rooms` (
   `room_id` SMALLINT UNSIGNED NOT NULL,
   `player_id` int(10) unsigned NOT NULL,
   `value` SMALLINT(3) NOT NULL,
   `decoration` varchar(35) NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `wards` (
   `ward_id` SMALLINT UNSIGNED NOT NULL,
   `player_id` int(10) unsigned NOT NULL,
   `VP` SMALLINT(3) NOT NULL DEFAULT '0',
   `nurse` SMALLINT(3) NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `card` (
   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `card_type` varchar(16) NOT NULL,
   `card_type_arg` int(11) NOT NULL,
   `card_location` varchar(16) NOT NULL,
   `card_location_arg` int(11) NOT NULL,
   PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;