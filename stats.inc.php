<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BigMonster implementation : © Nicolas Matton (nicolas@locla.be)
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * BigMonster game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    /*
    // Statistics global to table
    "table" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),

    /*    Examples:


        "table_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("table test stat 1"), 
                                "type" => "int" ),
                                
        "table_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("table test stat 2"), 
                                "type" => "float" )
        
    ),
    */
    // Statistics for each player
    "player" => array(

        "explorer" => array("id"=> 10,
                    "name" => totranslate("Explorer"),
                    "type" => "int" ),
        "pts_ice" => array("id"=> 11,
                    "name" => totranslate("Points of ice tiles"),
                    "type" => "int" ),
        "pts_bm" => array("id"=> 12,
                    "name" => totranslate("Points of big monster tiles"),
                    "type" => "int" ),
        "pts_lava" => array("id"=> 13,
                    "name" => totranslate("Points of lava tiles"),
                    "type" => "int" ),
        "pts_grassland" => array("id"=> 14,
                    "name" => totranslate("Points of grassland tiles"),
                    "type" => "int" ),
        "pts_swamp" => array("id"=> 15,
                    "name" => totranslate("Points of swamp tiles"),
                    "type" => "int" ),
        "pts_diams" => array("id"=> 16,
                    "name" => totranslate("Points of diamonds"),
                    "type" => "int" ),
        "pts_explo" => array("id"=> 17,
                    "name" => totranslate("Points of explorer tile"),
                    "type" => "int" ),
        "pts_medals" => array("id"=> 18,
                    "name" => totranslate("Points of medals"),
                    "type" => "int" ),
        "nbr_blue" => array("id"=> 19,
                    "name" => totranslate("Number of blue diamonds"),
                    "type" => "int" ),
        "nbr_red" => array("id"=> 20,
                    "name" => totranslate("Number of red diamonds"),
                    "type" => "int" ),
        "nbr_green" => array("id"=> 21,
                    "name" => totranslate("Number of green diamonds"),
                    "type" => "int" )
    
    ),

    "value_labels" => array(
        10 => array(
            1 => array(totranslate('male blue')),
            2 => array(totranslate('black female')),
            3 => array(totranslate('black male')),
            4 => array(totranslate('red female')),
            5 => array(totranslate('purple female')),
            6 => array(totranslate('purple male')),
            7 => array(totranslate('green female')),
            8 => array(totranslate('orange female')),
            9 => array(totranslate('red male')),
            10 => array(totranslate('blue female')),
            11 => array(totranslate('green male')),
            12 => array(totranslate('orange male')),
        )
    )

);
