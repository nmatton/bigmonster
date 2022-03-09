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
  * bigmonster.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class BigMonster extends Table
{
	function __construct( )
	{
        //**  Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels( array( 
                         "currentRound" => 10,
                         "currentTurn" => 11,
                         "active_row" => 13,
                         "first_player" => 14,
                         "teamdefined" => 15,
                         "explotileplacement" => 16,
                         "endcountdowntimestamp" => 17,
                         "playmode" => 101,
                         "hidescore" => 102,
                         '3pdraft' => 103) );

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
        $this->initLocalDB();
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "bigmonster";
    }	

    /*
        //** setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];  
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        self::setGameStateInitialValue( 'currentRound', 0 );
        self::setGameStateInitialValue( 'currentTurn', 0 );
        self::setGameStateInitialValue( 'teamdefined', 0 ); // 0 = team not defined, 1 = team defined
        self::setGameStateInitialValue( 'active_row', 0 ); // usefull for 2 and 3 players mode - active row of tile (where user can pick a card). 1 = top row; 2 = bottom row
        self::setGameStateInitialValue( 'explotileplacement', 0 ); // set to 1 when 19th tile is being placed
        self::setGameStateInitialValue( 'endcountdowntimestamp', -1 ); // value between 0 and 1000, define the end time of the countdown
        
        // Create cards
        $cards = array ();
        foreach ( $this->tiles_info as $type_id => $tile_type ) {
            for ($kind_monster=1; $kind_monster <= $tile_type['variety']; $kind_monster++) { 
                if ($type_id == 4) {
                    // lava monsters
                    switch ($kind_monster) {
                        case 1:
                            $nbr_players = array(
                                '2' => 2,
                                '3' => 4,
                                '4' => 2,
                                '5' => 3,
                                '6' => 4);
                                break;
                        case 2:
                            $nbr_players = array(
                                '2' => 4,
                                '3' => 6,
                                '4' => 4,
                                '5' => 5,
                                '6' => 6);
                                break;
                        case 3:
                            $nbr_players = array(
                                '2' => 7,
                                '3' => 11,
                                '4' => 7,
                                '5' => 9,
                                '6' => 11);
                            break;
                        default:
                            $nbr_players = array(
                                '2' => 2,
                                '3' => 3,
                                '4' => 2,
                                '5' => 3,
                                '6' => 3);
                        break;
                    }
                    $nbr = $nbr_players[self::getPlayersNumber()];
                } elseif ($type_id == 8) {
                    // rune
                    switch ($kind_monster) {
                        case 1:
                            $nbr_players = array(
                                '2' => 1,
                                '3' => 2,
                                '4' => 1,
                                '5' => 2,
                                '6' => 2);
                            break;
                        case 2:
                            $nbr_players = array(
                                '2' => 2,
                                '3' => 3,
                                '4' => 2,
                                '5' => 2,
                                '6' => 3);
                            break;
                        case 4:
                            $nbr_players = array(
                                '2' => 1,
                                '3' => 2,
                                '4' => 1,
                                '5' => 2,
                                '6' => 2);
                            break;
                        case 5:
                            $nbr_players = array(
                                '2' => 2,
                                '3' => 4,
                                '4' => 2,
                                '5' => 3,
                                '6' => 4);
                            break;
                        case 6:
                            $nbr_players = array(
                                '2' => 2,
                                '3' => 3,
                                '4' => 2,
                                '5' => 2,
                                '6' => 3);
                            break;
                        default:
                            $nbr_players = array(
                                '2' => 2,
                                '3' => 2,
                                '4' => 2,
                                '5' => 2,
                                '6' => 2);
                            break;
                    }
                    $nbr = $nbr_players[self::getPlayersNumber()];
                } elseif ($type_id == 6) {
                    // grassland
                    switch ($kind_monster) {
                        case 1:
                            $nbr_players = array(
                                '2' => 1,
                                '3' => 1,
                                '4' => 1,
                                '5' => 0,
                                '6' => 1);
                            break;
                        case 3:
                            $nbr_players = array(
                                '2' => 0,
                                '3' => 1,
                                '4' => 0,
                                '5' => 1,
                                '6' => 1);
                            break;
                        case 6:
                            $nbr_players = array(
                                '2' => 0,
                                '3' => 1,
                                '4' => 0,
                                '5' => 1,
                                '6' => 1);
                            break;
                        case 9:
                            $nbr_players = array(
                                '2' => 0,
                                '3' => 1,
                                '4' => 0,
                                '5' => 1,
                                '6' => 1);
                            break;                        
                        default:
                            $nbr_players = array(
                                '2' => 1,
                                '3' => 1,
                                '4' => 1,
                                '5' => 1,
                                '6' => 1);
                            break;
                    }
                    $nbr = $nbr_players[self::getPlayersNumber()];
                } else {
                    // the same number of tile for a given amount of players, whatever the type of monster
                    $nbr = $tile_type['nbr'][self::getPlayersNumber()];
                }
                $cards [] = array ('type' => $type_id,'type_arg' => $kind_monster,'nbr' => $nbr );
            }
        }
        
        $this->cards->createCards( $cards, 'deck' );

        // Shuffle deck
        $this->cards->shuffle('deck');

        // Attribute 2 possible explorers tiles to each players
        $tmp_explorer_list = $this->explorer_infos;
        if (self::getPlayersNumber() < 4) {
            // remove the "black explorers" when less than 4 players
            unset($tmp_explorer_list[2]);
            unset($tmp_explorer_list[3]);
        }
        foreach( $players as $player_id => $player )
        {
            for ($i=0; $i < 2; $i++) { 
                $avail_expl = array_keys($tmp_explorer_list);
                $explorer_id = $avail_expl[bga_rand( 0, count($avail_expl) - 1 )];
                unset($tmp_explorer_list[$explorer_id]);
                $this->setExplorer($explorer_id, $player_id);
            }            
        } 

        // Selection of random Medals
        // numbers (id) of medals corresponds to medals_info key in materials.inc.php
        $medals = array();
        if ($this->isTeamPlay()) {
            $possible_water_medal = [1,10,8];
            $possible_lava_medal = [5,4,6,2,8];
            while (count(array_unique(array_values($medals))) != 2) {
                $medals = array(
                    'water' => $possible_water_medal[bga_rand(0,2)],
                    'lava' => $possible_lava_medal[bga_rand(0,4)]);
            }
        } else {
            // def of possible medals (2 to 6 players - individual)
            $possible_grass_medal = [10,8];
            $possible_water_medal = [1,8];
            $possible_lava_medal = [8,5,2];
            $possible_ice_medal = [8,4,6];
            // selection of medal
            while (count(array_unique(array_values($medals))) != 4) {
                $medals = array(
                    'grass' => $possible_grass_medal[bga_rand( 0, 1 )],
                    'water' => $possible_water_medal[bga_rand( 0, 1 )],
                    'lava' => $possible_lava_medal[bga_rand( 0, 2 )],
                    'ice' => $possible_ice_medal[bga_rand( 0, 2 )]
                );
            }
        }

        $this->setMedalSelection($medals);
        
        // Initialize statistics
        $p_stats = $this->getStatList();
        foreach ($p_stats as $stat_name) {
            //var_dump($stat_name);
            if (($stat_name == "pts_team" and $this->isTeamPlay()) or $stat_name != "pts_team") {
                self::initStat( "player" , $stat_name, 0 );
            }
        }
        if ($this->isTeamPlay()) {
            self::initStat( "table" , "is_team", true );
        } else{
            self::initStat( "table" , "is_team", false );
        }

       

        //** End of the game initialization **/
    }

    /*
        //** getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player";
        $result['players'] = self::getCollectionFromDb( $sql );
        
        // Variables valid for 4+ games
        $result['hand'] = $this->custgetCardsInLocation( 'hand', $current_player_id );
        $result['cardsOnShips'] = $this->cards->countCardsByLocationArgs( 'onShip' );

        // Variables valid for 2 and 3 games
        $result['tiles_upper_row'] = $this->custgetCardsInLocation( 'hand', 1 );
        $result['tiles_lower_row'] = $this->custgetCardsInLocation( 'hand', 2 );
        $result['tilesonrows'] = $this->custgetCardsInLocation( 'hand');

        $result['active_row'] = self::getGameStateValue( 'active_row' );

        // Variables valid for all games variants
        if (self::getGameStateValue( 'currentRound' ) == 0 and self::getGameStateValue( 'currentTurn' ) == 0) {
            // we did not started first round yet
            $sql = "SELECT player_id, explorer_id FROM explorers WHERE player_id = $current_player_id AND selected = 1"; // retrieved the selected explorer tile ONLY for the current player (otherwise he can see other played explorer)
        } else {
            $sql = "SELECT player_id, explorer_id FROM explorers WHERE selected = 1"; // retrieved the selected explorer tile
        }
        $result['explorers'] = self::getCollectionFromDb( $sql );
        $result['cardsonboard'] = $this->getCardsOnBoard();
        $result['usedBoardPlaces'] = $this->getUsedBoard($this->getCardsOnBoard($current_player_id));
        $result['muted_cards'] = array();
        $mutated_monster = $this->get_mutation_level();
        foreach ($mutated_monster as $card_id => $value) {
            $result['muted_cards'][] = array(
                "player_id"=> $mutated_monster[$card_id]['card_location_arg'],
                "x"=> $mutated_monster[$card_id]['board_x'],
                "y"=> $mutated_monster[$card_id]['board_y'],
                "mutation_level" => intval($mutated_monster[$card_id]['mutation']));
        }
        $medals = $this->getMedalsInfo();
        foreach ($medals as $medal_id => $medal_details) {
            if ($medal_id > 10) {
                $info_id = floor($medal_id/10);
            } else {
                $info_id = $medal_id;
            }
            $medals[$medal_id]['back_id'] = $this->matching_pts_back_id[self::getGameStateValue( 'playmode' )][$this->medals_infos[$info_id]['pts']];
        }
        $result['medals'] = $medals;
        $result['first_player'] = self::getGameStateValue( 'first_player' );
        $result['teamdefined'] = self::getGameStateValue( 'teamdefined' );
        $result['teams'] = $this->get_teams();
        $pile_size = (self::getPlayersNumber() == 2) ? 4 : 6;
        $result['remaining_piles'] = intval($this->custcountCardInLocation( 'deck' )) / $pile_size;
        /*   *** HELP CONTENT FOR UI      ***
        */
        $result['help_monsters'] = $this->monster_infos;
        $result['help_explorers'] = $this->explorer_infos;
        $result['help_medals'] = $this->medals_infos;
        $result['isTeamPlay'] = $this->isTeamPlay();
        $result['hidescore'] = $this->hideScore();
        $result['is3pdraft'] = $this->is3pdraft();
        $result['cardsonshiporigin'] = $this->getCardsOnShipOrigin();
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        if (self::getGameStateValue( 'currentRound' ) === 0 and (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())) ) {
            return 0;
        } else {
            return floor((self::getGameStateValue( 'currentTurn' )+1)*100/17);
        }
    }

// ** Utility functions **

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    // ! ------ TESTING AND DEBUG FUNCTIONS ! //
	public function LoadDebug()
	{
		// These are the id's from the BGAtable I need to debug.
		$ids = [
			32557384,
			83985428,
			85602234,
			84795355
		];

		// Id of the first player in BGA Studio
		$sid = 2356482;
		
		foreach ($ids as $id) {
			// basic tables
			self::DbQuery("UPDATE player SET player_id=$sid WHERE player_id = $id" );
			self::DbQuery("UPDATE global SET global_value=$sid WHERE global_value = $id" );
			self::DbQuery("UPDATE stats SET stats_player_id=$sid WHERE stats_player_id = $id" );

			// 'other' game specific tables. example:
			// tables specific to your schema that use player_ids
			self::DbQuery("UPDATE card SET card_location_arg=$sid WHERE card_location_arg = $id" );
			
			++$sid;
		}
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

  
    // ! move some cards from deck to discard (in 2/3p variant, to get faster to end of phase 1 or 2) -> should be a multiple of dealed cards (4 in 2p mode or 6 in 3p mode) --- 
    public function movetodiscard($n_to_move)
    {
        $sql = "UPDATE card c JOIN (SELECT card_id FROM `card` WHERE card_location = 'deck' LIMIT $n_to_move) as d ON d.card_id = c.card_id SET card_location = 'discard'";
        $this->DbQuery($sql);
    }

    // ! move cards from hands of players to discard (for classic draft mode : to get faster to end of phase 1 or 2) --> need client F5 after this call
    public function movefromhandtodiscard($n_to_move)
    {
        foreach (array_keys($this->loadPlayersBasicInfos()) as $pid) {
            $sql = "UPDATE card c JOIN (SELECT card_id FROM `card` WHERE card_location = 'hand' AND card_location_arg = $pid LIMIT $n_to_move) as d ON d.card_id = c.card_id SET card_location = 'discard'";
            $this->DbQuery($sql);
        }
    }

    // ! run the team scoring notif ---
    public function sendTeamScoreNotif()
    {
        $breakdowns = array();
        foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
            // Compute score with details
            $score = $this->computeScore($player_id, true);
            // append to breakdowns
            $breakdowns[$player_id] = $score;
            // get diamonds counts
        }

        // TODO : In theory there can be multiple...
        $winner_id = self::getUniqueValueFromDB( "SELECT player_id FROM player ORDER BY player_score DESC, player_score_aux DESC LIMIT 1" );
        // compute team score if teamode is enabled
        if ($this->isTeamPlay()) {
            $team_scores = $this->computeTeamScore($breakdowns);
            $winning_team = array_values(array_keys($team_scores,max($team_scores)));
        } else {
            $team_scores = array();
            $winning_team = array();
        }
        $notif_data = array(
            "breakdowns" => $breakdowns,
            "winner_ids" => $winner_id,
            "team_scores" => $team_scores,
            "winning_team" => $winning_team
        );
        // send notif of end scores
        self::NotifyAllPlayers("endGame_scoring", '', $notif_data);
    }

    public function sendScoreBoard()
    {
        
        
        // set stats and compute total scores
        $breakdowns = array();
        foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
            // Compute score with details
            $score = $this->computeScore($player_id, true);
            // append to breakdowns
            $breakdowns[$player_id] = $score;
            // get diamonds counts
            $diams = $this->getDiamondsCount($player_id);
            // organise stats results
            $player_stat_results = array(
                "explorer" => $this->getUniqueValueFromDB("SELECT explorer_id FROM explorers WHERE player_id = $player_id AND selected = 1"),
                "pts_total" => $score['score'],
                "pts_ice" => $score['ice'],
                "pts_bm" => $score['bigmonster'],
                "pts_lava" => $score['lava'] ,
                "pts_grassland" => $score['grassland'] ,
                "pts_swamp" => $score['swamp'] ,
                "pts_diams" => $score['diams'] ,
                "pts_explo" => $score['explo'] ,
                "pts_medals" => $score['medals'] ,
                "nbr_blue" => $diams['blue'] ,
                "nbr_red" => $diams['red'] ,
                "nbr_green" => $diams['green']);
                $p_stats = $this->getStatList();
            foreach ($p_stats as $stat_name) {
                if ($stat_name != "pts_team") {
                    self::setStat( $player_stat_results[$stat_name], $stat_name, $player_id );
                }
            }
            // set the tie-breaker score
            self::DbQuery( "UPDATE player SET player_score_aux = ".$score['bigmonster']." WHERE player_id='".$player_id."'" );
        }
        
        $winner_id = array_keys(self::getNonEmptyCollectionFromDB( "SELECT sq1.player_id FROM (SELECT player_id, player_score_aux  FROM `player` WHERE player_score = (SELECT max(player_score) FROM player)) as sq1 WHERE sq1.player_score_aux = (SELECT max(sq2.player_score_aux) FROM (SELECT player_id, player_score_aux  FROM `player` WHERE player_score = (SELECT max(player_score) FROM player)) as sq2)" ));
        
        // compute team score if teamode is enabled
        if ($this->isTeamPlay()) {
            $team_scores = $this->computeTeamScore($breakdowns);
            $winning_team = array_keys($team_scores,max($team_scores));
            foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
                #set stat of team score
                $pteam = $this->get_teams()[$player_id];
                self::setStat( $team_scores[$pteam], "pts_team", $player_id );
            }
        } else {
            $team_scores = array();
            $winning_team = array();
        }
        if (count($winning_team) > 1) {
            // more than 1 team won -> check 1st tie-breaker : the best score of the 2nd teams' member
            $tie_scores_tmp = $this->computeTeamScore($breakdowns, true);
            $tie_scores = array();
            foreach ($winning_team as $tied_team) {
                $tie_scores[$tied_team] = $tie_scores_tmp[$tied_team];
            }
            $winning_team = array_keys($tie_scores,max($tie_scores));
            if (count($winning_team) > 1) {
                // tie again -> check 2nd tie-breaker : the most bigmonster point for teams
                $tie_scores = array();
                foreach ($winning_team as $tied_team) {
                    $team_player_ids = $this->getTeamPlayers($tied_team);
                    $score1 = $this->computeScore($team_player_ids[0], true);
                    $score2 = $this->computeScore($team_player_ids[1], true);
                    $tie_scores[$tied_team] = $score1['bigmonster'] + $score2['bigmonster'];
                }
                $winning_team = array_keys($tie_scores,max($tie_scores));
                if (count($winning_team) > 1) {
                    // teams are still tied -> tie is the final situation : record to DB
                    foreach ($winning_team as $tied_team) {
                        $team_player_ids = $this->getTeamPlayers($tied_team);
                        $tiescore = $tie_scores[$tied_team];
                        self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[0]."'" );
                        self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[1]."'" );
                    }
                } else {
                    // one team won with the 2nd tie-breaker : record to DB
                    $team_player_ids = $this->getTeamPlayers($winning_team);
                    $tiescore = $tie_scores[$winning_team];
                    self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[0]."'" );
                    self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[1]."'" );
                }
            } else {
                // record tie-breaker score to DB
                $team_player_ids = $this->getTeamPlayers($winning_team);
                $tiescore = $tie_scores[$winning_team];
                self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[0]."'" );
                self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[1]."'" );
            }
        }
        $notif_data = array(
            "breakdowns" => $breakdowns,
            "winner_ids" => $winner_id,
            "team_scores" => $team_scores,
            "winning_team" => $winning_team
        );
        // send notif of end scores
        // update score of BGA framework
        foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
            $score = $this->computeScore($player_id);
            if ($score['delta'] !== 0) {
                // score has changed
                $this->dbSetScore($player_id, $score['score']); // update DB
                self::NotifyAllPlayers("scoreUpdate", '', array(
                    "player_id" => $player_id,
                    "score" => $score['score'],
                    "score_delta" => $score['delta'])
                ); // notify players
            }
        }
        if ($this->isTeamPlay()) {
            foreach (array_unique(array_values($this->get_teams())) as $team ) {
                $players_ids = $this->getTeamPlayers($team);
                if (in_array($team, $winning_team)) {
                    $this->dbSetScore($players_ids[0], 1); // update DB
                    $this->dbSetScore($players_ids[1], 1); // update DB
                } else {
                    $this->dbSetScore($players_ids[0], 0); // update DB
                    $this->dbSetScore($players_ids[1], 0); // update DB

                }
            }
        }
        // send notif for the final score animation
        self::NotifyAllPlayers("endGame_scoring", '', $notif_data);
    }

    public function test()
    {
        $res = $this->checkMedalSuccess(7);
        print_r($res);
        $medal_id=81;
    }

    // ! ------ end of testing and debug functions /////

    // LOCAL DB INITIALIZATION

    
    public function initLocalDB()
    {
        $sql = 'SELECT * FROM player';
        $this->dbplayer = self::getCollectionFromDb($sql);
        $sql = 'SELECT * FROM explorers';
        $this->dbexplorers = self::getCollectionFromDb($sql);
        $sql = 'SELECT * FROM card';
        $this->dbcard = self::getCollectionFromDb($sql);
    }

    public function updateLocalDB($dbname)
    {
        if ($dbname == 'player') {
            $sql = 'SELECT * FROM player';
            $this->dbplayer = self::getCollectionFromDb($sql);
        } elseif ($dbname == 'explorers') {
            $sql = 'SELECT * FROM explorers';
            $this->dbexplorers = self::getCollectionFromDb($sql);
        } elseif ($dbname == 'card') {
            $sql = 'SELECT * FROM card';
            $this->dbcard = self::getCollectionFromDb($sql);
        }
    }

      /**
   * Multi-array search
   *
   * @param array $array
   * @param array $search
   * @return array
   */
    protected function multi_array_search($array, $search)
    {

        // Create the result array
        $result = array();
        // Iterate over each array element
        foreach ($array as $key => $value)
        {
            // Iterate over each search condition
            foreach ($search as $k => $v)
            {
                // If the array element does not meet the search condition then continue to the next element
                if (!isset($value[$k]) || $value[$k] != $v)
                {
                continue 2;
                }
            }
            // Add the array element's key to the result array
            $result[] = $key;
        }
        // Return the result array
        return $result;
    }


    public function isLastPlayerFinished()
    {
        // return true if all players except one have finished their turn
        return count($this->gamestate->getActivePlayerList()) == 1;
    }
    // DECK-custom functions using local DB data

    public function custgetCardsOfTypeInLocation($type, $type_arg=null, $location, $location_arg = null)
    {
        if (is_null($type_arg) and is_null($location_arg)) {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_type' => $type, 'card_location' => $location)));
        } elseif (is_null($type_arg) and !is_null($location_arg)) {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_type' => $type, 'card_location' => $location, 'card_location_arg' => $location_arg)));
        } elseif (!is_null($type_arg) and is_null($location_arg)) {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_type' => $type, 'card_location' => $location, 'card_type_arg' => $type_arg)));
        } else {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_type' => $type, 'card_location' => $location, 'card_location_arg' => $location_arg, 'card_type_arg' => $type_arg)));
        }
        $res=array();
        for ($i=0; $i < count($ids); $i++) {
            $cid = $this->dbcard[$ids[$i]]['card_id'];
            $res[$cid] = array('id' => $cid,
                'type' => $this->dbcard[$ids[$i]]['card_type'],
                'type_arg' => $this->dbcard[$ids[$i]]['card_type_arg'],
                'location' => $this->dbcard[$ids[$i]]['card_location'],
                'location_arg' => $this->dbcard[$ids[$i]]['card_location_arg']);
        }
        return $res;
    }

    public function custcountCardInLocation($location, $location_arg=null)
    {
        /* Return the number of cards in specified location.
            location (string): the location where to count the cards.
            location_arg (optional): if specified, count only cards with the specified "location_arg". */
        if (is_null($location_arg)) {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => $location)));
        } else {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => $location, 'card_location_arg' => $location_arg)));
        }
        return count($ids);
        
    }
    
    public function custgetCardsInLocation($location, $location_arg = null, $order_by = null)
    {
        /* Get all cards in specific location, as an array. Return an empty array if the location is empty.
            location (string): the location where to get the cards.
            location_arg (optional): if specified, return only cards with the specified "location_arg".
            order_by (optional): if specified, returned cards are ordered by the given database field. Example: "card_id" or "card_type". */
        # code...
        if (is_null($location_arg)) {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => $location)));
        } else {
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => $location, 'card_location_arg' => $location_arg)));
        }
        $res=array();
        for ($i=0; $i < count($ids); $i++) {
            $cid = $this->dbcard[$ids[$i]]['card_id'];
            $res[$cid] = array(
                'id' => $cid,
                'card_id' => $cid,
                'type' => $this->dbcard[$ids[$i]]['card_type'],
                'type_arg' => $this->dbcard[$ids[$i]]['card_type_arg'],
                'location' => $this->dbcard[$ids[$i]]['card_location'],
                'location_arg' => $this->dbcard[$ids[$i]]['card_location_arg']);
        }
        if (!is_null($order_by)) {
            usort($res, function ($a, $b) use ($order_by) {
                return $a[$order_by] <=> $b[$order_by];
            });
        }
        return $res;
    }

    public function custgetCard($card_id)
    {
        /* Get specific card information.
           Return null if this card is not found. */
        $id = array_values($this->multi_array_search($this->dbcard, array('card_id' => $card_id)));
        if (count($id) == 0) {
            return null;
        } else {
            $cid = $this->dbcard[$id[0]]['card_id'];
            return array(
                'id' => $cid,
                'card_id' => $cid,
                'type' => $this->dbcard[$id[0]]['card_type'],
                'type_arg' => $this->dbcard[$id[0]]['card_type_arg'],
                'location' => $this->dbcard[$id[0]]['card_location'],
                'location_arg' => $this->dbcard[$id[0]]['card_location_arg']);
        }
    }


    public function getCardsOnShipOrigin()
    {
        // return array with player_id as key and id of player that sent card to that player as value or 0 if the player has no card on his ship
        // example : Array ([2356487] => 2356482 [2356488] => 2356487 [2356486] => 0 [2356482] => 0 )
        try {
            $sql = 'SELECT player_id, cardsonshiporigin FROM player';
            return self::getCollectionFromDb($sql, true);
        } catch (\Throwable $th) {
            return array();
        }
    }


    function get_teams($updatedb=false)
    {
        // return array of players and their team
        // example : Array ( [2356487] => 0 [2356488] => 1 [2356486] => 1 [2356482] => 0 )
        if ($updatedb) {
            $sql = 'SELECT * FROM player';
            $this->dbplayer = self::getCollectionFromDb($sql);
        }
        return array_column($this->dbplayer,'team','player_id');
    }

    function getTeamPlayers($teamid)
    {
        // return array of players ids in team
        $ids = array_values($this->multi_array_search($this->dbplayer, array('team' => $teamid)));
        $res=array();
        for ($i=0; $i < count($ids); $i++) {
            $res[] = $this->dbplayer[$ids[$i]]['player_id'];
        }
        return $res;
    }


    // recursive flattening of array
    function array_flatten($array) { 
        if (!is_array($array)) { 
          return false; 
        } 
        $result = array(); 
        foreach ($array as $key => $value) { 
          if (is_array($value)) { 
            $result = array_merge($result, $this->array_flatten($value)); 
          } else { 
            $result = array_merge($result, array($key => $value));
          } 
        } 
        return $result; 
      }

    // check if teamplay mode is selected
    public function isTeamPlay() {
        return intval($this->getGameStateValue('playmode')) === 2;
    }

    // is 3p draft mode ?
    public function is3pdraft() {
        // 1 = draft mode ; 2 = variant mode
        return intval($this->getGameStateValue('3pdraft')) === 1;
    }

    // check if hiding score mode is selected
    public function hideScore() {
        return intval($this->getGameStateValue('hidescore')) === 2;
    }

    // get current state name
    protected function getStateName() {
        $state = $this->gamestate->state();
        return $state['name'];
    }
   // get score
    protected function dbGetScore($player_id) {
        return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
    }
    // set score
    protected function dbSetScore($player_id, $count) {
        $this->DbQuery("UPDATE player SET player_score='$count' WHERE player_id='$player_id'");
    }
    // set aux score (tie breaker)
    protected function dbSetAuxScore($player_id, $score) {
        $this->DbQuery("UPDATE player SET player_score_aux=$score WHERE player_id='$player_id'");
    }
    // increment score (can be negative too)
    protected function dbIncScore($player_id, $inc) {
        $count = $this->dbGetScore($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->dbSetScore($player_id, $count);
        }
        return $count;
    }

    protected function setExplorer($explorer_id, $player_id)
    {
        $sql = "INSERT INTO explorers (explorer_id, player_id, selected) VALUES (".$explorer_id.",". $player_id.", 0)";
        self::DbQuery( $sql );
    }

    protected function getExplorer()
    {
        $ids = array_values($this->multi_array_search($this->dbexplorers, array('selected' => 1)));
        $res=array();
        for ($i=0; $i < count($ids); $i++) {
            $pid = $this->dbexplorers[$ids[$i]]['player_id'];
            $res[$pid] = array('player_id' => $pid, 'explorer_id' => $this->dbexplorers[$ids[$i]]['explorer_id']);
        }
        return $res;
    }

    protected function getCardsOnBoard($player_id=null, $type=null, $last_play=0)
    {
        if (!is_null($type) and $type != 0) {
            //$sql = "SELECT `card_id`, `card_type`, `card_type_arg`, `mutation`, `board_x`, `board_y` FROM card WHERE card_location = 'board' AND card_location_arg = $player_id AND `card_type` = $type";
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'card_type' => $type)));
        } elseif (!is_null($player_id) and $player_id != 0 and $last_play == 0) {
            //$sql = "SELECT `card_id`, `card_type`, `card_type_arg`, `mutation`, `board_x`, `board_y` FROM card WHERE card_location = 'board' AND card_location_arg = $player_id";
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id)));
        } elseif (!is_null($player_id) and $player_id != 0 and $last_play > 0) {
            //$sql = "SELECT `card_id`, `card_location_arg`, `card_type`, `card_type_arg`, `mutation`, `board_x`, `board_y` FROM card WHERE card_location = 'board' AND card_location_arg = $player_id AND last_play = $last_play";
            $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'last_play' => $last_play)));
        } else {
            //$sql = "SELECT `card_id`, `card_location_arg`, `card_type`, `card_type_arg`, `mutation`, `board_x`, `board_y` FROM card WHERE card_location = 'board' AND last_play = $last_play";
            $ids1 = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board', 'last_play' => $last_play)));
            $ids2 = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board', 'last_play' => $last_play+2)));
            $ids = array_merge($ids1, $ids2);
        }
        $res=array();
        for ($i=0; $i < count($ids); $i++) {
            $cid = $this->dbcard[$ids[$i]]['card_id'];
            $res[$cid] = array('card_id' => $cid,
                'card_type' => $this->dbcard[$ids[$i]]['card_type'],
                'card_type_arg' => $this->dbcard[$ids[$i]]['card_type_arg'],
                'card_location_arg' => $this->dbcard[$ids[$i]]['card_location_arg'],
                'mutation' => $this->dbcard[$ids[$i]]['mutation'],
                'board_x' => $this->dbcard[$ids[$i]]['board_x'],
                'board_y' => $this->dbcard[$ids[$i]]['board_y']);
        }
        return $res;
    }

    protected function getUsedBoard($cardsOnBoard)
    {
        $used_pos = [[0,0],[1,0],[1,1],[0,1]]; // explorer tile
        foreach ($cardsOnBoard as $card_id => $card_info) {
            $x = $card_info['board_x'];
            $y = $card_info['board_y'];
            array_push($used_pos, [intval($x),intval($y)]);
            if ($card_info['card_type'] == 2) {
                // mutagenic tile -> horizontal
                array_push($used_pos, [intval($x+1),intval($y)]);
            } else {
                array_push($used_pos, [intval($x),intval($y+1)]);
            }
        }
        return $used_pos;
    }

    protected function moveCardFromHandToBoard($whichMove, $player_id)
    {
        //$sql = "SELECT 0, `card_id`, `card_type`, `card_type_arg` FROM card WHERE `card_location` = 'hand' AND card_location_arg = $player_id"; // added the 0 to easily access results -> removed from code using it
        //$card_data = self::getCollectionFromDb( $sql);
        $id = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'hand','card_location_arg' => $player_id)))[0];
        $res= array('card_id' =>  $this->dbcard[$id]['card_id'],
            'card_type' => $this->dbcard[$id]['card_type'],
            'card_type_arg' => $this->dbcard[$id]['card_type_arg']);
        $sql = "UPDATE card SET `board_x` = ".  intval($whichMove[0]).", `board_y` = ". intval($whichMove[1]) .", `card_location` = 'board', `last_play` = 1 WHERE `card_location` = 'hand' AND card_location_arg = $player_id" ;
        self::DbQuery( $sql );
        return $res;
    }

    protected function moveCardsFromTo($cards,$from_location, $to_location, $from_location_arg=null, $to_location_arg=null)
    {
        $cards_id_str = implode("','", $cards);
        if (!is_null($from_location_arg) or !is_null($to_location_arg)) {
            if (!is_null($from_location_arg) and !is_null($to_location_arg)) {
                # from_location_arg and from_location_arg filled
                $sql = "UPDATE card SET card_location = '$to_location', card_location_arg= $to_location_arg WHERE card_id IN ('$cards_id_str') and card_location = '$from_location' and card_location_arg = $from_location_arg";
            } elseif (!is_null($from_location_arg)) {
                # from_location_arg only filled
                $sql = "UPDATE card SET card_location = '$to_location' WHERE card_id IN ('$cards_id_str') and card_location = '$from_location' and card_location_arg = $from_location_arg";
            } else {
                # to_location_arg only filled
                $sql = "UPDATE card SET card_location = '$to_location', card_location_arg= $to_location_arg WHERE card_id IN ('$cards_id_str') and card_location = '$from_location'";
            }
        } else {
            $sql = "UPDATE card SET card_location = '$to_location' WHERE card_id IN ('$cards_id_str') and card_location = '$from_location'";
        }
        self::DbQuery( $sql );
    }

    protected function checkCardInHand($player_id, $sel_card)
    {
        //$sql = "SELECT 1 FROM card WHERE card_location = 'hand' AND card_location_arg = $player_id AND card_id = $sel_card";
        $ids = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'hand','card_location_arg' => $player_id, 'card_id' => $sel_card)));
        if (!empty($ids)) {
            return true;
        } else {
            return false;
        }
    }

    protected function check_mutated($whichMove, $card_data, $player_id)
    {
        // When a monster is place, check if there is mutagenic monster above or below
        
        // check if a simple mutagenic monster is placed below
        $x = intval($whichMove[0]) - 1;
        $y = intval($whichMove[1]) + 2;
        //$sql = "SELECT COUNT(*) as count FROM card WHERE `card_location` = 'board' AND card_location_arg = $player_id AND `board_x` = $x AND `board_y` = $y AND (`card_type` = 2 AND `card_type_arg` = 2)";
        $valid_upward_matagen = count(array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id,'board_x' => $x,'board_y' => $y,'card_type' => 2,'card_type_arg' => 2))));
        //$valid_upward_matagen = self::getUniqueValueFromDB( $sql );
        // check if explorer 5 and card located above the mutagenic
        //$sql = "SELECT explorer_id FROM explorers WHERE selected = 1 AND player_id = $player_id AND explorer_id = 5"; 
        $ids = array_values($this->multi_array_search($this->dbexplorers, array('selected' => 1,'player_id' => $player_id, 'explorer_id' => 5)));
        if (!empty($ids) and intval($whichMove[0]) == 1 and intval($whichMove[1]) == -2) {
            $valid_explorer_matagen = true;
        } else {
            $valid_explorer_matagen = false;
        }
        // check if a double mutagenic monster is placed above
        $x1 = intval($whichMove[0]) - 1;
        $y1 = intval($whichMove[1]) - 1;
        $x2 = intval($whichMove[0]);
        $y2 = intval($whichMove[1]) - 1;
        //$sql = "SELECT COUNT(*) as count FROM card WHERE `card_location` = 'board' AND card_location_arg = $player_id AND ((`board_x` = $x1 AND `board_y` = $y1) OR (`board_x` = $x2 AND `board_y` = $y2)) AND (`card_type` = 2 AND `card_type_arg` = 1)";
        $valid_downward_matagen1 = count(array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'card_type' => 2, 'card_type_arg' => 1, 'board_x' => $x1, 'board_y'=>$y1))));
        $valid_downward_matagen2 = count(array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'card_type' => 2, 'card_type_arg' => 1, 'board_x' => $x2, 'board_y'=>$y2))));
        $valid_downward_matagen = $valid_downward_matagen1 + $valid_downward_matagen2;
        //$valid_downward_matagen = self::getUniqueValueFromDB( $sql );
        if ($valid_upward_matagen > 0 or $valid_explorer_matagen or $valid_downward_matagen > 0) {
            if (($valid_upward_matagen > 0 or $valid_explorer_matagen) and $valid_downward_matagen > 0) {
                //double mutagen
                $sql = "UPDATE card SET mutation = 2 WHERE card_id = ".$card_data['card_id'];
                self::DbQuery( $sql );
            } else {
                //single mutagen
                $sql = "UPDATE card SET mutation = 1 WHERE card_id = ".$card_data['card_id'];
                self::DbQuery( $sql );
            }
            return true;
        }
        return false;

    }

    protected function check_mutation($whichMove, $card_data, $player_id)
    {
        $mutated_cards = array();
        if (intval($card_data['card_type_arg']) == 1) {
            // double downward mutagenic monster
            $x1 = intval($whichMove[0]);
            $y1 = intval($whichMove[1]) + 1;
            $x2 = intval($whichMove[0]) + 1;
            $y2 = intval($whichMove[1]) + 1;
            //$sql = "SELECT `card_id`, `board_x`, `board_y`, `card_type_arg` FROM card WHERE `card_location` = 'board' AND `card_location_arg` = $player_id AND `card_type` = 1 AND ((`board_x` = $x1 AND `board_y` = $y1) OR (`board_x` = $x2 AND `board_y` = $y2))";
            //$mutated_cards = self::getCollectionFromDb( $sql);
            $mutated_card1_id = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'card_type' => 1, 'board_x' => $x1, 'board_y'=>$y1)));
            $mutated_card2_id = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'card_type' => 1, 'board_x' => $x2, 'board_y'=>$y2)));
            if (empty($mutated_card1_id) and empty($mutated_card2_id)) {
                $mutated_cards = array();
            } else {
                $mutated_cards = array();
                if (!empty($mutated_card1_id)) {
                    $cid = $mutated_card1_id[0];
                    $mutated_cards[$cid] = array('card_id' => $cid , 'board_x' => $this->dbcard[$cid]['board_x'], 'board_y' => $this->dbcard[$cid]['board_y'], 'card_type_arg' => $this->dbcard[$cid]['card_type_arg']);
                }
                if (!empty($mutated_card2_id)) {
                    $cid = $mutated_card2_id[0];
                    $mutated_cards[$cid] = array('card_id' => $cid , 'board_x' => $this->dbcard[$cid]['board_x'], 'board_y' => $this->dbcard[$cid]['board_y'], 'card_type_arg' => $this->dbcard[$cid]['card_type_arg']);
                }
            }
        } else {
            // simple upward mutagenic monster (right side)
            $x = intval($whichMove[0]) + 1;
            $y = intval($whichMove[1]) - 2;
            //$sql = "SELECT `card_id`, `board_x`, `board_y`, `card_type_arg` FROM card WHERE `card_location` = 'board' AND `card_location_arg` = $player_id AND `card_type` = 1 AND `board_x` = $x AND `board_y` = $y";
            //$mutated_cards = self::getCollectionFromDb( $sql);
            $mutated_card_id = array_values($this->multi_array_search($this->dbcard, array('card_location' => 'board','card_location_arg' => $player_id, 'card_type' => 1, 'board_x' => $x, 'board_y'=>$y)));
            if (empty($mutated_card_id)) {
                $mutated_cards = array();
            } else {
                $mutated_cards = array();
                $cid = $mutated_card_id[0];
                $mutated_cards[$cid] = array('card_id' => $cid , 'board_x' => $this->dbcard[$cid]['board_x'], 'board_y' => $this->dbcard[$cid]['board_y'], 'card_type_arg' => $this->dbcard[$cid]['card_type_arg']);
            }
        }
        if (!empty($mutated_cards)) {
            // mute monster
            foreach ($mutated_cards as $card_id => $value) {
                $sql = "UPDATE card SET mutation = mutation + 1 , last_play = 2 WHERE card_id = $card_id";
                self::DbQuery( $sql );
            }
        }
        return $mutated_cards;
    }

    protected function get_mutation_level($card_id = null)
    {
        if (is_null($card_id)) {
            // return all ice monster with mutation level > 0
            $ids1 = array_values($this->multi_array_search($this->dbcard, array('mutation' => 1)));
            $ids2 = array_values($this->multi_array_search($this->dbcard, array('mutation' => 2)));
            $ids = array_merge($ids1, $ids2);
            $res=array();
            for ($i=0; $i < count($ids); $i++) {
                $cid = $this->dbcard[$ids[$i]]['card_id'];
                $res[$cid] = array('card_id' => $cid,
                    'mutation' => $this->dbcard[$ids[$i]]['mutation'],
                    'card_location_arg' => $this->dbcard[$ids[$i]]['card_location_arg'],
                    'board_x' => $this->dbcard[$ids[$i]]['board_x'],
                    'board_y' => $this->dbcard[$ids[$i]]['board_y']);
            }
            //$sql = "SELECT card_id, mutation, board_x, board_y, card_location_arg FROM card WHERE mutation > 0";
            //return self::getCollectionFromDb( $sql);
            return $res;
        } else {
            // return the mutation level of the card_id
            $id = array_values($this->multi_array_search($this->dbcard, array('card_id' => $card_id)));
            if (!empty($id)) {
                $res = $this->dbcard[$id[0]]['mutation'];
            } else {
                $res = 0;
            }
            //$sql = "SELECT mutation FROM card WHERE card_id = $card_id";
            //return self::getUniqueValueFromDB( $sql );
            return $res;
        }
    }

    protected function get_last_played_cards()
    {
        //$sql = "SELECT card_id, board_x, board_y, card_type, card_type_arg, card_location_arg, mutation  FROM card WHERE last_play = 1";
        //return self::getCollectionFromDb( $sql);
        $this->updateLocalDB('card');
        $ids = array_values($this->multi_array_search($this->dbcard, array('last_play' => 1)));
        $res=array();
        for ($i=0; $i < count($ids); $i++) {
            $cid = $this->dbcard[$ids[$i]]['card_id'];
            $res[$cid] = array('card_id' => $cid,
                'card_type' => $this->dbcard[$ids[$i]]['card_type'],
                'card_type_arg' => $this->dbcard[$ids[$i]]['card_type_arg'],
                'card_location_arg' => $this->dbcard[$ids[$i]]['card_location_arg'],
                'mutation' => $this->dbcard[$ids[$i]]['mutation'],
                'board_x' => $this->dbcard[$ids[$i]]['board_x'],
                'board_y' => $this->dbcard[$ids[$i]]['board_y']);
        }
        return $res;
    }

    protected function get_last_muted_cards()
    {
        //$sql = "SELECT card_id, board_x, board_y, card_type, card_type_arg, card_location_arg, mutation  FROM card WHERE last_play = 2 ORDER BY card_location_arg";
        //return self::getCollectionFromDb( $sql);
        $ids = array_values($this->multi_array_search($this->dbcard, array('last_play' => 2)));
        $tres=array();
        for ($i=0; $i < count($ids); $i++) {
            $cid = $this->dbcard[$ids[$i]]['card_id'];
            $tres[$cid] = array('card_id' => $cid,
                'card_type' => $this->dbcard[$ids[$i]]['card_type'],
                'card_type_arg' => $this->dbcard[$ids[$i]]['card_type_arg'],
                'card_location_arg' => $this->dbcard[$ids[$i]]['card_location_arg'],
                'mutation' => $this->dbcard[$ids[$i]]['mutation'],
                'board_x' => $this->dbcard[$ids[$i]]['board_x'],
                'board_y' => $this->dbcard[$ids[$i]]['board_y']);
        }
        usort($tres, function ($a, $b) {
            return $a['card_location_arg'] <=> $b['card_location_arg'];
        });
        $res = array();
        for ($i=0; $i < count($tres); $i++) { 
            $res[$tres[$i]['card_id']] = $tres[$i];
        }
        return $res;
    }

    protected function reset_last_played()
    {
        $sql = "UPDATE card SET last_play = 0 WHERE 1";
        self::DbQuery( $sql );
    }

    protected function setMedalSelection($medals)
    {
        // insert indivudual permanent medals
        $perm_medals = array('desert' => 3, 'rune' => 9);
        foreach ($perm_medals as $type => $medal_code) {
            $sql = "INSERT INTO medals (type, medal_id, player_id) VALUES ('$type', $medal_code, 0)";
            self::DbQuery( $sql );
        }
        if ($this->isTeamPlay()) {
            // insert permeamnt medals for team
            $perm_team_medals = array('desert' => 31, 'lowest' => 71, 'rune' => 91);
            foreach ($perm_team_medals as $type => $medal_code) {
                for ($i=0; $i < 2; $i++) { 
                    $mc = $medal_code + $i;
                    $sql = "INSERT INTO medals (type, medal_id, player_id) VALUES ('$type', $mc, 0)";
                    self::DbQuery( $sql );
                }
            }

            foreach ($medals as $type => $medal_code) {
                $sql = "INSERT INTO medals (type, medal_id, player_id) VALUES ('$type', $medal_code, 0)";
                self::DbQuery( $sql );
                for ($i=1; $i < 3; $i++) { 
                    $medal_code_team = ($medal_code * 10) + $i;
                    $sql = "INSERT INTO medals (type, medal_id, player_id) VALUES ('$type', $medal_code_team, 0)";
                    self::DbQuery( $sql );
                }
            }
        } else {
            // individual play
            // insert permament penalty medals
            $sql = "INSERT INTO medals (type, medal_id, player_id) VALUES ('lowest', 7, 0)";
            self::DbQuery( $sql );
            // insert random medals
            foreach ($medals as $type => $medal_code) {
                $sql = "INSERT INTO medals (type, medal_id, player_id) VALUES ('$type', $medal_code, 0)";
                self::DbQuery( $sql );
            }
        }
    }

    protected function getMedalsInfo()
    {
        $sql = "SELECT medal_id, type , player_id FROM medals";
        return self::getCollectionFromDb( $sql);
    }

    protected function setMedalAttribution($list_players, $medal_id)
    {
        $sql = "UPDATE medals SET player_id = '$list_players' WHERE medal_id = $medal_id";
        self::DbQuery( $sql );
    }

    protected function checkMedalSuccess($medal_id, $player_id=null, $printres=false, $teamid=-1, $get_details=false)
    {
        switch (intval($medal_id)) {
            case 3:
                // indiv : 3 desert tiles - team : 6 desert tiles
                if ($teamid == -1) {
                    $nbr_desert_tiles = count($this->custgetCardsOfTypeInLocation(7,null,'board',$player_id));
                    $explorer_infos = $this->getExplorer();
                    if ($explorer_infos[intval($player_id)]['explorer_id'] == 12) {
                        $nbr_desert_tiles++;
                    }
                    if ($nbr_desert_tiles >= 3) {
                        return true;
                    }
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_desert_tiles = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_desert_tiles >= 6) {
                        return true;
                    }
                } else if($get_details){
                    $nbr_desert_tiles = count($this->custgetCardsOfTypeInLocation(7,null,'board',$player_id));
                    $explorer_infos = $this->getExplorer();
                    if ($explorer_infos[intval($player_id)]['explorer_id'] == 12) {
                        $nbr_desert_tiles++;
                    }
                    return $nbr_desert_tiles;
                } else {
                    return false;
                }
                break;
            case 9:
                // indiv : 4 rune *monsters* (not tile) - team : 8 rune *monsters* (not tile)
                $rune_monster = $this->custgetCardsOfTypeInLocation(8,null,'board',$player_id);
                $rune_count = $this->getrunecount($rune_monster);
                if ($teamid == -1 and $rune_count >= 4) {
                    return true;
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $this->updateLocalDB('player');
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_rune_count = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_rune_count >= 8) {
                        return true;
                    }
                } else if($get_details){
                    return $rune_count;
                } else {
                    return false;
                }
                break;
            case 7:
                // the lowest number of desert and rune monsters
                // THIS IS THE ONLY CASE WHERE THE FUNCTION DOES NOT RETURNS A BOOL
                // RETURN A ARRAY OF PLAYER_ID FOR INDVIDUAL PLAY
                // RETURN ARRAY OF TEAM_ID FOR TEAM PLAY
                if ($this->isTeamPlay()) {
                    $total_count = 999;
                    $teams = $this->get_teams(); // associative array with player_id as key and team_id as value
                    $team_count = array();
                    foreach ($teams as $player_id => $team_id) {
                        $rune_monster = $this->custgetCardsOfTypeInLocation(8,null,'board',$player_id);
                        $rune_count = $this->getrunecount($rune_monster);
                        $desert_count = $this->checkMedalSuccess('3', $player_id, false, $team_id, true);
                        $total_count = $rune_count + $desert_count;
                        if (key_exists($team_id, $team_count)) {
                            $team_count[$team_id] = $team_count[$team_id] + $total_count;
                        } else {
                            $team_count[$team_id] = $total_count;
                        }
                    }
                    $team_medal_owner = array_values(array_keys($team_count,min($team_count)));
                    return $team_medal_owner;
                } else {
                    $total_count = 999;
                    $users_counts = array();
                    foreach (array_keys($this->loadPlayersBasicInfos()) as $pid) {
                        $rune_monster = $this->custgetCardsOfTypeInLocation(8,null,'board',$pid);
                        $rune_count = $this->getrunecount($rune_monster);
                        $nbr_desert_tiles = count($this->custgetCardsOfTypeInLocation(7,null,'board',$pid));
                        $user_count = intval($rune_count) + intval($nbr_desert_tiles);
                        $users_counts[$pid] = $user_count;
                    }
                    $user_medal_owner = array_values(array_keys($users_counts,min($users_counts)));
                    return $user_medal_owner;
                }
                break;
            case 1:
                // indiv : 2 complete big monsters - team : 4 complete big monsters
                $big_monsters = $this->getCardsOnBoard($player_id, 3);
                $complete_bm_count = 0;
                foreach ($big_monsters as $card_id => $value) {
                    if (intval($value['card_type_arg']) == 1) {
                        foreach ($big_monsters as $card_id2 => $value2) {
                            if (intval($value['board_x']) + 1 == intval($value2['board_x']) and intval($value['board_y']) == intval($value2['board_y']) and  intval($value2['card_type_arg']) == 2 ) {
                                $complete_bm_count++;
                            }
                        }
                    }
                }
                if ($teamid == -1 and $complete_bm_count >= 2) {
                    return true;
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_complete_bm_count = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_complete_bm_count >= 4) {
                        return true;
                    }
                } else if($get_details){
                    return $complete_bm_count;
                } else {
                    return false;
                }
                break;
            case 10:
                // indiv : 4 swamp or grassland tiles -- team : 8 swamp or grassland tiles
                $nbr_swamp_tiles = count($this->custgetCardsOfTypeInLocation(5,null,'board',$player_id));
                $nbr_grassland_tiles = count($this->custgetCardsOfTypeInLocation(6,null,'board',$player_id));
                if ($teamid == -1 and (($nbr_swamp_tiles + $nbr_grassland_tiles) >= 4)) {
                    return true;
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_swamp_grass_tiles = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_swamp_grass_tiles >= 8) {
                        return true;
                    }
                } else if($get_details){
                    return $nbr_swamp_tiles + $nbr_grassland_tiles;
                } else {
                    return false;
                }
                break;
            case 8:
                // indiv : 5 different tiles on the board -- team : 7 different tiles on the board
                if ($teamid == -1) {
                    $nbr_tile_types = 0;
                    for ($i=2; $i < 9; $i++) { 
                        (count($this->custgetCardsOfTypeInLocation($i,null,'board',$player_id)) > 0)?$nbr_tile_types++:null;
                    }
                    if (count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id)) < 1 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id)) > 0) {
                        // ice and mutagenic are same type of monster, this is to avoid to count twice the types if a player have ice and mutagenic monster
                        $nbr_tile_types++;
                    }
                    $explorer_infos = $this->getExplorer();
                    if ($explorer_infos[intval($player_id)]['explorer_id'] == 5 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id)) < 1 and count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id)) < 1) {
                        // if player have the purple female explorer and no ice on board yet, add +1
                        $nbr_tile_types++;
                    }
                    if ($explorer_infos[intval($player_id)]['explorer_id'] == 12 and count($this->custgetCardsOfTypeInLocation(7,null,'board',$player_id)) < 1) {
                        // if player have the orange male explorer and no desert monster on board yet, add +1
                        $nbr_tile_types++;
                    }
                    if ($nbr_tile_types >= 5) {
                        if ($printres) var_dump($nbr_tile_types);
                        return true;
                    }
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $pid1 = $team_player_ids[0];
                    $pid2 = $team_player_ids[1];

                    $nbr_tile_types = 0;
                    for ($i=2; $i < 9; $i++) { 
                        if ((count($this->custgetCardsOfTypeInLocation($i,null,'board',$pid1)) > 0) or (count($this->custgetCardsOfTypeInLocation($i,null,'board',$pid2)) > 0)) {
                            $nbr_tile_types++;
                        }
                    }
                    if ((count($this->custgetCardsOfTypeInLocation(2,null,'board',$pid1)) < 1 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$pid1)) > 0) or (count($this->custgetCardsOfTypeInLocation(2,null,'board',$pid2)) < 1 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$pid2)) > 0) ) {
                        // ice and mutagenic are same type of monster, this is to avoid to count twice the types if a player have ice and mutagenic monster
                        $nbr_tile_types++;
                    }
                    $explorer_infos = $this->getExplorer();
                    if (($explorer_infos[intval($pid1)]['explorer_id'] == 5 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$pid1)) < 1 and count($this->custgetCardsOfTypeInLocation(2,null,'board',$pid1)) < 1) or ($explorer_infos[intval($pid2)]['explorer_id'] == 5 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$pid2)) < 1 and count($this->custgetCardsOfTypeInLocation(2,null,'board',$pid2)) < 1)) {
                        // if player have the purple female explorer and no ice on board yet, add +1
                        $nbr_tile_types++;
                    }
                    if (($explorer_infos[intval($pid1)]['explorer_id'] == 12 and count($this->custgetCardsOfTypeInLocation(7,null,'board',$pid1)) < 1) or ($explorer_infos[intval($pid2)]['explorer_id'] == 12 and count($this->custgetCardsOfTypeInLocation(7,null,'board',$pid2)) < 1)) {
                        // if player have the orange male explorer and no desert monster on board yet, add +1
                        $nbr_tile_types++;
                    }
                    if ($nbr_tile_types >= 7) {
                        if ($printres) var_dump($nbr_tile_types);
                        return true;
                    }
                } else if($get_details){
                    $nbr_tile_types = 0;
                    for ($i=2; $i < 9; $i++) { 
                        (count($this->custgetCardsOfTypeInLocation($i,null,'board',$player_id)) > 0)?$nbr_tile_types++:null;
                    }
                    if (count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id)) < 1 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id)) > 0) {
                        // ice and mutagenic are same type of monster, this is to avoid to count twice the types if a player have ice and mutagenic monster
                        $nbr_tile_types++;
                    }
                    $explorer_infos = $this->getExplorer();
                    if ($explorer_infos[intval($player_id)]['explorer_id'] == 5 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id)) < 1 and count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id)) < 1) {
                        // if player have the purple female explorer and no ice on board yet, add +1
                        $nbr_tile_types++;
                    }
                    if ($explorer_infos[intval($player_id)]['explorer_id'] == 12 and count($this->custgetCardsOfTypeInLocation(7,null,'board',$player_id)) < 1) {
                        // if player have the orange male explorer and no desert monster on board yet, add +1
                        $nbr_tile_types++;
                    }
                    return $nbr_tile_types;
                } else {
                    return false;
                }
                break;
            case 5:
                // indiv 5 lava tiles -- team: 10 lava tiles
                $nbr_lava_tiles = count($this->custgetCardsOfTypeInLocation(4,null,'board',$player_id));
                if ($teamid == -1 and $nbr_lava_tiles >= 5) {
                    return true;
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_lava_tiles = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_lava_tiles >= 10) {
                        return true;
                    }
                } else if($get_details){
                    return $nbr_lava_tiles;
                } else {
                    return false;
                }
                break;
            case 2:
                // indiv : 3 different crystal completed -- team : 3 trios of different crystal completed
                if ($teamid == -1) {
                    $diams_info =$this->getDiamondsCount($player_id);
                    if (($diams_info['red'] > 0 and $diams_info['green'] > 0 and $diams_info['blue'] > 0)) {
                        return true;
                    }
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_diams_info1 = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true);
                    $tot_diams_info2 = $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    $diams_tot = array('red' => $tot_diams_info1['red'] + $tot_diams_info2['red'], 'green' => $tot_diams_info1['green'] + $tot_diams_info2['green'], 'blue' => $tot_diams_info1['blue'] + $tot_diams_info2['blue']);
                    if ($diams_tot['red'] >= 3 and $diams_tot['green'] >= 3 and $diams_tot['blue'] >= 3) {
                        return true;
                    }
                } else if($get_details){
                    return $this->getDiamondsCount($player_id);
                } else {
                    return false;
                }
                break;
            case 4:
                // indiv : 6 ice tiles -- team : 8 ice tiles
                if ($teamid == -1) {
                    $explorer_infos = $this->getExplorer();
                    $nbr_ice_tiles = count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id));
                    $nbr_mutagenic_tiles = count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id));
                    ($explorer_infos[intval($player_id)]['explorer_id'] == 5)?$nbr_mutagenic_tiles++:null;
                    if (($nbr_ice_tiles + $nbr_mutagenic_tiles) >= 6) {
                        # code...
                        return true;
                    }
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_ice_tiles = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_ice_tiles >= 8) {
                        return true;
                    }
                } else if($get_details){
                    $explorer_infos = $this->getExplorer();
                    $nbr_ice_tiles = count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id));
                    $nbr_mutagenic_tiles = count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id));
                    ($explorer_infos[intval($player_id)]['explorer_id'] == 5)?$nbr_mutagenic_tiles++:null;
                    return $nbr_ice_tiles + $nbr_mutagenic_tiles;
                } else {
                    return false;
                }
                break;
            case 6:
                // indiv : 4 mutagenic monsters -- team : 12 mutagenic monsters
                if ($teamid == -1) {
                    $explorer_infos = $this->getExplorer();
                    $mutagenic_tiles = $this->custgetCardsOfTypeInLocation(2,null,'board',$player_id);
                    $nbr_mutagenic_tiles=0;
                    foreach ($mutagenic_tiles as $card_id => $card_details) {
                        ($card_details['type_arg'] == 2)?$nbr_mutagenic_tiles++:null;
                        ($card_details['type_arg'] == 1)?$nbr_mutagenic_tiles+=2:null;
                    }
                    ($explorer_infos[intval($player_id)]['explorer_id'] == 5)?$nbr_mutagenic_tiles++:null;
                    if ($nbr_mutagenic_tiles >= 4) {
                        return true;
                    }
                } else if ($this->isTeamPlay() and !$get_details and $teamid > -1) {
                    $team_player_ids = $this->getTeamPlayers($teamid);
                    $tot_mutagenic_tiles = $this->checkMedalSuccess($medal_id, $team_player_ids[0], false, $teamid, true) + $this->checkMedalSuccess($medal_id, $team_player_ids[1], false, $teamid, true);
                    if ($tot_mutagenic_tiles >= 12) {
                        return true;
                    }
                } else if($get_details){
                    $explorer_infos = $this->getExplorer();
                    $mutagenic_tiles = $this->custgetCardsOfTypeInLocation(2,null,'board',$player_id);
                    $nbr_mutagenic_tiles=0;
                    foreach ($mutagenic_tiles as $card_id => $card_details) {
                        ($card_details['type_arg'] == 2)?$nbr_mutagenic_tiles++:null;
                        ($card_details['type_arg'] == 1)?$nbr_mutagenic_tiles+=2:null;
                    }
                    ($explorer_infos[intval($player_id)]['explorer_id'] == 5)?$nbr_mutagenic_tiles++:null;
                    return $nbr_mutagenic_tiles;
                } else {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }
        return false;

    }

    protected function getrunecount($rune_monster)
    {
        $rune_count = 0;
        foreach ($rune_monster as $key => $value) {
            if ($value['type_arg'] == 1) {
                $rune_count += 2;
            } else {
                $rune_count += 1;
            }
        }
        return $rune_count;
    }

    protected function getDiamondsCount($player_id, $printres=false)
    {
        // for testing: getDiamondsCount(2356483)
        $cards_info = $this->getCardsOnBoard($player_id);
        $green_count = 0;
        $red_count = 0;
        $blue_count = 0;
        $explo_diamonds = $this->explorer_infos[$this->getExplorer()[$player_id]['explorer_id']]['diamonds'];
        $explo_has_topleft = false;
        $explo_has_left = false;
        $explo_has_top = false;
        $monster_with_diams = array(4,8);
        //var_dump($cards_info);
        foreach ($cards_info as $card_id => $card_details) {
            if (in_array(intval($card_details['card_type']),$monster_with_diams)) {
                $diamonds = $this->monster_infos[$card_details['card_type']][$card_details['card_type_arg']]['diamonds'];
                // the card has diamonds on it
                foreach ($diamonds as $key => $diamond) {
                    switch ($diamond) {
                        case 'GL':
                            // Green on Left
                            foreach ($cards_info as $card_id2 => $card_details2) {
                                if (in_array(intval($card_details2['card_type']), $monster_with_diams)) {
                                    $diamonds2 = $this->monster_infos[$card_details2['card_type']][$card_details2['card_type_arg']]['diamonds'];
                                    if (intval($card_details['board_x']) - 1 == intval($card_details2['board_x']) and intval($card_details['board_y']) == intval($card_details2['board_y']) and in_array('GR', $diamonds2)) {
                                        // card is placed on the left side of our "main" card and has a diamond on its right side
                                        $green_count++;
                                    } 
                                }
                            }
                            if (in_array('GR', $explo_diamonds) and intval($card_details['board_x']) == 2 and intval($card_details['board_y']) == 0) {
                                $green_count++;
                            }
                            break;
                        case 'GR':
                            // green on right -> only check against explorer since if a GL is complete, all other GR should also be... except for explorers :)
                            if (in_array('GL', $explo_diamonds) and intval($card_details['board_x']) == -1 and intval($card_details['board_y']) == 0) {
                                $green_count++;
                            }
                            break;
                        case 'RT':
                            // Red on Top
                            foreach ($cards_info as $card_id2 => $card_details2) {
                                if (in_array(intval($card_details2['card_type']), $monster_with_diams)) {
                                    $diamonds2 = $this->monster_infos[$card_details2['card_type']][$card_details2['card_type_arg']]['diamonds'];
                                    if (intval($card_details['board_x']) == intval($card_details2['board_x']) and intval($card_details['board_y']) - 2 == intval($card_details2['board_y']) and in_array('RB', $diamonds2)) {
                                        // card is placed on the top of our "main" card and has a diamond on its bottom
                                        $red_count++;
                                    } 
                                }
                            }
                            // diamond completed by explorer tile
                            if (in_array('RLB', $explo_diamonds) and intval($card_details['board_x']) == 0 and intval($card_details['board_y']) == 2) {
                                // red on Left Bottom on explorer
                                $red_count++;
                            } elseif (in_array('RRB', $explo_diamonds) and intval($card_details['board_x']) == 1 and intval($card_details['board_y']) == 2) {
                                // red on Right Bottom on explorer
                                $red_count++;
                            }
                            break;
                        case 'RB':
                            // Red on Bottom => check 'GR' case for more info. same logic here
                            if (in_array('RRT', $explo_diamonds) and intval($card_details['board_x']) == 1 and intval($card_details['board_y']) == -2) {
                                // red on Right Top on explorer
                                $red_count++;
                            } elseif (in_array('RLT', $explo_diamonds) and intval($card_details['board_x']) == 0 and intval($card_details['board_y']) == -2) {
                                // red on Left Top on explorer
                                $red_count++;
                            }
                            break;
                        case 'BTL':
                            $has_top = false; // tile on same x, y-1 and BBL diamond
                            $has_left = false; // tile on same y, x-1 and BTR diamond
                            $has_topleft = false; // tile on x-1, y-1 and BBR diamond
                            foreach ($cards_info as $card_id2 => $card_details2) {
                                if (in_array(intval($card_details2['card_type']), $monster_with_diams)) {
                                    $diamonds2 = $this->monster_infos[$card_details2['card_type']][$card_details2['card_type_arg']]['diamonds'];
                                    if ((intval($card_details['board_x']) === intval($card_details2['board_x']) and intval($card_details['board_y']) - 2 === intval($card_details2['board_y']) and in_array('BBL', $diamonds2)) or (in_array('BBL', $explo_diamonds) and intval($card_details['board_x']) === 0 and intval($card_details['board_y']) === 2)) {
                                        // card is placed on the top side of our "main" card and has a blue diamond on its bottom left corner
                                        $has_top = true;
                                    }
                                    if (intval($card_details['board_x']) - 1 === intval($card_details2['board_x']) and intval($card_details['board_y']) === intval($card_details2['board_y']) and in_array('BTR', $diamonds2) or (in_array('BTR', $explo_diamonds) and intval($card_details['board_x']) === -1 and intval($card_details['board_y']) === 2)) {
                                        // card is placed on the left side of our "main" card and has a diamond on its right side
                                        $has_left = true;
                                    }
                                    if (intval($card_details['board_x']) - 1 === intval($card_details2['board_x']) and intval($card_details['board_y']) - 2 === intval($card_details2['board_y']) and in_array('BBR', $diamonds2) or (in_array('BBR', $explo_diamonds) and intval($card_details['board_x']) === 2 and intval($card_details['board_y']) === 2)) {
                                        // card is placed on the left side of our "main" card and has a diamond on its right side
                                        $has_topleft = true;
                                    }
                                }
                            }
                            if ($has_top and $has_left and $has_topleft) {
                                $blue_count++;
                            }
                            //var_dump([$card_details['board_x'], $card_details['board_y'], $has_top,$has_left,$has_topleft]);
                            break;
                        default:
                            //other diamonds, we do nothing here
                            break;
                    }
                }
                // check if blue diamond can be completed with explorer
                if (intval($card_details['board_x']) === -1 and intval($card_details['board_y']) === -1 and in_array('BBR', $diamonds)) {
                    $explo_has_topleft = true;
                } elseif (intval($card_details['board_x']) === -1 and intval($card_details['board_y']) === 0 and in_array('BTR', $diamonds)) {
                    $explo_has_left = true;
                } elseif (intval($card_details['board_x']) === 0 and intval($card_details['board_y']) === -1 and in_array('BBL', $diamonds)) {
                    $explo_has_top = true;
                }
            }
        }
        // we should now test if the explorer has a Blue Top Left diamond and see if it is completed
        if (in_array('BTL', $explo_diamonds) and $explo_has_topleft and $explo_has_left and $explo_has_top) {
            $blue_count++;
        }
        $result = array(
                        'green' => $green_count,
                        'red' => $red_count,
                        'blue' => $blue_count);
        if ($printres) {
            var_dump($result);
        }
        return $result;
    }

    protected function computeScore($player_id, $detailled_score=false, $printres=false)
    {
        $prev_score = $this->dbGetScore($player_id);
        $player_score = 0;
        $ice_pts = 0;
        $bigmonster_pts = 0;
        $lava_pts = 0;
        $grassland_pts = 0;
        $swamp_pts = 0;
        $diams_pts = 0;
        $explo_pts = 0;
        $medals_pts = 0;
        // diamonds score
        $diams = $this->getDiamondsCount($player_id);
        foreach ($diams as $color => $count) {
            switch ($color) {
                case 'green':
                    $diams_pts += 2 * $count;
                    break;
                case 'red':
                    $diams_pts += 5 * $count;
                    break;
                case 'blue':
                    $diams_pts += 10 * $count;
                    break;
                default:
                    # code...
                    break;
            }
        }
        // medals infos
        $medals = $this->getMedalsInfo();
        foreach ($medals as $medal_id => $medal_info) {
            $medal_pids = explode(",", $medal_info['player_id']);
            foreach ($medal_pids as $medal_pid) {
                if ($medal_pid == $player_id) {
                    if ($medal_id > 10) {
                        $mid = floor($medal_id / 10);
                    } else {
                        $mid = $medal_id;
                    }
                    $medals_pts += $this->medals_infos[$mid]['pts'];
                }
            }
        }
        // card-specifics points
        $cards_info = $this->getCardsOnBoard($player_id);
        $bigmonster_counted = array();
        $bigmonster_alone = array();
        foreach ($cards_info as $card_id => $card_details) {
            switch (intval($card_details['card_type'])) {
                case 1:
                    // ice monster
                    $ice_pts += $this->monster_infos[1][intval($card_details['card_type_arg'])]['pts'][$card_details['mutation']];
                    break;
                case 3:
                    // bigmonster
                    $bigmonster_complete = false;
                    if ($card_details['card_type_arg'] == 1) {
                        foreach ($cards_info as $card_id2 => $card_details2) {
                            if ($card_details2['card_type_arg'] == 2 and $card_details['board_x'] == $card_details2['board_x'] - 1 and $card_details['board_y'] == $card_details2['board_y']) {
                                $bigmonster_complete = true;
                                $bigmonster_counted[] = $card_details2['card_id'];
                                if (in_array($card_details2['card_id'], $bigmonster_alone)) {
                                    $bigmonster_pts--;
                                }
                            }
                        }
                        if (!$bigmonster_complete) {
                            $bigmonster_pts++;
                        } else {
                            $bigmonster_pts += 11;
                            $bigmonster_complete = false;
                        }
                    } elseif (!in_array($card_details['card_id'], $bigmonster_counted)) {
                        $bigmonster_pts++;
                        $bigmonster_alone[] = $card_details['card_id'];
                    }
                    break;
                case 4:
                    // lava monster
                    $lava_pts += $this->monster_infos[4][intval($card_details['card_type_arg'])]['pts'];
                    break;
                case 6:
                    // grassland monster
                    switch (intval($card_details['card_type_arg'])) {
                        case 1:
                            # 2 / green diamond
                            $grassland_pts += 2 * $diams['green'];
                            break;
                        case 2:
                            # 2 / medal of +5;+10;-10
                            $medal_count = 0;
                            foreach ($medals as $medal_id => $medal_info) {
                                if ($medal_info['player_id'] == $player_id and $medal_info['type'] != 'furious') {
                                    $medal_count++;
                                }
                            }
                            $grassland_pts += 2 * $medal_count;
                            break;
                        case 3:
                            # 1 / diam
                            $grassland_pts += $diams['green'] + $diams['red'] + $diams['blue'];
                            break;
                        case 4:
                            # 1 / type of terrain
                            $nbr_tile_types = 0;
                            for ($i=2; $i < 9; $i++) { 
                                (count($this->custgetCardsOfTypeInLocation($i,null,'board',$player_id)) > 0)?$nbr_tile_types++:null;
                            }
                            if (count($this->custgetCardsOfTypeInLocation(2,null,'board',$player_id)) < 1 and count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id)) > 1) {
                                // ice and mutagenic are same type of monster, this is to avoid to count twice the types if a player have ice and mutagenic monster
                                $nbr_tile_types++;
                            }
                            $grassland_pts += $nbr_tile_types;
                            break;
                        case 5:
                            # 2 / rune monster
                            $rune_monster = $this->custgetCardsOfTypeInLocation(8,null,'board',$player_id);
                            $rune_count = $this->getrunecount($rune_monster);
                            $grassland_pts += 2 * $rune_count;
                            break;
                        case 6:
                            # 1 / lava monster
                            $grassland_pts += count($this->custgetCardsOfTypeInLocation(4,null,'board',$player_id));
                            break;
                        case 7:
                            # 4 / complete big monster
                            $big_monsters = $this->getCardsOnBoard($player_id, 3);
                            $complete_bm_count = 0;
                            foreach ($big_monsters as $c_id => $val) {
                                if (intval($val['card_type_arg']) == 1) {
                                    foreach ($big_monsters as $c_id2 => $val2) {
                                        if (intval($val['board_x']) + 1 == intval($val2['board_x']) and intval($val['board_y']) == intval($val2['board_y']) and  intval($val2['card_type_arg']) == 2 ) {
                                            $complete_bm_count++;
                                        }
                                    }
                                }
                            }
                            $grassland_pts += 4 * $complete_bm_count;
                            break;
                        case 8:
                            # 3 / desert tile
                            $grassland_pts += 3 * count($this->custgetCardsOfTypeInLocation(7,null,'board',$player_id));
                            break;
                        case 9:
                            # 2 / grassland tile
                            $grassland_pts += 2 * count($this->custgetCardsOfTypeInLocation(6,null,'board',$player_id));
                            break;
                        case 10:
                            # 1 / filled position around the tile
                            $positions = array_fill(0,8,0); // creating array of possible position around tile (starting diagonally up-left, following positions are clock-wise)
                            $x = $card_details['board_x'];
                            $y = $card_details['board_y'];
                            foreach ($cards_info as $card_id2 => $card_details2) {
                                // check if the tile fill a position around the tile
                                if ($card_details2['card_type'] == 2) {
                                    // horizontal tile
                                    if ($card_details2['board_x'] == $x - 2 and $card_details2['board_y'] == $y -1) {
                                        $positions[0] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 1 and $card_details2['board_y'] == $y -1) {
                                        $positions[0] = 1;
                                        $positions[1] = 1;
                                    } elseif ($card_details2['board_x'] == $x and $card_details2['board_y'] == $y -1) {
                                        $positions[1] = 1;
                                        $positions[2] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y -1) {
                                        $positions[2] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 2 and $card_details2['board_y'] == $y) {
                                        $positions[7] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y) {
                                        $positions[3] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 2 and $card_details2['board_y'] == $y - 1) {
                                        $positions[7] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y - 1) {
                                        $positions[3] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 2 and $card_details2['board_y'] == $y - 2) {
                                        $positions[6] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 1 and $card_details2['board_y'] == $y - 2) {
                                        $positions[5] = 1;
                                        $positions[6] = 1;
                                    } elseif ($card_details2['board_x'] == $x and $card_details2['board_y'] == $y - 2) {
                                        $positions[4] = 1;
                                        $positions[5] = 1;
                                    } elseif ($card_details2['board_x'] == $x +1 and $card_details2['board_y'] == $y - 2) {
                                        $positions[4] = 1;
                                    }
                                } else {
                                    // vertical tile
                                    if ($card_details2['board_x'] == $x -1 and $card_details2['board_y'] == $y -2 ) {
                                        $positions[0] = 1;
                                    } elseif ($card_details2['board_x'] == $x and $card_details2['board_y'] == $y -2) {
                                        $positions[1] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y -2) {
                                        $positions[2] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 1 and $card_details2['board_y'] == $y -1) {
                                        $positions[0] = 1;
                                        $positions[7] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y -1) {
                                        $positions[2] = 1;
                                        $positions[3] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 1 and $card_details2['board_y'] == $y) {
                                        $positions[7] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y) {
                                        $positions[3] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 1 and $card_details2['board_y'] == $y - 1) {
                                        $positions[6] = 1;
                                        $positions[7] = 1;
                                    } elseif ($card_details2['board_x'] == $x + 1 and $card_details2['board_y'] == $y - 1) {
                                        $positions[3] = 1;
                                        $positions[4] = 1;
                                    } elseif ($card_details2['board_x'] == $x - 1 and $card_details2['board_y'] == $y - 2) {
                                        $positions[6] = 1;
                                    } elseif ($card_details2['board_x'] == $x and $card_details2['board_y'] == $y - 2) {
                                        $positions[5] = 1;
                                    } elseif ($card_details2['board_x'] == $x +1 and $card_details2['board_y'] == $y - 2) {
                                        $positions[4] = 1;
                                    }
                                }
                            }
                            $grassland_pts += array_sum($positions);
                            break;
                        case 11:
                            # 9 if male licorn is on board
                            $male_licorn = false;
                            foreach ($cards_info as $card_id2 => $card_details2) {
                                if ($card_details2['card_type'] == 6 and $card_details['card_type_arg'] == 12) {
                                    $male_licorn = true;
                                }
                            }
                            if ($male_licorn) {
                                $grassland_pts += 9;
                            }
                            break;
                        case 12:
                            # 9 if female licorn is on board
                            $female_licorn = false;
                            foreach ($cards_info as $card_id2 => $card_details2) {
                                if ($card_details2['card_type'] == 6 and $card_details['card_type_arg'] == 11) {
                                    $female_licorn = true;
                                }
                            }
                            if ($female_licorn) {
                                $grassland_pts += 9;
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }
        //var_dump($bigmonster_alone);
        //var_dump($bigmonster_counted);

        // swamp monsters
        $nbr_swamp_tiles = count($this->custgetCardsOfTypeInLocation(5,null,'board',$player_id));
        $swamp_pts += $this->monster_infos[5]['pts'][$nbr_swamp_tiles];
        // explorer scores
        $explorer_id = $this->getExplorer()[$player_id]['explorer_id'];
        switch (intval($explorer_id)) {
            case 1:
                # 1 / ice monster tile
                $explo_pts += count($this->custgetCardsOfTypeInLocation(1,null,'board',$player_id));
                break;
            case 4:
                # 1 / lava monster tile
                $explo_pts += count($this->custgetCardsOfTypeInLocation(4,null,'board',$player_id));
                break;
            case 6:
                # 1 / mutagenic monster
                $explo_pts += count($this->custgetCardsOfTypeInLocation(2,2,'board',$player_id)) + 2 * count($this->custgetCardsOfTypeInLocation(2,1,'board',$player_id));
                break;
            case 7:
                # 1 / grassland
                $explo_pts += count($this->custgetCardsOfTypeInLocation(6,null,'board',$player_id));
                break;
            case 8:
                # 2 / medal of 5 , 10 , -10 pts
                $medal_count = 0;
                foreach ($medals as $medal_id => $medal_info) {
                    if ($medal_info['player_id'] == $player_id and $medal_info['type'] != 'furious') {
                        $medal_count++;
                    }
                }
                $explo_pts += 2 * $medal_count;
                break;
            case 9:
                # 2 / rune monster
                $rune_monster = $this->custgetCardsOfTypeInLocation(8,null,'board',$player_id);
                $rune_count = $this->getrunecount($rune_monster);
                $explo_pts += 2 * $rune_count;
                break;
            case 10:
                # 4 / complete bigmonster
                $big_monsters = $this->getCardsOnBoard($player_id, 3);
                $complete_bm_count = 0;
                foreach ($big_monsters as $c_id => $val) {
                    if (intval($val['card_type_arg']) == 1) {
                        foreach ($big_monsters as $c_id2 => $val2) {
                            if (intval($val['board_x']) + 1 == intval($val2['board_x']) and intval($val['board_y']) == intval($val2['board_y']) and  intval($val2['card_type_arg']) == 2 ) {
                                $complete_bm_count++;
                            }
                        }
                    }
                }
                $explo_pts += 4 * $complete_bm_count;
                break;
            case 11:
                # 1 / complete diamond
                $explo_pts += $diams['green'] + $diams['red'] + $diams['blue'];
                break;
            // STRETCH GOAL VARIANT
            case 13:
                # 4 / twice-muted monsters
                $sql = "SELECT card_id FROM card WHERE card_location_arg = $player_id AND mutation = 2";
                $explo_pts += count(self::getCollectionFromDb( $sql));
                break;
            case 14:
                # 3 / blue ice monster
                $explo_pts += count($this->custgetCardsOfTypeInLocation(1,1,'board',$player_id));
                break;
            case 15:
                # 3 / furious dragon (furious dragon on lava tile and on medal)
                $explo_pts += count($this->custgetCardsOfTypeInLocation(4,4,'board',$player_id)) + count($this->custgetCardsOfTypeInLocation(4,5,'board',$player_id));
                break;
            case 16:
                # 2 / gold
                $explo_pts += 2 + count($this->custgetCardsOfTypeInLocation(4,6,'board',$player_id));
                break;
            case 17:
                # 5 / licorn
                $explo_pts += 5 * count($this->custgetCardsOfTypeInLocation(6,11,'board',$player_id)) + 5 * count($this->custgetCardsOfTypeInLocation(6,12,'board',$player_id));
                break;
            default:
                # code...
                break;
        }
        $player_score = $ice_pts + $bigmonster_pts + $lava_pts + $grassland_pts + $swamp_pts + $diams_pts + $explo_pts + $medals_pts;
        if ($detailled_score) {
            $score = array( 'score' => $player_score,
                            'delta' => $player_score - $prev_score,
                            'ice' => $ice_pts,
                            'bigmonster' => $bigmonster_pts,
                            'lava' => $lava_pts,
                            'grassland' => $grassland_pts,
                            'swamp' => $swamp_pts,
                            'diams' => $diams_pts,
                            'explo' => $explo_pts,
                            'medals' => $medals_pts);
        } else {
            $score = array( 'score' => $player_score,
                            'delta' => $player_score - $prev_score);
        }
        if ($printres) {
            var_dump($score);
        }
        return $score;
    }

    protected function computeTeamScore($breakdowns, $tie=false)
    {
        # Compute team score based on indivuduals scores
        $team_score = array();
        $teams = $this->get_teams();
        foreach ($teams as $player_id => $team_id) {
            if (array_key_exists($team_id, $team_score)) {
                if ($team_score[$team_id] > $breakdowns[$player_id]['score'] and !$tie) {
                    $team_score[$team_id] = $breakdowns[$player_id]['score'];
                } elseif ($team_score[$team_id] < $breakdowns[$player_id]['score'] and $tie) {
                    $team_score[$team_id] = $breakdowns[$player_id]['score'];
                }
            }
            else {
                $team_score[$team_id] = $breakdowns[$player_id]['score'];
            }
        }
        return $team_score;
    }

    protected function getStatList()
    {
        return array(
            "explorer",
            "pts_total",
            "pts_team",
            "pts_ice",
            "pts_bm",
            "pts_lava",
            "pts_grassland",
            "pts_swamp",
            "pts_diams",
            "pts_explo",
            "pts_medals",
            "nbr_blue",
            "nbr_red",
            "nbr_green");
    }


/******************************************************************

                             Player actions

***********************************************************************/

    function selectTeamPlayer( $player_id, $team_player_id)
    {
        self::checkAction( 'selectTeam' );
        $sql = "UPDATE player SET team_sel = $team_player_id WHERE player_id = $player_id";
        self::DbQuery($sql);
        if ($this->isLastPlayerFinished() and $this->isTeamPlay()) { 
            $sql = "SELECT player_id pid, team_sel ts FROM player";
            $team_choices_raw = self::getCollectionFromDB( $sql );
            $team_choices = array();
            foreach ($team_choices_raw as $key => $value)
            {
                $team_choices[$key] = $value['ts'];
            }
            // $team_choices is array with player_id as key and its choice as value
            if(count(array_unique($team_choices))<count($team_choices)){
                // there are/is duplicate(s) to fix (multiple player has chosen the same player to team with)
                $teams = [];
                $not_teamed = [];
                foreach ($team_choices as $pid => $tm) {
                    if ($pid == $team_choices[$tm] and !in_array($pid,$this->array_flatten($teams)) and !in_array($tm,$this->array_flatten($teams))) {
                        $teams[$pid] = [$pid,$tm];
                    } elseif ($pid != $team_choices[$tm]) {
                        $not_teamed[] = $pid;
                    }
                }
                for ($i=0; $i < count($not_teamed); $i+=2) { 
                    $teams[$not_teamed[$i]] = [$not_teamed[$i],$not_teamed[$i+1]];
                }
                // teams is now the final attribution : array of key 0..1 (or 0..2 at 6 players)
                $final_team_choices = array_values($teams);
            } else {
                // no duplicates
                $teams = array();
                foreach ($team_choices as $pid => $tm)
                {
                    if ($pid == $team_choices[$tm] and !in_array($pid,$this->array_flatten($teams)) and !in_array($tm,$this->array_flatten($teams))) {
                        $teams[$pid] = [$pid,$tm];
                    }
                }
                $final_team_choices = array_values($teams); // reset keys to range from 0 to 1 (or 3 if 6 players mode)
            }
            // record to DB team association
            foreach ($final_team_choices as $team => $players) {
                foreach ($players as $player) {
                    $sql = "UPDATE player SET team = $team WHERE player_id = $player";
                    self::DbQuery( $sql );
                }
            }
            self::setGameStateValue( 'teamdefined', 1 );
            $this->gamestate->setPlayerNonMultiactive($player_id, 'explorerSelection');
        }
        else {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'explorerSelection');
        }
    }

    function selectStartingExplorer($explorer_id)
    {
        self::checkAction( 'selectStartingExplorer' );
        $player_id = $this->getCurrentPlayerId(); // CURRENT ! as multiplayerstate
        $sql = "UPDATE explorers SET selected = 1 WHERE player_id = $player_id AND explorer_id = $explorer_id";
        self::DbQuery($sql);
        if (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())) {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'var_newTurn');
        } else {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'newRound');
        }
    }

    function selectShip($ship_player_id, $rem_cards_str, $sel_card)
    {
        self::checkAction( 'selectShip' );
        self::dump('ship_player_id', $ship_player_id);
        self::dump('rem_cards_str', $rem_cards_str);
        self::dump('sel_card', $sel_card);
        $player_id = $this->getCurrentPlayerId(); // CURRENT ! as multiplayerstate
        $rem_cards = explode(',', $rem_cards_str);
        // check provided cards are correctly in hand
        $cards = array_keys($this->custgetCardsInLocation('hand', $player_id));
        $cards_checked = 0;
        foreach ($rem_cards as $card_id) {
            if (!in_array($card_id, $cards)) {
                throw new BgaVisibleSystemException ('The card ${card_id} supposed to be in remaining cards is not in your hand ! Please reload the page (F5).');
            }
        }
        if (!in_array($sel_card, $cards)) {
            throw new BgaVisibleSystemException ('The selected card ${sel_card} is not in your hand ! Please reload the page (F5).');
        }
        // check that target ship is not already selected
        $sql = "SELECT COUNT(*) FROM card WHERE card_location='onShip' AND card_location_arg=$ship_player_id";
        $nb_cards_on_ship = self::getUniqueValueFromDB($sql);
        if ($nb_cards_on_ship > 0) {
            throw new BgaVisibleSystemException (self::_("There is already tiles on that player's ship. Please reload the page (F5)."));
        }
        $current_turn = self::getGameStateValue( 'currentTurn' );
        // update position of remaining cards
        self::NotifyPlayer( $player_id, "ReplayTileSelected", '', array(
                "card_id" => $sel_card));
        if ($ship_player_id == 0) {
            // last tile of current round -> remaining card to discard
            $this->moveCardsFromTo($rem_cards,'hand', 'discard', $player_id, $player_id);
            self::NotifyPlayer( $player_id, "cardsOnShip", '', array(
                "player_id" => $player_id,
                "player_ship_id" => $ship_player_id,
                "turn" => $current_turn));
        } else {
            // standard draft
            $this->moveCardsFromTo($rem_cards,'hand', 'onShip', $player_id, $ship_player_id);
            self::NotifyAllPlayers("cardsOnShip", clienttranslate('${player_name} put the rest of his cards to ${player_ship_name} ship'),array(
                "player_name" => self::getPlayerNameById($player_id),
                "player_ship_name" => self::getPlayerNameById($ship_player_id),
                "player_id" => $player_id,
                "player_ship_id" => $ship_player_id,
                "turn" => $current_turn)
            );
            self::DbQuery("UPDATE player SET cardsonshiporigin = $player_id WHERE player_id = $ship_player_id");
        }
        $current_state = $this->getStateName();
        if ($current_state == 'bmExploTileSelection') {
            $this->gamestate->nextState( 'bmExploTilePlacement' );
        } else {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'placeTile'); // deactivate player; if none left, transition to 'placeTile' state
        }
        # code...

    }

    function var_selectTile($rem_cards_str, $sel_card, $source_row, $sel_action)
    {
        self::checkAction( 'var_SelectTile' );
        $active_row = self::getGameStateValue( 'active_row' );
        if ($active_row != 0 and $source_row != $active_row) {
            throw new BgaVisibleSystemException (clienttranslate("Seleted row is not active !"));
        }
        $player_id = self::getActivePlayerId();
        $rem_cards = explode(',', $rem_cards_str);
        $cards = array_keys($this->custgetCardsInLocation('hand', $source_row));
        $cards_checked = 0;
        foreach ($rem_cards as $card_id) {
            if (!in_array($card_id, $cards)) {
                throw new BgaVisibleSystemException (clienttranslate('The card ${card_id} supposed to be in remaining cards is not in this row !'));
            }
        }
        if (count($rem_cards) + 1 != count($cards)) {
            throw new BgaVisibleSystemException (clienttranslate("Some cards are missing in hand or too much are in DB"));
        }
        if (!in_array($sel_card, $cards)) {
            throw new BgaVisibleSystemException (clienttranslate('The selected card ${sel_card} is not in the selected row !'));
        }
        if ($sel_action == 0) {
            // selected card is to be played
            $this->cards->moveCard($sel_card,'hand', $player_id);
            $log_msg = clienttranslate('${player_name} selected the ${monster_kind_name} monster in the ${row} row');
        } elseif ($sel_action == 1) {
            $this->cards->moveCard($sel_card,'discard', $player_id);
            $log_msg = clienttranslate('${player_name} discarded the ${monster_kind_name} monster in the ${row} row');
        } elseif ($sel_action == 3) {
            $this->cards->moveCard($sel_card,'hand', $player_id);
            $this->cards->moveCard($rem_cards[0],'discard', $player_id);
            $log_msg = clienttranslate('${player_name} selected the ${monster_kind_name} monster and discarded the ${monster_kind_name_dicard} monster in the ${row} row');
        } else {
            throw new BgaVisibleSystemException (clienttranslate("Wrong sel_action value !"));
        }
        $sel_row = ($source_row == 1) ? clienttranslate('upper') : clienttranslate('lower');
        $kind_monster = $this->custgetCard( $sel_card )['type'];
        $kind_monster_discard = $this->custgetCard( $rem_cards[0] )['type'];
        self::NotifyAllPlayers("SelectedTile", $log_msg ,array(
            "player_name" => self::getPlayerNameById($player_id),
            "monster_kind_name" => $this->tiles_info[$kind_monster]['name'],
            "monster_kind" => $kind_monster,
            "row" => $sel_row,
            "card_id" => $sel_card,
            "action" => $sel_action,
            "monster_kind_name_dicard" => $this->tiles_info[$kind_monster_discard]['name'],
            "discard_card_id" => $rem_cards[0],
            'i18n' => array( 'monster_kind_name' , 'row', 'monster_kind_name_dicard' ) )
        );
        if ($sel_action == 0) {
            self::setGameStateValue( 'active_row', $source_row );
        } else {
            $this->gamestate->nextState( 'var_placeTile' );
        }
    }

    function placeTile($whichMove_str)
    {
        self::checkAction( 'placeTile' );
        if (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())) {
            // variant mode
            $player_id = self::getActivePlayerId();
        } else {
            $player_id = $this->getCurrentPlayerId(); // CURRENT ! as multiplayerstate
        }
        // get the placement position
        $whichMove = explode(',', $whichMove_str);
        $card_data = $this->moveCardFromHandToBoard($whichMove, $player_id);
        // check if placed tile get mutated
        if (intval($card_data['card_type']) == 1) {
            // placed tile is an ice monster
            if ($this->check_mutated($whichMove, $card_data, $player_id)) {
                // tile is muted
                $this->updateLocalDB('card');
                $mutation_level = $this->get_mutation_level(intval($card_data['card_id']));
                $notif_data = array(array(
                    "player_id" => $player_id,
                    "x"=> $whichMove[0],
                    "y"=> $whichMove[1],
                    "card_id" => $card_data['card_id'],
                    "kind" => $card_data['card_type_arg'],
                    "mutation_level" => $mutation_level)
                    );
                self::NotifyPlayer( $player_id, "muted_monster", '', $notif_data);
            }
        }
        // check if placed tile creates mutation
        if (intval($card_data['card_type']) == 2) {
            // placed tile is a mutagenic monster
            $mutated_monster = $this->check_mutation($whichMove, $card_data, $player_id);
            if (!is_null($mutated_monster)) {
                $this->updateLocalDB('card');
                $notif_data = array();
                foreach ($mutated_monster as $card_id => $value) {
                    $mutation_level = $this->get_mutation_level(intval($card_id));
                    $notif_data[] = array(
                        "player_id" => $player_id,
                        "x"=> $mutated_monster[$card_id]['board_x'],
                        "y"=> $mutated_monster[$card_id]['board_y'],
                        "card_id" => $card_id,
                        "kind" => $mutated_monster[$card_id]['card_type_arg'],
                        "mutation_level"=> $mutation_level);
                }
                self::NotifyPlayer( $player_id, "muted_monster", '', $notif_data);
            }
        }
        $current_state = $this->getStateName();
        //var_dump(self::getPlayersNumber());
        if ($current_state == 'bmExploTilePlacement') {
            $this->gamestate->nextState( 'endTurn' );
        } elseif (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())){
            //var_dump('GO TO VAR END TURN !!!');
            $this->gamestate->nextState( 'var_endTurn' );
        } else {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'endTurn'); // deactivate player; if none left, transition to 'endTurn' state
        }
        
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

    function argexplorerSelection()
    {
        /* Provide the 2 explorer tiles ID available for selection to player
            ==> PRIVATE INFORMATION NEED TO BE SENT HERE ! (other players cannot see what are the 2 tiles other players could select... even if it does not have big impact on the game)
        */
        // fetch explore tiles attribution
        $sql = "SELECT player_id, GROUP_CONCAT( explorer_id ) AS 'explorer_id' FROM explorers WHERE selected = 0 GROUP BY player_id";
        $explorers_attr = self::getCollectionFromDB( $sql );
        $data = array('_private'=>array());
        if ($this->isTeamPlay()) {
            $team = $this->get_teams(true);
        }
        foreach ($explorers_attr as $player_id  => $explorer_id) {
            $explo_ids = explode(",", $explorer_id['explorer_id']);
            for ($i=0; $i < count($explo_ids) ; $i++) {
                $data['_private'][$player_id]['explorers'][$i] = array(
                    'explorer_id' => $explo_ids[$i],
                    'explorer_info' => $this->explorer_infos[$explo_ids[$i]]['descr'],
                    'i18n' => array( 'explorer_info' ));
            }
            if ($this->isTeamPlay()) {
                $data['_private'][$player_id]['team'] =$team;
            }
        }
        return $data;
    }

    function argtileSelection()
    {
        $countcards = array();
        foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
            $countcards[$player_id] = $this->custcountCardInLocation( 'hand', $player_id );
        }
        if (intval(self::getGameStateValue( 'endcountdowntimestamp' )) > -1) {
            $endcoutdowntime = intval(self::getGameStateValue( 'endcountdowntimestamp' ));
            $ctime = time();
            $coutdowntime = $endcoutdowntime - $ctime;
        } else if (self::getGameStateValue( 'currentRound' ) == 1 and self::getGameStateValue( 'currentTurn' ) == 0) {
            $ctime = time();
            $endcoutdowntime = $ctime + 10;
            self::setGameStateValue( 'endcountdowntimestamp', intval($endcoutdowntime) );
            $coutdowntime = 10;
        } else {
            $coutdowntime = 0;
        }
        return array('countcards' => $countcards, 'coutdowntime' => $coutdowntime);
    }

    function argvar_tileSelection()
    {
        return array(self::getGameStateValue( 'active_row' ));
    }

    function asum($base,$toadd) {
        return [$base[0] + $toadd[0], $base[1] + $toadd[1]];
    }

    function argplaceTile()
    {
        /* Provide available place on board */
        // fetch the cards on the board
        $player_id_list = array_keys($this->loadPlayersBasicInfos());
        $possible_moves = array('_private'=>array());
        foreach ($player_id_list as $player_id) {
            $played_tile = $this->getCardsOnBoard($player_id, null, 1);
            if (!empty($played_tile)) {
                // player has played card but F5 in the meantime before other players has played his cards
                $possible_moves['_private'][$player_id] = $played_tile ;
            } else {
                $cardsOnBoard = $this->getCardsOnBoard($player_id);
                $avail_place = array();
                $used_place = $this->getUsedBoard($cardsOnBoard);
                $allowed_vert = [[-1,-1],[0,-1],[1,-1],[1,0],[1,1],[1,2],[0,2],[-1,2],[-1,1],[-1,0]]; // allowed relative position for vertical tile
                $allowed_hor = [[-1,-1],[0,-1],[1,-1],[2,-1],[2,0],[2,1],[1,1],[0,1],[-1,1],[-1,0]]; // allowed relative position for horizontal tile
                // retreive the possible and allowed position from tile already placed on board
                foreach ($cardsOnBoard as $card_id => $card_info) {
                    $x = $card_info['board_x'];
                    $y = $card_info['board_y'];
                    $possible_pos = ($card_info['card_type'] == 2) ? array_map(array($this,'asum'),$allowed_hor,array_fill(0,count($allowed_hor),[$x,$y])) : array_map(array($this,'asum'),$allowed_vert,array_fill(0,count($allowed_vert),[$x,$y])) ;
                    foreach ($possible_pos as $key => $pos) {
                        if (in_array($pos, $used_place) or in_array($pos, $avail_place)) {
                        } else {
                            $avail_place[] = $pos;
                        }
                    }
                }
                // add possible position and allowed position around explorer tile
                $explo_tile_pos = [[0,0], [1,0]];
                foreach ($explo_tile_pos as $key => $pos) {
                    $x = $pos[0];
                    $y = $pos[1];
                    $possible_pos = array_map(array($this,'asum'),$allowed_vert,array_fill(0,count($allowed_vert),[$x,$y])) ;
                    foreach ($possible_pos as $ppkey => $pppos) {
                        if (in_array($pppos, $used_place) or in_array($pppos, $avail_place)) {
                        } else {
                            $avail_place[] = $pppos;
                        }
                    }
                }
                $placement_dirV = array();
                $placement_dirH = array();
                foreach ($avail_place as $key => $value) {
                    // define extension direction and check if position is actually valid (see commit 8045c6d49af95b78abec49dfe725fcec729363de for previous method)
                    if (
                        (in_array([$value[0] + 1, $value[1] - 1], $used_place) and  in_array([$value[0], $value[1] - 1], $avail_place) and in_array([$value[0] + 1, $value[1]], $avail_place))//BL
                        or (in_array([$value[0] - 1, $value[1] - 1], $used_place) and in_array([$value[0], $value[1] - 1], $avail_place) and in_array([$value[0] - 1, $value[1]], $avail_place)) // BR
                        or ((in_array([$value[0] - 1, $value[1]], $used_place) or in_array([$value[0] + 1, $value[1]], $used_place)) and in_array([$value[0], $value[1] + 1], $used_place) and in_array([$value[0], $value[1] - 1], $avail_place) ) // inscrements
                        or (in_array([$value[0], $value[1] - 1 ], $used_place) and in_array([$value[0], $value[1] + 1], $used_place))
                        ) {
                        $placement_dirV[] = 'X';
                    } else if (in_array([$value[0], $value[1] + 1], $used_place)) {
                        $placement_dirV[] = 'U';
                    } else {
                        $placement_dirV[] = 'D';
                    }
                    if (
                        (in_array([$value[0] - 1, $value[1]], $avail_place) and in_array([$value[0] , $value[1] + 1], $avail_place) and in_array([$value[0] - 1, $value[1]+1], $used_place)) //TR (free on left and free on bottom and busy on bottom-left)
                        or (in_array([$value[0] - 1, $value[1]], $avail_place) and in_array([$value[0] , $value[1] - 1], $avail_place) and in_array([$value[0] - 1, $value[1]-1], $used_place)) //BR (free on left and free on top and busy on top-left)
                        or (in_array([$value[0] - 1, $value[1]], $used_place) and in_array([$value[0] + 1, $value[1]], $used_place)) // between 2 tiles
                        or (in_array([$value[0] + 1, $value[1]], $used_place) and in_array([$value[0] - 1, $value[1]], $avail_place) and !(in_array([$value[0] - 2, $value[1]], $avail_place) and in_array([$value[0]-1 , $value[1] + 1], $avail_place))) // used on right, free on left (but the left not flagged as TR)
                        ) {
                        $placement_dirH[] = 'X';
                    } else if (in_array([$value[0] + 1, $value[1]], $used_place)) {
                        $placement_dirH[] = 'L';
                    } else {
                        $placement_dirH[] = 'R';
                    }
                }
                $possible_moves['_private'][$player_id] = array('possibleMoves' => $avail_place,
                                                                'placement_dirV' => $placement_dirV,
                                                                'placement_dirH' => $placement_dirH);
            }
        }
        return $possible_moves;
    }

    function argbmExploTileSelection()
    {
        # Send info of tile on the discard pile
        $cards = $this->custgetCardsInLocation( 'hand',  self::getActivePlayerId() );
        return $cards;
    }

    function argbmExploTilePlacement()
    {
        return $this->argplaceTile();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

   // this will make all players multiactive just before entering the state
    function st_MultiPlayerInit() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    function st_teamSelection()
    {
        if ($this->isTeamPlay()) {
            if (self::getPlayersNumber() == 5) {
                //throw new BgaUserException( self::_("Team play mode can be played at 4 or 6 players, not 5 !") );
                $this->gamestate->nextState( 'pregameEnd' );
            } else {
                self::NotifyAllPlayers("AskTeamSelection", '', array());
                $this->gamestate->setAllPlayersMultiactive();
            }
        } else {
            $this->gamestate->nextState( 'explorerSelection' );
        }
    }

    function st_newRound()
    {
        // check current round number
        $currentRound = self::getGameStateValue( 'currentRound' ) ;
        if( $currentRound == 0 ) {
            // first round 
            self::setGameStateValue( 'currentRound', 1 );
            // notify players of selected explorers
            $sql = "SELECT player_id, explorer_id FROM explorers WHERE selected = 1";
            $explorers = self::getCollectionFromDB( $sql );
            //self::dump( 'explorer_infos', $this->explorer_infos );
            foreach ($explorers as $player_id => $explorer_id) {
                self::NotifyAllPlayers("selectedExplorers",clienttranslate('${player_name} selected ${explorer_name}'), array(
                    "player_name" => self::getPlayerNameById($player_id),
                    "explorer_name" => $this->explorer_infos[$explorer_id['explorer_id']]['name'],
                    "explorer_id" => $explorer_id['explorer_id'],
                    "player_id" => $player_id,
                    'i18n' => array( 'explorer_name' ))
                );
            }
            
        } else {
            // second round
            $currentRound++;
            self::setGameStateValue( 'currentRound', $currentRound );
        }
           
        // Deal the 10 cards of the first round to each players
        $players = self::loadPlayersBasicInfos();
        $current_turn = self::getGameStateValue( 'currentTurn' );
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(10, 'deck', $player_id);
        
            self::NotifyPlayer( $player_id, "updateHand", '', array(
                "cards" => $cards,
                "event" => 'newRound',
                "roundId" => $currentRound,
                "turn" => $current_turn
            ) );
        }
        $this->gamestate->nextState( 'tileSelection' );
    }

    function st_var_newTurn()
    {
        $currentTurn = self::getGameStateValue( 'currentTurn' );
        if ($currentTurn == 0) {
            // First turn
            // notify players of selected explorers
            $sql = "SELECT player_id, explorer_id FROM explorers WHERE selected = 1";
            $explorers = self::getCollectionFromDB( $sql );
            self::dump( 'explorer_infos', $this->explorer_infos );
            foreach ($explorers as $player_id => $explorer_id) {
                self::NotifyAllPlayers("selectedExplorers", clienttranslate('${player_name} selected ${explorer_name}'), array(
                    "player_name" => self::getPlayerNameById($player_id),
                    "explorer_name" => $this->explorer_infos[$explorer_id['explorer_id']]['name'],
                    "explorer_id" => $explorer_id['explorer_id'],
                    "player_id" => $player_id,
                    'i18n' => array( 'explorer_name' ))
                );
            }
            $this->activeNextPlayer();
        }
        $active_row = self::getGameStateValue( 'active_row' );
        $n_cards = (self::getPlayersNumber() == 2) ? 4:6;

        if (($currentTurn == 1 and self::getPlayersNumber() == 2) or ($currentTurn == 2 and self::getPlayersNumber() == 3)) {
            // first turn where both row are filled
            $cards_avail = [];
            $cards_avail['upper'] = $this->cards->pickCards($n_cards, 'deck', 1);
            $cards_avail['lower'] = $this->cards->pickCards($n_cards, 'deck', 2);
            self::NotifyAllPlayers( "updateTileAvail", '', array(
                "cards" => $cards_avail,
                "updated_row" => 0
            ) );
        } else if ($currentTurn > 0) {
            $cards = $this->cards->pickCards($n_cards, 'deck', $active_row);
            self::NotifyAllPlayers( "updateTileAvail", '', array(
                "cards" => $cards,
                "updated_row" => $active_row
            ) );
        } else {
            // $currentTurn == 0
            $cards = $this->cards->pickCards($n_cards, 'deck', 1);
            self::NotifyAllPlayers("updateTileAvail", '', array(
                "cards" => $cards,
                "updated_row" => 1
            ) );
        }
        
        $currentTurn+=1;
        self::setGameStateValue( 'currentTurn', $currentTurn );
        self::setGameStateValue( 'active_row', 0 ); // reset active row
        self::setGameStateValue( 'first_player', self::getActivePlayerId());
        $this->gamestate->nextState( 'var_tileSelection' );
    }

    function getcurrentTurn()
    {
        echo(self::getGameStateValue( 'currentTurn' ));
    }

    function st_endTurn()
    {
        // 1. notifying all user of played card (and location on board)
        $last_played_cards = $this->get_last_played_cards();
        foreach ($last_played_cards as $card_id => $value) {
            $player_id = $last_played_cards[$card_id]['card_location_arg'];
            $type_monster = $last_played_cards[$card_id]['card_type'];
            self::NotifyAllPlayers("playedTiles", clienttranslate('${player_name} played an ${monster_type_name} monster'), array(
                "player_name" => self::getPlayerNameById($player_id),
                "monster_type_name" => $this->tiles_info[$type_monster]['name'],
                "player_id" => $player_id,
                "x" => $last_played_cards[$card_id]['board_x'],
                "y" => $last_played_cards[$card_id]['board_y'],
                "type_monster" => $type_monster,
                "kind_monster" => $last_played_cards[$card_id]['card_type_arg'],
                "card_id" => $card_id,
                "mutation_level" => $last_played_cards[$card_id]['mutation'],
                'i18n' => array( 'monster_type_name' ))
            );
        }
        // 1.2 Notify users if a mutation happenned (after placement of mutegenic)
        $last_muted_cards = $this->get_last_muted_cards(); // returned result is ordered by card_location_arg
        if (!empty($last_muted_cards)) {
            $notif_data = array();
            $last_player_id = 0;
            foreach ($last_muted_cards as $card_id => $value) {
                $player_id = intval($last_muted_cards[$card_id]['card_location_arg']);
                if ($last_player_id === $player_id or $last_player_id === 0) {
                    $last_player_id = $player_id;
                    $notif_data[] = array(
                        "player_name" => self::getPlayerNameById($player_id),
                        "player_id" => $player_id,
                        "x" => $last_muted_cards[$card_id]['board_x'],
                        "y" => $last_muted_cards[$card_id]['board_y'],
                        "kind" => $last_muted_cards[$card_id]['card_type_arg'],
                        "card_id" => $card_id,
                        "mutation_level" => $last_muted_cards[$card_id]['mutation']);
                } else {
                    // notify all changes occured by placement of mutagenic for one player
                    $notif_data["player_name"] = self::getPlayerNameById($last_player_id);
                    self::NotifyAllPlayers("muted_monster", clienttranslate('${player_name} : the placement of a mutagenic monster triggers a mutation of ice monster(s)'), $notif_data);
                    $last_player_id = $player_id;
                    $notif_data = array();
                    $notif_data[] = array(
                        "player_name" => self::getPlayerNameById($player_id),
                        "player_id" => $player_id,
                        "x" => $last_muted_cards[$card_id]['board_x'],
                        "y" => $last_muted_cards[$card_id]['board_y'],
                        "kind" => $last_muted_cards[$card_id]['card_type_arg'],
                        "card_id" => $card_id,
                        "mutation_level" => $last_muted_cards[$card_id]['mutation']);
                }
            }
            if (!empty($notif_data)) {
                $notif_data["player_name"] = self::getPlayerNameById($last_player_id);
                self::NotifyAllPlayers("muted_monster", clienttranslate('${player_name} : the placement of a mutagenic monster triggers a mutation of ice monster(s)'), $notif_data);
            }
        }
        $this->reset_last_played();
        // 2. Checking medals attribution
        $sql = "SELECT player_id id FROM player";
        $players = self::getCollectionFromDb( $sql );
        $medals_info = $this->getMedalsInfo();
        foreach ($medals_info as $medal_id => $medal_details) {
            if (intval($medal_details['player_id']) === 0 and $medal_details['type'] != 'lowest') {
                // medal not yet attributed to a player and not the medal attributed at the end of the game
                $sucess_player_id = '';
                if ($medal_id > 10) {
                    // team medal -- only process if indiv medal is attributed
                    if ($medals_info[floor($medal_id/10)]['player_id'] != 0) {
                        foreach (array_unique(array_values($this->get_teams())) as $team ) {
                            if ($this->checkMedalSuccess(floor($medal_id/10), 0, false, $team)) {
                                $team_player_ids = $this->getTeamPlayers($team);
                                if ($medal_id % 10 == 1) {
                                    // attribute to the first player of the team the medal ending by 1 (31,41,51,...)
                                    $sucess_player_id .= strval($team_player_ids[0]).',';
                                } else {
                                    // attribute to the first player of the team the medal ending by 2 (32,42,52,...)
                                    $sucess_player_id .= strval($team_player_ids[1]).',';
                                }
                            }
                        }
                    }
                } else {
                    foreach ($players as $player_id => $player) {
                        if ($this->checkMedalSuccess($medal_details['medal_id'], $player_id)) {
                            $sucess_player_id .= strval($player_id).',';
                        }
                    }
                }
                // check if one (or more) player has won the medal
                if (strlen($sucess_player_id > 0)) {
                    $list_players_str = substr($sucess_player_id, 0, -1);
                    $list_players = explode(',', $list_players_str);
                    $this->setMedalAttribution($list_players_str, $medal_details['medal_id']);
                    foreach ($list_players as $player_id) {
                        if ($medal_id > 10) {
                            $medal_sid = floor($medal_id / 10);
                            $medal_name = $this->medals_infos[$medal_sid]['name_team'];
                            $pts = $this->medals_infos[$medal_sid]['pts'];
                            $back_id = $this->matching_pts_back_id[self::getGameStateValue( 'playmode' )][$this->medals_infos[$medal_sid]['pts']] ;
                        } else {
                            $medal_name = $this->medals_infos[$medal_details['medal_id']]['name'];
                            $pts = $this->medals_infos[$medal_details['medal_id']]['pts'];
                            $back_id = $this->matching_pts_back_id[self::getGameStateValue( 'playmode' )][$this->medals_infos[$medal_id]['pts']] ;
                        }
                        self::NotifyAllPlayers("wonMedal", clienttranslate('${player_name} won the "${medal_name}" medal!'), array(
                            "player_name" => self::getPlayerNameById($player_id),
                            "medal_name" => $medal_name,
                            "player_id" => $player_id,
                            "medal_id" => $medal_id,
                            "pts" => strval($pts),
                            "back_id" => $back_id,
                            'i18n' => array( 'medal_name' ))
                        );
                    }
                }
            }
        }
        // 3. Score
        if (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())) {
            // compute score for player finishing turn
            $player_id = self::getActivePlayerId();
            $score = $this->computeScore($player_id);
            if ($score['delta'] !== 0) {
                // score has changed
                $this->dbSetScore($player_id, $score['score']); // update DB
                self::NotifyAllPlayers("scoreUpdate", '', array(
                    "player_id" => $player_id,
                    "score" => $score['score'],
                    "score_delta" => $score['delta'])
                ); // notify players
            }
        } else {
            // compute scores for all and notify delta
            foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
                $score = $this->computeScore($player_id);
                if ($score['delta'] !== 0) {
                    // score has changed
                    $this->dbSetScore($player_id, $score['score']); // update DB
                    self::NotifyAllPlayers("scoreUpdate", '', array(
                        "player_id" => $player_id,
                        "score" => $score['score'],
                        "score_delta" => $score['delta'])
                    ); // notify players
                }
            }
        }
        // 4. Give extra time to players
        if (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())) {
            self::giveExtraTime($player_id);
        } else {
            foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
                self::giveExtraTime($player_id);
            }
        }
        // 5. checking game situation
        if (self::getPlayersNumber() == 2 or (self::getPlayersNumber() == 3 and !$this->is3pdraft())) {
            $active_row = self::getGameStateValue( 'active_row' );
            $row_cards_remaining = $this->custcountCardInLocation( 'hand', $active_row );
            if ($row_cards_remaining >= 2) {
                // move to next player in same turn (set of tiles on same row)
                $this->activeNextPlayer();
                $this->gamestate->nextState( 'var_tileSelection' );
            } else {
                // the row is complete, move to next turn
                $tot_cards_remaining = $this->custcountCardInLocation( 'deck' ) + $this->custcountCardInLocation( 'hand');
                if ($tot_cards_remaining == 0) {
                    // end of the game
                    $this->gamestate->nextState( 'pregameEnd' );
                } else {
                    $this->gamestate->changeActivePlayer( $this->getPlayerAfter($this->getPlayerAfter(self::getActivePlayerId() )));
                    $this->gamestate->nextState( 'var_newTurn' );
                }
            }
        } else {
            // 5.0 update the turn count
            $current_turn = intval(self::getGameStateValue( 'currentTurn' ));
            $current_turn += 1;
            self::setGameStateValue( 'currentTurn', $current_turn );
            $cards_remaining = $this->custcountCardInLocation( 'onShip', array_keys($players)[0]);
            if ( $cards_remaining >= 2) {
                //  5.1 -> If No, update cards location and go to tileSelection
                $current_turn = self::getGameStateValue( 'currentTurn' );
                foreach ($players as $player_id => $player) {
                    $this->cards->moveAllCardsInLocationKeepOrder( 'onShip', 'hand' );
                    $this->updateLocalDB('card');
                    $cards = $this->custgetCardsInLocation( 'hand', $player_id );
                    self::NotifyPlayer( $player_id, "updateHand", '', array( 
                        "cards" => $cards,
                        "event" => 'newTurn',
                        "turn" => $current_turn
                    ) );
                }
                $this->gamestate->nextState( 'tileSelection' );
            }
            elseif (self::getGameStateValue( 'currentRound' ) < 2) {
                //  5.2 -> if Yes, and first round go to new round state
                $this->gamestate->nextState( 'newRound' );
    
            } else {
                // 5.3 -> If Yes and second round, process the end of the game
                // If the explorer 3 is in game, let him to place a tile from the discard pile (bmExploTileSelection state)
                $sql = "SELECT player_id FROM explorers WHERE selected = 1 AND explorer_id = 3";
                $player_id = $this->getUniqueValueFromDB($sql);
                if (!is_null($player_id) and self::getGameStateValue( 'explotileplacement' ) == 0) {
                    // activate the player
                    $this->gamestate->changeActivePlayer( $player_id );
                    // add time to that player
                    self::giveExtraTime($player_id);
                    // move all tiles from discard to the hand of the player
                    $this->cards->moveAllCardsInLocation('discard', 'hand', null, $player_id);
                    $this->updateLocalDB('card');
                    // update global var that logged that the 19th tile placement has been done
                    self::setGameStateValue( 'explotileplacement', 1 );
                    // move to specific state
                    $this->gamestate->nextState( 'bmExploTileSelection' );
                } else {
                    # Go to the end of the game
                    $this->gamestate->nextState( 'pregameEnd' );
                }
    
            }
        }
    }

    function st_bmExploTileSelection()
    {
        # Is this function really useful ?
    }

    function st_bmExploTilePlacement()
    {
        # Is this function really useful ?
    }

    function st_pregameEnd() {
        // end of the game
        if ($this->isTeamPlay() and self::getPlayersNumber() == 5) {
            // go directly to the end of the game if 5 players and team mode (not allowed configuration)
            $this->gamestate->nextState( 'gameEnd' );
        }
        // Check who get the "lowest" desert/rune medal
        if (!$this->isTeamPlay()) {
            $lowest_player = $this->checkMedalSuccess(7);
            if (count($lowest_player) == self::getPlayersNumber()) $lowest_team = array(); # noone gets the medals since all are at min
            if (count($lowest_player) > 1) {
                $player_id_list = implode(',',$lowest_player);
                $this->setMedalAttribution($player_id_list, 7);
                for ($i=0; $i < count($lowest_player); $i++) {
                    $player_id = $lowest_player[$i];
                    $notif_data = array(
                        "player_name" => self::getPlayerNameById($player_id),
                        "medal_name" => $this->medals_infos[7]['name'],
                        "player_id" => $player_id,
                        "medal_id" => 7,
                        "pts" => $this->medals_infos[7]['pts'],
                        "back_id" => $this->matching_pts_back_id[3][$this->medals_infos[7]['pts']],
                        'i18n' => array( 'medal_name' )
                    );
                    self::NotifyAllPlayers("wonMedal", clienttranslate('${player_name} receives the "${medal_name}" medal!'), $notif_data);
                }
            } else {
                $player_id = $lowest_player[0];
                $this->setMedalAttribution($player_id, 7);
                $notif_data = array(
                    "player_name" => self::getPlayerNameById($player_id),
                    "medal_name" => $this->medals_infos[7]['name'],
                    "player_id" => $player_id,
                    "medal_id" => 7,
                    "pts" => $this->medals_infos[7]['pts'],
                    "back_id" => $this->matching_pts_back_id[3][$this->medals_infos[7]['pts']],
                    'i18n' => array( 'medal_name' )
                );
                self::NotifyAllPlayers("wonMedal", clienttranslate('${player_name} receives the "${medal_name}" medal!'), $notif_data);
            }
        }
        else {
            $lowest_team = $this->checkMedalSuccess(7);
            if (count($lowest_team) == self::getPlayersNumber()/2) $lowest_team = array(); # noone gets the medals since all are at min
            if (count($lowest_team) > 1) {
                // multiple teams are tied on the medal
                $lowest_medal_attrib = array('71' => '', '72' => '');
                foreach ($lowest_team as $team_id) {
                    $team_player_ids = $this->getTeamPlayers($team_id);
                    for ($i=0; $i < 2; $i++) {
                        $lowest_medal_attrib[7*10+$i+1] .= strval($team_player_ids[$i]) . ',';
                        $player_id = $team_player_ids[$i];
                        $notif_data = array(
                            "player_name" => self::getPlayerNameById($player_id),
                            "medal_name" => $this->medals_infos[7]['name_team'],
                            "player_id" => $player_id,
                            "medal_id" => 7*10+$i+1,
                            "pts" => $this->medals_infos[7]['pts'],
                            "back_id" => $this->matching_pts_back_id[3][$this->medals_infos[7]['pts']],
                            'i18n' => array( 'medal_name' )
                        );
                        self::NotifyAllPlayers("wonMedal", clienttranslate('${player_name} receives the "${medal_name}" medal!'), $notif_data);
                    }
                }
                $this->setMedalAttribution(substr($lowest_medal_attrib[71], 0, -1), 71);
                $this->setMedalAttribution(substr($lowest_medal_attrib[72], 0, -1), 72);
            } else {
                $team_player_ids = $this->getTeamPlayers($lowest_team[0]);
                for ($i=0; $i < 2; $i++) { 
                    $player_id = $team_player_ids[$i];
                    $this->setMedalAttribution($player_id, 7*10+$i+1);
                    $notif_data = array(
                        "player_name" => self::getPlayerNameById($player_id),
                        "medal_name" => $this->medals_infos[7]['name_team'],
                        "player_id" => $player_id,
                        "medal_id" => 7*10+$i+1,
                        "pts" => $this->medals_infos[7]['pts'],
                        "back_id" => $this->matching_pts_back_id[3][$this->medals_infos[7]['pts']],
                        'i18n' => array( 'medal_name' )
                    );
                    self::NotifyAllPlayers("wonMedal", clienttranslate('${player_name} receives the "${medal_name}" medal!'), $notif_data);
                }
            }
        }
        
        // set stats and compute total scores
        $breakdowns = array();
        foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
            // Compute score with details
            $score = $this->computeScore($player_id, true);
            // append to breakdowns
            $breakdowns[$player_id] = $score;
            // get diamonds counts
            $diams = $this->getDiamondsCount($player_id);
            // organise stats results
            $player_stat_results = array(
                "explorer" => $this->getUniqueValueFromDB("SELECT explorer_id FROM explorers WHERE player_id = $player_id AND selected = 1"),
                "pts_total" => $score['score'],
                "pts_ice" => $score['ice'],
                "pts_bm" => $score['bigmonster'],
                "pts_lava" => $score['lava'] ,
                "pts_grassland" => $score['grassland'] ,
                "pts_swamp" => $score['swamp'] ,
                "pts_diams" => $score['diams'] ,
                "pts_explo" => $score['explo'] ,
                "pts_medals" => $score['medals'] ,
                "nbr_blue" => $diams['blue'] ,
                "nbr_red" => $diams['red'] ,
                "nbr_green" => $diams['green']);
                $p_stats = $this->getStatList();
            foreach ($p_stats as $stat_name) {
                if ($stat_name != "pts_team") {
                    self::setStat( $player_stat_results[$stat_name], $stat_name, $player_id );
                }
            }
            // set the tie-breaker score
            self::DbQuery( "UPDATE player SET player_score_aux = ".$score['bigmonster']." WHERE player_id='".$player_id."'" );
        }
        
        // get array of winners ids
        $winner_id = array_keys(self::getNonEmptyCollectionFromDB( "SELECT sq1.player_id FROM (SELECT player_id, player_score_aux  FROM `player` WHERE player_score = (SELECT max(player_score) FROM player)) as sq1 WHERE sq1.player_score_aux = (SELECT max(sq2.player_score_aux) FROM (SELECT player_id, player_score_aux  FROM `player` WHERE player_score = (SELECT max(player_score) FROM player)) as sq2)" ));
        
        // compute team score if teamode is enabled
        if ($this->isTeamPlay()) {
            $team_scores = $this->computeTeamScore($breakdowns);
            $winning_team = array_keys($team_scores,max($team_scores));
            foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
                #set stat of team score
                $pteam = $this->get_teams()[$player_id];
                self::setStat( $team_scores[$pteam], "pts_team", $player_id );
            }
        } else {
            $team_scores = array();
            $winning_team = array();
        }
        if (count($winning_team) > 1) {
            // more than 1 team won -> check 1st tie-breaker : the best score of the 2nd teams' member
            $tie_scores_tmp = $this->computeTeamScore($breakdowns, true); // true param is for tie (get highest score of the team)
            $tie_scores = array();
            foreach ($winning_team as $tied_team) {
                $tie_scores[$tied_team] = $tie_scores_tmp[$tied_team];
            }
            $winning_team = array_keys($tie_scores,max($tie_scores));
            if (count($winning_team) > 1 and $this->isTeamPlay()) {
                // tie again -> check 2nd tie-breaker : the most bigmonster point for teams
                $tie_scores = array();
                foreach ($winning_team as $tied_team) {
                    $team_player_ids = $this->getTeamPlayers($tied_team);
                    $score1 = $this->computeScore($team_player_ids[0], true);
                    $score2 = $this->computeScore($team_player_ids[1], true);
                    $tie_scores[$tied_team] = $score1['bigmonster'] + $score2['bigmonster'];
                }
                $winning_team = array_keys($tie_scores,max($tie_scores));
                if (count($winning_team) > 1) {
                    // teams are still tied -> tie is the final situation : record to DB
                    foreach ($winning_team as $tied_team) {
                        $team_player_ids = $this->getTeamPlayers($tied_team);
                        $tiescore = $tie_scores[$tied_team];
                        self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[0]."'" );
                        self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[1]."'" );
                    }
                    // set tie score to 0 for other teams
                    $looser_teams= array();
                    foreach ($this->get_teams() as $val) {
                        if(!in_array($val, $winning_team)){
                            array_push($looser_teams,$val);
                        }
                    }
                    foreach ($looser_teams as $looser_team) {
                        $team_player_ids = $this->getTeamPlayers($looser_team);
                        $tiescore = $tie_scores[$looser_team];
                        self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[0]."'" );
                        self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[1]."'" );
                    }
                } else {
                    // one team won with the 2nd tie-breaker : record to DB
                    $team_player_ids = $this->getTeamPlayers($winning_team);
                    $tiescore = $tie_scores[$winning_team];
                    self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[0]."'" );
                    self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[1]."'" );
                    // set tie score to 0 for other teams
                    $looser_teams= array();
                    foreach ($this->get_teams() as $val) {
                        if(!in_array($val, $winning_team)){
                            array_push($looser_teams,$val);
                        }
                    }
                    foreach ($looser_teams as $looser_team) {
                        $team_player_ids = $this->getTeamPlayers($looser_team);
                        $tiescore = $tie_scores[$looser_team];
                        self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[0]."'" );
                        self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[1]."'" );
                    }
                }
            } else {
                // record tie-breaker score to DB
                $team_player_ids = $this->getTeamPlayers($winning_team);
                $tiescore = $tie_scores[$winning_team];
                self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[0]."'" );
                self::DbQuery( "UPDATE player SET player_score_aux = ".$tiescore." WHERE player_id='".$team_player_ids[1]."'" );
                // set tie score to 0 for other teams
                $looser_teams= array();
                foreach ($this->get_teams() as $val) {
                    if(!in_array($val, $winning_team)){
                        array_push($looser_teams,$val);
                    }
                }
                foreach ($looser_teams as $looser_team) {
                    $team_player_ids = $this->getTeamPlayers($looser_team);
                    $tiescore = $tie_scores[$looser_team];
                    self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[0]."'" );
                    self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[1]."'" );
                }
            }
        } else if (count($winning_team) == 1 and $this->isTeamPlay()) {
            // one team won, no tie-breaker : record to DB
            $team_player_ids = $this->getTeamPlayers($winning_team);
            self::DbQuery( "UPDATE player SET player_score_aux = 1 WHERE player_id='".$team_player_ids[0]."'" );
            self::DbQuery( "UPDATE player SET player_score_aux = 1 WHERE player_id='".$team_player_ids[1]."'" );
            // set tie score to 0 for other teams
            $looser_teams= array();
            foreach ($this->get_teams() as $val) {
                if(!in_array($val, $winning_team)){
                    array_push($looser_teams,$val);
                }
            }
            foreach ($looser_teams as $looser_team) {
                $team_player_ids = $this->getTeamPlayers($looser_team);
                self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[0]."'" );
                self::DbQuery( "UPDATE player SET player_score_aux = 0 WHERE player_id='".$team_player_ids[1]."'" );
            }

        }
        $notif_data = array(
            "breakdowns" => $breakdowns,
            "winner_ids" => $winner_id,
            "team_scores" => $team_scores,
            "winning_team" => $winning_team
        );
        // send notif of end scores
        // update score of BGA framework
        foreach (array_keys($this->loadPlayersBasicInfos()) as $player_id) {
            $score = $this->computeScore($player_id);
            if ($score['delta'] !== 0) {
                // score has changed
                $this->dbSetScore($player_id, $score['score']); // update DB
                self::NotifyAllPlayers("scoreUpdate", '', array(
                    "player_id" => $player_id,
                    "score" => $score['score'],
                    "score_delta" => $score['delta'])
                ); // notify players
            }
        }
        if ($this->isTeamPlay()) {
            foreach (array_unique(array_values($this->get_teams())) as $team ) {
                $players_ids = $this->getTeamPlayers($team);
                if (in_array($team, $winning_team)) {
                    $this->dbSetScore($players_ids[0], 1); // update DB
                    $this->dbSetScore($players_ids[1], 1); // update DB
                } else {
                    $this->dbSetScore($players_ids[0], 0); // update DB
                    $this->dbSetScore($players_ids[1], 0); // update DB

                }
            }
        }
        // send notif for the final score animation
        self::NotifyAllPlayers("endGame_scoring", '', $notif_data);
        $this->gamestate->nextState( 'gameEnd' );

    }



    
//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            if ($statename == "var_tileSelection") {
                $active_row = self::getGameStateValue( 'active_row' );
                if ($active_row == 0) {
                    $lower_row_cards_remaining = $this->custcountCardInLocation( 'hand', 2 );
                    if ($lower_row_cards_remaining > 0) {
                        $source_row = bga_rand( 1, 2 );
                    } else {
                        $source_row = 1;
                    }
                } else {
                    $source_row = $active_row;
                }
                $cards = array_keys($this->custgetCardsInLocation('hand', $source_row));
                $sel_card_idx = bga_rand( 0, count($cards) - 1 );
                $sel_card = $cards[$sel_card_idx];
                array_splice($cards,$sel_card_idx,1);
                $disc_card_idx = bga_rand( 0, count($cards) - 1 );
                $disc_card = $cards[$disc_card_idx];
                $this->cards->moveCard(  $sel_card , 'zombieHand', 0); // move cards to zombie hand
                $this->cards->moveCard(  $disc_card , 'discard', 0); // move cards to discard
                $log_msg = clienttranslate('Zombie selected the ${monster_kind_name} monster and discarded the ${monster_kind_name_dicard} monster in the ${row} row');
                $kind_monster = $this->custgetCard( $sel_card )['type'];
                $kind_monster_discard = $this->custgetCard( $disc_card )['type'];
                $sel_row = ($source_row == 1) ? 'upper' : 'lower';
                self::NotifyAllPlayers("SelectedTile", $log_msg ,array(
                    "monster_kind_name" => $this->tiles_info[$kind_monster]['name'],
                    "monster_kind" => $kind_monster,
                    "row" => $sel_row,
                    "card_id" => $sel_card,
                    "action" => 3,
                    "monster_kind_name_dicard" => $this->tiles_info[$kind_monster_discard]['name'],
                    "discard_card_id" => $disc_card,
                    'i18n' => array( 'monster_kind_name', 'monster_kind_name_dicard' ))
                );
                self::setGameStateValue( 'active_row', $source_row );
                $this->gamestate->nextState( 'var_placeTile' );
            } elseif ($statename == "var_placeTile") {
                // move card on hand to discard
                $sel_card = array_keys($this->custgetCardsInLocation('zombieHand'));
                $this->cards->moveAllCardsInLocation('zombieHand', 'discard' );
                $this->updateLocalDB('card');
                self::NotifyAllPlayers("ZombiePlayedTile", '', ["sel_card" => $sel_card[0]]);
                $this->gamestate->nextState( 'var_endTurn' );
            } else {
                $this->gamestate->nextState( "zombiePass" );
            }
            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            switch ($statename) {
                case 'explorerSelection':
                    // check if explorer has been selected
                    $sel_explo = $this->getExplorer();
                    if (!array_key_exists($active_player, $sel_explo)) {
                        // get possible explorer for zombie player
                        $sql = "SELECT explorer_id FROM explorers WHERE player_id = $active_player";
                        $explo_list = self::getCollectionFromDb( $sql );
                        $explorer_id = intval(array_keys($explo_list)[0]);
                        $sql = "UPDATE explorers SET selected = 1 WHERE player_id = $active_player AND explorer_id = $explorer_id";
                        self::DbQuery($sql);
                        $this->gamestate->setPlayerNonMultiactive( $active_player, 'newRound' );
                    } else {
                        $this->gamestate->setPlayerNonMultiactive( $active_player, 'newRound' );
                    }
                    break;
                case 'tileSelection':
                    $this->updateLocalDB('card');
                    // get card on hand of player
                    $cards = array_keys($this->custgetCardsInLocation('hand', $active_player));
                    //assert(count($cards) > 0);
                    /* if (count($cards) == 0) {
                        sleep(2);
                        $cards = array_keys($this->custgetCardsInLocation('hand', $active_player));
                    } */
                    self::dump('Zombiecards', $cards);
                    $sel_card_idx = bga_rand( 0, count($cards) - 1 );
                    $sel_card = $cards[$sel_card_idx];
                    unset($cards[$sel_card_idx]); // cards_ids contains now the remaining cards list
                    // select randomly another player without cards on ship
                    $player_list = array_keys($this->loadPlayersBasicInfos());
                    self::dump('PlayerList_initial', $player_list);
                    unset($player_list[array_search (intval($active_player), $player_list)]);
                    self::dump('PlayerList_AfterRemovingZombie', $player_list);
                    $cards_on_ship = 1;
                    while ($cards_on_ship > 0) {
                        $target_player_idx = bga_rand(0 , count($player_list) - 1);
                        $target_player = $player_list[$target_player_idx];
                        //check that this player does not have cards on his ship
                        $sql = "SELECT count(card_id) as count FROM card WHERE card_location_arg = $target_player AND card_location = 'onShip'";
                        $cards_on_ship = self::getUniqueValueFromDB($sql);
                        unset($player_list[intval($target_player_idx)]);
                    }
                    self::dump('PlayerList_final', $player_list);
                    self::dump('target_player', $target_player);
                    $current_turn = self::getGameStateValue( 'currentTurn' );
                    if (count($cards) > 1) {
                        // move cards to someone's ship
                        $this->moveCardsFromTo($cards,'hand', 'onShip', $active_player, $target_player);
                        self::NotifyAllPlayers("cardsOnShip", clienttranslate('${player_name} put the rest of his cards to ${player_ship_name} ship'),array(
                            "player_name" => self::getPlayerNameById($active_player),
                            "player_ship_name" => self::getPlayerNameById($target_player),
                            "player_id" => $active_player,
                            "player_ship_id" => strval($target_player),
                            "turn" => $current_turn )
                        );
                    } else {
                        // move cards to discard
                        $this->moveCardsFromTo($cards,'hand', 'discard', $active_player, $active_player);
                        self::NotifyAllPlayers("cardsOnShip", clienttranslate('${player_name} put the last card to discard'),array(
                            "player_name" => self::getPlayerNameById($active_player),
                            "player_id" => $active_player,
                            "player_ship_id" => 0,
                            "turn" => $current_turn)
                        );
                    }
                    // move the selected card to "zombieBoard" (a board for the zombie, never displayed nor used to count points)
                    $this->moveCardsFromTo(array($sel_card),'hand', 'zombieBoard', $active_player, $active_player);
                    $this->gamestate->setPlayerNonMultiactive($active_player, 'placeTile');
                    break;
                case 'placeTile':
                    $this->gamestate->setPlayerNonMultiactive($active_player, 'endTurn');
                    break;
                default:
                    break;
            }
            
            return;
        }

    }
    

//** DB upgrade


    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
        //        if( $from_version <= 1405061421 )
        //        {
        //        ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }


    }    
}
