
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- SeventhSeaCityOfFiveSails implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(20) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  `card_serialized` text NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `events` (
  `event_id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `event_priority` tinyint NOT NULL,
  `event_serialized` text NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `duel` (
  `duel_id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `challenging_player_id` int NOT NULL,
  `challenger_id` int NOT NULL,
  `defending_player_id` int NOT NULL,
  `defender_id` int NOT NULL,
  `challenger_threat` tinyint NOT NULL,
  `defender_threat` tinyint NOT NULL,
  PRIMARY KEY (`duel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `duel_round` (
  `duel_round_id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `duel_id` tinyint unsigned NOT NULL,
  `round` tinyint NOT NULL,
  `player_id` int NOT NULL,
  `actor_id` int NOT NULL,
  `starting_challenger_threat` tinyint NOT NULL,
  `starting_defender_threat` tinyint NOT NULL,
  `technique_id` varchar(50) NULL,
  `technique_name` varchar(500) NULL,
  `technique_riposte` tinyint NULL,
  `technique_parry` tinyint NULL,
  `technique_thrust` tinyint NULL,
  `maneuver_id` varchar(50) NULL,
  `maneuver_name` varchar(500) NULL,
  `maneuver_riposte` tinyint NULL,
  `maneuver_parry` tinyint NULL,
  `maneuver_thrust` tinyint NULL,
  `combat_card_id` int NULL,
  `gambled` bit(1) NULL,
  `combat_riposte` tinyint NULL,
  `combat_parry` tinyint NULL,
  `combat_thrust` tinyint NULL,
  `ending_challenger_threat` tinyint NULL,
  `ending_defender_threat` tinyint NULL,
  PRIMARY KEY (`duel_round_id`),
  FOREIGN KEY (`duel_id`) REFERENCES `duel` (`duel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Player fields for selected deck and cards
ALTER TABLE `player` 
  ADD `turn_order` tinyint NULL,
  ADD `deck_source` varchar(20) NULL,
  ADD `deck_id` varchar(20) NULL,
  ADD `leader_card_id` smallint NULL,
  ADD `selected_scheme_id` smallint NULL,
  ADD `selected_character_id` smallint NULL;

