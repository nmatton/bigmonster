
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BigMonster implementation : © Nicolas Matton (nicolas@locla.be)
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

 -- add info about team selected
 ALTER TABLE `player` ADD `team_sel` int(10) NOT NULL DEFAULT 0;
 ALTER TABLE `player` ADD `team` int(10) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  `mutation` int(2) DEFAULT 0,
  `board_x` int(2),
  `board_y` int(2),
  `last_play` int(1) DEFAULT 0,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `explorers` (
  `explorer_id` int(2) unsigned NOT NULL,
  `player_id` int(11) NOT NULL,
  `selected` int(1) NOT NULL,
  PRIMARY KEY (`explorer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `medals` (
  `medal_id` int(2) unsigned NOT NULL,
  `type` varchar(16) NOT NULL,
  `player_id` varchar(50) NOT NULL,
  PRIMARY KEY (`medal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


