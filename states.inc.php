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
 * states.inc.php
 *
 * BigMonster game states description
 *
 */


//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    

    2 => array(
        "name" => "teamSelection",
        "description" => clienttranslate('Other players must choose their team'),
        "descriptionmyturn" => clienttranslate('${you} must choose your team'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "selectTeam" ),
        "transitions" => array( "explorerSelection" => 3, "gameEnd" => 99),
        "action" => "st_teamSelection"
    ),

    3 => array(
        "name" => "explorerSelection",
        "description" => clienttranslate('Other players must select an explorer'),
        "descriptionmyturn" => clienttranslate('${you} must select an explorer'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "selectStartingExplorer" ),
        "transitions" => array( "newRound" => 4 , "var_newTurn" => 10),
        "action" => "st_MultiPlayerInit",
        "args" => "argexplorerSelection"
    ),

    4 => array(
        "name" => "newRound",
        "description" => clienttranslate('New round preparation...'),
        "type" => "game",
        "action" => "st_newRound",
        "transitions" => array( "tileSelection" => 5 )
    ),

    5 => array(
        "name" => "tileSelection",
        "description" => clienttranslate('Other players must select a tile and then a ship to give the rest of your hand'),
        "descriptionlasttile" => clienttranslate('Other players must select a tile'),
        "descriptionmyturn" => clienttranslate('${you} must select a tile and then a ship to give the rest of your hand'),
        "descriptionmyturnlasttile" => clienttranslate('${you} must select a tile'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "selectTile", "selectShip" ),
        "action" => "st_MultiPlayerInit",
        "args" => "argtileSelection",
        "transitions" => array( "placeTile" => 6 )
    ),

    6 => array(
        "name" => "placeTile",
        "description" => clienttranslate('Other players must place the selected tile'),
        "descriptionmyturn" => clienttranslate('${you} must place the tile on your board'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "placeTile" ),
        "action" => "st_MultiPlayerInit",
        "args" => "argplaceTile",
        "transitions" => array( "endTurn" => 7 )
    ),

    7 => array(
        "name" => "endTurn",
        "description" => clienttranslate('Ending turn...'),
        "type" => "game",
        "action" => "st_endTurn",
        "updateGameProgression" => true,
        "transitions" => array( "newRound" => 4, "tileSelection" => 5, "bmExploTileSelection" => 8, "pregameEnd" => 98 )
    ),

    8 => array(
        "name" => "bmExploTileSelection",
        "description" => clienttranslate('Black male explorer : ${actplayer} can select a tile of the discard pile'),
        "descriptionmyturn" => clienttranslate('Black male explorer : ${you} must select a tile from the discard pile'),
        "type" => "activeplayer",
        "possibleactions" => array( "selectTile", "selectShip"),
        "action" => "st_bmExploTileSelection",
        "args" => "argbmExploTileSelection",
        "transitions" => array( "bmExploTilePlacement" => 9, "zombiePass" => 98 )
    ),

    9 => array(
        "name" => "bmExploTilePlacement",
        "description" => clienttranslate('Black male explorer : ${actplayer} can place a tile of the discard pile'),
        "descriptionmyturn" => clienttranslate('Black male explorer : ${you} must place a tile from the discard pile'),
        "type" => "activeplayer",
        "possibleactions" => array( "placeTile" ),
        "action" => "st_bmExploTilePlacement",
        "args" => "argbmExploTilePlacement",
        "transitions" => array( "pregameEnd" => 98, "zombiePass" => 98 )
    ),

    10 => array(
        "name" => "var_newTurn",
        "description" => clienttranslate('Preparing new turn...'),
        "type" => "game",
        "action" => "st_var_newTurn",
        "updateGameProgression" => true,
        "transitions" => array( "var_tileSelection" => 11)
    ),

    11 => array(
        "name" => "var_tileSelection",
        "description" => clienttranslate('${actplayer} must select a tile to play'),
        "descriptiondiscard" => clienttranslate('${actplayer} must select a tile to discard'),
        "descriptionmyturn" => clienttranslate('${you} must select a tile to play'),
        "descriptionmyturndiscard" => clienttranslate('${you} must select a tile to discard'),
        "type" => "activeplayer",
        "possibleactions" => array( "var_SelectTile" ),
        "args" => "argvar_tileSelection",
        "transitions" => array( "var_placeTile" => 12)
    ),

    12 => array(
        "name" => "var_placeTile",
        "description" => clienttranslate('${actplayer} must place the selected tile'),
        "descriptionmyturn" => clienttranslate('${you} must place the tile on your board'),
        "type" => "activeplayer",
        "possibleactions" => array( "placeTile" ),
        "args" => "argplaceTile",
        "transitions" => array( "var_endTurn" => 13)
    ),

    13 => array(
        "name" => "var_endTurn",
        "description" => clienttranslate('Ending turn...'),
        "type" => "game",
        "action" => "st_endTurn",
        "transitions" => array( "var_tileSelection" => 11, "var_newTurn" => 10, "pregameEnd" => 98)
    ),

    98 => array(
        "name" => "pregameEnd",
        "description" => clienttranslate('Counting final score...'),
        "type" => "game",
        "action" => "st_pregameEnd",
        "transitions" => array( "gameEnd" => 99)
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



