<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BigMonster implementation : © Nicolas Matton (nicolas@locla.be)
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * bigmonster.action.php
 *
 * BigMonster main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/bigmonster/bigmonster/myAction.html", ...)
 *
 */
  
  
  class action_bigmonster extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "bigmonster_bigmonster";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
    public function selectTeam()
    {
        self::setAjaxMode();
        $player_id = self::getArg( "player_id", AT_posint, true );
        $team_player_id = self::getArg( "team_player_id", AT_posint, true );
        $result = $this->game->selectTeamPlayer( $player_id, $team_player_id );
        self::ajaxResponse( );
    }

    public function selectStartingExplorer()
    {
        self::setAjaxMode();     
        $tile = self::getArg( "tile", AT_posint, true );
        $result = $this->game->selectStartingExplorer( $tile );
        self::ajaxResponse( );
    }

    public function selectShip()
    {
        self::setAjaxMode();     
        $ship_player_id = self::getArg( "ship_player_id", AT_posint, true );
        $rem_cards = self::getArg( "rem_cards", AT_numberlist, true );
        $sel_card = self::getArg( "sel_card", AT_posint, true );
        $result = $this->game->selectShip( $ship_player_id, $rem_cards , $sel_card);
        self::ajaxResponse( );
    }

    public function var_selectTile()
    {
        self::setAjaxMode();     
        $source_row = self::getArg( "source_row", AT_posint, true );
        $rem_cards = self::getArg( "rem_cards", AT_numberlist, true );
        $sel_card = self::getArg( "sel_card", AT_posint, true );
        $sel_action = self::getArg( "sel_action", AT_posint, true );
        $result = $this->game->var_selectTile($rem_cards, $sel_card, $source_row, $sel_action);
        self::ajaxResponse( );
    }

    public function placeTile()
    {
        self::setAjaxMode();
        $whichMove = self::getArg( "whichMove", AT_numberlist, true );
        $result = $this->game->placeTile( $whichMove );
        self::ajaxResponse( );
    }

      /*
   * loadBug: in studio, type loadBug(20762) into the table chat to load a bug report from production
   * client side JavaScript will fetch each URL below in sequence, then refresh the page
   */
  public function loadBug($reportId)
  {
    $db = explode('_', self::getUniqueValueFromDB("SELECT SUBSTRING_INDEX(DATABASE(), '_', -2)"));
    $game = $db[0];
    $tableId = $db[1];
    self::notifyAllPlayers('loadBug', "Trying to load <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a>", [
      'urls' => [
        // Emulates "load bug report" in control panel
        "https://studio.boardgamearena.com/admin/studio/getSavedGameStateFromProduction.html?game=$game&report_id=$reportId&table_id=$tableId",
        
        // Emulates "load 1" at this table
        "https://studio.boardgamearena.com/table/table/loadSaveState.html?table=$tableId&state=1",
        
        // Calls the function below to update SQL
        "https://studio.boardgamearena.com/1/$game/$game/loadBugSQL.html?table=$tableId&report_id=$reportId",
        
        // Emulates "clear PHP cache" in control panel
        // Needed at the end because BGA is caching player info
        "https://studio.boardgamearena.com/admin/studio/clearGameserverPhpCache.html?game=$game",
      ]
    ]);
  }

  /*
   * loadBugSQL: in studio, this is one of the URLs triggered by loadBug() above
   */
  public function loadBugSQL($reportId)
  {
    $studioPlayer = self::getCurrentPlayerId();
    $players = $this->playerManager->getPlayers();

    // Change for your game
    // We are setting the current state to match the start of a player's turn if it's already game over
    $sql = [
      "UPDATE global SET global_value=" . ST_MOVE . " WHERE global_id=1 AND global_value=" . ST_BGA_GAME_END
    ];
    foreach ($players as $player) {
      $pId = $player->getId();
      // All game can keep this SQL
      $sql[] = "UPDATE player SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE global SET global_value=$studioPlayer WHERE global_value=$pId";
      $sql[] = "UPDATE stats SET stats_player_id=$studioPlayer WHERE stats_player_id=$pId";

      // Change the below SQL to update the specific tables for your game
      $sql[] = "UPDATE card SET card_location_arg=$studioPlayer WHERE card_location_arg=$pId";
      $sql[] = "UPDATE piece SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE log SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE log SET action_arg=REPLACE(action_arg, $pId, $studioPlayer)";

      // This could be improved, it assumes you had sequential studio accounts before loading
      // e.g., quietmint0, quietmint1, quietmint2, etc. are at the table
      $studioPlayer++;
    }
    $msg = "<b>Loaded <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a></b><hr><ul><li>" . implode(';</li><li>', $sql) . ';</li></ul>';
    self::warn($msg);
    self::notifyAllPlayers('message', $msg, []);

    foreach ($sql as $q) {
      self::DbQuery($q);
    }
    self::reloadPlayersBasicInfos();
  }
    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

