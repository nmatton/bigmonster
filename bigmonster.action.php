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
    
    public function loadBugSQL() {
      self::setAjaxMode();
      $reportId = (int) self::getArg('report_id', AT_int, true);
      $this->game->loadBugSQL($reportId);
      self::ajaxResponse();
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
  

