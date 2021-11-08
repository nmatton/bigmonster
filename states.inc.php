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

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
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
        "name" => "explorerSelection",
        "description" => clienttranslate('Other players must select an explorer'),
        "descriptionmyturn" => clienttranslate('${you} must select an explorer'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "selectStartingExplorer" ),
        "transitions" => array( "newRound" => 3 , "var_newTurn" => 9),
        "action" => "st_MultiPlayerInit",
        "args" => "argexplorerSelection"
    ),

    3 => array(
        "name" => "newRound",
        "description" => clienttranslate('Upkeep...'),
        "type" => "game",
        "action" => "st_newRound",
        "transitions" => array( "tileSelection" => 4 )
    ),

    4 => array(
        "name" => "tileSelection",
        "description" => clienttranslate('Other players must select a tile and then a ship to give the rest of your hand'),
        "descriptionlasttile" => clienttranslate('Other players must select a tile'),
        "descriptionmyturn" => clienttranslate('${you} must select a tile and then a ship to give the rest of your hand'),
        "descriptionmyturnlasttile" => clienttranslate('${you} must select a tile'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "selectTile", "selectShip" ),
        "action" => "st_MultiPlayerInit",
        "args" => "argtileSelection",
        "transitions" => array( "placeTile" => 5 )
    ),

    5 => array(
        "name" => "placeTile",
        "description" => clienttranslate('Other players must place the selected tile'),
        "descriptionmyturn" => clienttranslate('${you} must place the tile on your board'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "placeTile" ),
        "action" => "st_MultiPlayerInit",
        "args" => "argplaceTile",
        "transitions" => array( "endTurn" => 6 )
    ),

    6 => array(
        "name" => "endTurn",
        "description" => clienttranslate('Upkeep...'),
        "type" => "game",
        "action" => "st_endTurn",
        "updateGameProgression" => true,
        "transitions" => array( "newRound" => 3, "tileSelection" => 4, "bmExploTileSelection" => 7, "pregameEnd" => 98 )
    ),

    7 => array(
        "name" => "bmExploTileSelection",
        "description" => clienttranslate('Black male explorer : ${actplayer} can select a tile of the discard pile'),
        "descriptionmyturn" => clienttranslate('Black male explorer : ${you} must select a tile from the discard pile'),
        "type" => "activeplayer",
        "possibleactions" => array( "selectTile", "selectShip"),
        "action" => "st_bmExploTileSelection",
        "args" => "argbmExploTileSelection",
        "transitions" => array( "bmExploTilePlacement" => 8, "zombiePass" => 98 )
    ),

    8 => array(
        "name" => "bmExploTilePlacement",
        "description" => clienttranslate('Black male explorer : ${actplayer} can place a tile of the discard pile'),
        "descriptionmyturn" => clienttranslate('Black male explorer : ${you} must place a tile from the discard pile'),
        "type" => "activeplayer",
        "possibleactions" => array( "placeTile" ),
        "action" => "st_bmExploTilePlacement",
        "args" => "argbmExploTilePlacement",
        "transitions" => array( "pregameEnd" => 98, "zombiePass" => 98 )
    ),

    9 => array(
        "name" => "var_newTurn",
        "description" => clienttranslate('Upkeep...'),
        "type" => "game",
        "action" => "st_var_newTurn",
        "updateGameProgression" => true,
        "transitions" => array( "var_tileSelection" => 10)
    ),

    10 => array(
        "name" => "var_tileSelection",
        "description" => clienttranslate('${actplayer} must select a tile to play'),
        "descriptiondiscard" => clienttranslate('${actplayer} must select a tile to discard'),
        "descriptionmyturn" => clienttranslate('${you} must select a tile to play'),
        "descriptionmyturndiscard" => clienttranslate('${you} must select a tile to discard'),
        "type" => "activeplayer",
        "possibleactions" => array( "var_SelectTile" ),
        "args" => "argvar_tileSelection",
        "transitions" => array( "var_placeTile" => 11)
    ),

    11 => array(
        "name" => "var_placeTile",
        "description" => clienttranslate('${actplayer} must place the selected tile'),
        "descriptionmyturn" => clienttranslate('${you} must place the tile on your board'),
        "type" => "activeplayer",
        "possibleactions" => array( "placeTile" ),
        "args" => "argplaceTile",
        "transitions" => array( "var_endTurn" => 12)
    ),

    12 => array(
        "name" => "var_endTurn",
        "description" => clienttranslate('Upkeep...'),
        "type" => "game",
        "action" => "st_endTurn",
        "transitions" => array( "var_tileSelection" => 10, "var_newTurn" => 9, "pregameEnd" => 98)
    ),

    98 => array(
        "name" => "pregameEnd",
        "description" => clienttranslate('Upkeep...'),
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



