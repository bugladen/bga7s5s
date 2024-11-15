
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
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  `card_serialized` text NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `events` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_priority` tinyint NOT NULL,
  `event_serialized` text NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Player fields for selected deck
ALTER TABLE `player` 
  ADD `deck_source` varchar(20) NULL,
  ADD `deck_id` varchar(20) NULL,
  ADD `leader_card_id` smallint NULL,
  ADD `selected_scheme_id` smallint NULL,
  ADD `selected_character_id` smallint NULL;

