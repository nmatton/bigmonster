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
 * gameoptions.inc.php
 *
 * BigMonster game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in bigmonster.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    101 => array(
        'name' => totranslate('Play mode'),
        'values' => array(
            1 => array(
                'name' => totranslate('Individual mode'),
                'description' => totranslate('Free for all mode, be the best to win against all other players'),
                'tmdisplay' => ('Individual')
            ),
            2 => array(
                'name' => totranslate('Team mode'),
                'description' => totranslate('Play in teams of 2. The final team score is the lowest score of team members'),
                'tmdisplay' => totranslate('Team mode')
            ),
        ),
        'displaycondition' => array( 
            // Note: do not display this option unless these conditions are met
            array(
                'type' => 'minplayers ',
                'value' => 4
            ),
            array( 
                'type' => 'otheroption',
                'id' => 201, // ELO OFF hardcoded framework option
                'value' => 1, // 1 if OFF
            )
        ),

        'startcondition' => array(
            1 => array(),
            2 => array(
                array(
                    'type' => 'minplayers',
                    'value' => 4,
                    'message' => totranslate('At least 4 players are required for team play.')
                )
            ),
        ),
        'notdisplayedmessage' => totranslate('Team mode available for table of 4 or 6 players with ELO disabled')
    ),

    102 => array(
        'name' => totranslate('Hide live scores'),
        'values' => array(
            1 => array(
                'name' => totranslate('No'),
                'description' => totranslate('Show live scores'),
                'tmdisplay' => ('')
            ),
            2 => array(
                'name' => totranslate('Yes'),
                'description' => totranslate('Hide live scores'),
                'tmdisplay' => totranslate('Hide live scores'),
                'nobeginner' => true
            )
        )
    )
);


