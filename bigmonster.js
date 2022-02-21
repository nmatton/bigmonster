/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BigMonster implementation : © Nicolas Matton (nicolas@locla.be)
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * bigmonster.js
 *
 * BigMonster user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
 var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
 var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/scrollmap",
    "ebg/stock",
    g_gamethemeurl + "modules/scroller.js",
    g_gamethemeurl + 'modules/modal.js',
],
function (dojo, declare) {
    return declare("bgagame.bigmonster", ebg.core.gamegui, {
        constructor: function(){
            this.SCALE = 100;
            this.tiledwidth = 50;
            this.tileheight = 100;
            this.medalwidth = 100;
            this.medalheight = 100;
            this.tilePerRow = 9; // number of tile per row on sprited tile image
            this.tiles_img_path = 'img/monster_tiles_stock_v2.jpg'; // tile image path for stock component
            this.tile_selected = false; // check if user has selected a tile in his hand
            this.explo_selected = false; // check if user has selected a starting tile
            this.busyShips = [] // list of ships selected in the current turn. Re-initialized at each turn. in INT type !!
            this.buttonAdded = false; // button on the last tile selection
            this.game_mode = 1; // 1 for indiv play; 2 for team play
            this.explorers = {}; // list of explorers
            this.explorer_id; // current player's explorer id
            this.current_move = '0'; // current move of the player
            this.possible_explorers = []; // list of possible explorers
            this.selected_row = 0; // currently row in use (in 2-3 players)
            this.selected_tile_id = 0; // id of the selected tile (in 2-3 players)
            this.active_row = 0; // row that can be selected (0 = both; 1 = upper; 2 = lower) (in 2-3 players)
            this.selected_tile_type = 0; // type of monster selected

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
            setLoader(value, max) {
                this.inherited(arguments);
                if (!this.isLoadingComplete && value >= 100) {
                  this.isLoadingComplete = true;
                  this.onLoadingComplete();
                }
              },
          
              onLoadingComplete() {
                 if (this.hidescore) {
                    debug(this.gamedatas.players)
                    for (var o =  Object.keys(this.gamedatas.players).length - 1; o >= 0; o--) {
                        pid = Object.keys(this.gamedatas.players)[o];
                        debug(pid)
                        $('player_score_' + pid).innerHTML='?';
                        this.addTooltip( 'player_score_' + pid, _('live score is hidden by table option'), '', 10 )
                    }
                } 
              },
        setup: function( gamedatas )
        {
            // Create a new div for buttons to avoid BGA auto clearing it
            dojo.place("<div id='customActions' style='display:inline-block'></div>", $('generalactions'), 'after');
            this.isTeamPlay = gamedatas.isTeamPlay;
            if (this.isTeamPlay) this.game_mode = 2;
            this.teams = gamedatas.teams;
            this.teams_values = Object.values(this.teams).filter(this.onlyUnique)
            this.team_defined = toint(gamedatas.teamdefined);
            this.team_ui_setup = false;
            // **SETUP SCROLL AREAS** //
            this.currentPlayer = this.player_id;
            this.nums_of_players = Object.keys(gamedatas.players).length;
            this.boards = [];
            this.hidescore = gamedatas.hidescore;
            this.is3pdraft = gamedatas.is3pdraft;
            centerscroll = false;
            if (!this.isTeamPlay) {
                // individual game setup
                for (var t of  Object.keys(gamedatas.players)) {
                    this.boards[t] = new Scroller(ebg.scrollmap(),t, 0);
                }
                if (this.isSpectator) {
                    for (var o =  Object.keys(gamedatas.players).length - 1; o >= 0; o--)
                        dojo.place( Object.keys(gamedatas.players)[o] + "_scrollmap_wrapper", "Boards", "after");
                    for (o =  Object.keys(gamedatas.players).length - 1; o >= 0; o--) {
                        dojo.place( Object.keys(gamedatas.players)[o] + "_scrollmap_wrapper", "Boards", "after");
                        if ( Object.keys(gamedatas.players)[o] == this.currentPlayer)
                            break
                    }
                } else {
                    for (var o = gamedatas.playerorder.length - 1; o >= 0; o--)
                        dojo.place(gamedatas.playerorder[o] + "_scrollmap_wrapper", "Boards", "after");
                    for (o = gamedatas.playerorder.length - 1; o >= 0; o--) {
                        dojo.place(gamedatas.playerorder[o] + "_scrollmap_wrapper", "Boards", "after");
                        if (gamedatas.playerorder[o] == this.currentPlayer)
                            break
                    }
                }
                centerscroll = true;
            } else if (this.isTeamPlay && this.team_defined) {
                this.team_ui_setup = true;
                // team setup -- only create scroll area when teams are defined - nothing to show otherwise
                this.player_team = this.teams[this.currentPlayer];
                for (var t of  Object.keys(gamedatas.players)) {
                    this.boards[t] = new Scroller(ebg.scrollmap(),t, 0);
                }
                this.teams_ordered = [];
                 this.teams_values.forEach(element => {
                    this.teams_ordered[element] = Object.keys(this.teams).filter(key => this.teams[key] == element); 
                });
                if (this.isSpectator) {
                    for (var o =  Object.keys(gamedatas.players).length - 1; o >= 0; o--)
                        dojo.place( Object.keys(gamedatas.players)[o] + "_scrollmap", "Boards", "after");
                    for (o =  Object.keys(gamedatas.players).length - 1; o >= 0; o--) {
                        dojo.place( Object.keys(gamedatas.players)[o] + "_scrollmap", "Boards", "after");
                        if ( Object.keys(gamedatas.players)[o] == this.currentPlayer)
                            break
                    }
                } else {
                    dojo.query('.player_info_team').style('display','block')
                    // start by placing other teams
                    for (const team in this.teams_ordered) {
                        if (Object.hasOwnProperty.call(this.teams_ordered, team)) {
                            const team_members = this.teams_ordered[team];
                            team_members.forEach(e => {
                                if (team != this.player_team) {
                                    let team_color = this.gamedatas["players"][this.teams_ordered[team][0]]['color']
                                    dojo.place(e + "_scrollmap_wrapper", "Boards", "after"); // place the scroll area on right place
                                    dojo.style(e + '_team_info','background-color','#'+team_color); // set the team color
                                    $(e + '_team_info').innerHTML='TEAM ' + (toint(team) + 1); // set the team name
                                    // add banner on player miniboard
                                    let tbDiv = this.format_block('jstpl_team_banner', {
                                        color : '#'+team_color,
                                        team_nr: toint(team) + 1
                                    });
                                    player_board = $('player_board_'+e)
                                    dojo.place( tbDiv , player_board);
                                }
    
                            });
                        }
                    }
                    // place current player scroll area
                    let team_color = this.gamedatas["players"][this.teams_ordered[this.player_team][0]]['color']
                    let teammate = this.getOtherTeamMember(this.teams, this.player_team, this.currentPlayer)
                    dojo.place(this.player_id + "_scrollmap_wrapper", "Boards", "after"); // place current player's scroll area just after Boards
                    // styling current player scroll area
                    dojo.style(this.player_id+'_team_info','background-color','#'+team_color);
                    $(this.player_id + '_team_info').innerHTML='TEAM ' + (toint(this.player_team) + 1); // set the team name
                    // add banner on player miniboard
                    let tbDiv = this.format_block('jstpl_team_banner', {
                        color : '#'+team_color,
                        team_nr: toint(this.player_team) + 1
                    });
                    player_board = $('player_board_'+this.player_id)
                    dojo.place( tbDiv , player_board);

                    // place other teams player's scroll areas just after current player's scroll area
                    dojo.place(teammate + "_scrollmap_wrapper", this.player_id + "_scrollmap_wrapper", "after");
                    dojo.style(teammate+'_team_info','background-color','#'+team_color);
                    $(teammate + '_team_info').innerHTML='TEAM ' + (toint(this.player_team) + 1); // set the team name
                    // add banner on player miniboard
                    player_board = $('player_board_'+teammate)
                    dojo.place( tbDiv , player_board);
                    
                }
                centerscroll = true;
            }
            if (centerscroll) {
                for (var t of Object.keys(gamedatas.players)) {
                    if (this.boards.includes(t)) {
                        this.boards[t].scrollTo(-this.SCALE / 2, -this.SCALE / 2)
                    }
                }
            }
            // **** PLAYERS BOARDS SETUP **** //

            if (this.isReadOnly() && typeof g_replayFrom == 'undefined' && !g_archive_mode) {
                dojo.destroy('myhand_wrap');
                dojo.query('.hand_wrapper').style('display','none')
                debug('spector mode');
/*                 let oldParent = document.getElementById('Boards');
                let newParent = document.getElementById('MainBoardArea');
                while (oldParent.childNodes.length) { 	newParent.appendChild(oldParent.firstChild); } */
            }
            // ** EXPLORER TILES ** //
            this.explorers = this.gamedatas.explorers;
            for (var i in this.gamedatas.explorers) {
                var explorer_id = this.explorers[i]['explorer_id'];
                if (i == this.player_id) {
                    this.explorer_id = explorer_id;
                }
                this.placeTile(i, explorer_id,explorer_id, 0,0,0,1,0);
                let explo_info = gamedatas.help_explorers[explorer_id]['descr'];
                if (this.nums_of_players >= 4 || this.is3pdraft) {
                    this.addTooltip( 'tile_e_'+explorer_id, _(explo_info) , _('draft cards to this player'), 10 )
                }
            }
            if (this.nums_of_players >= 4 || this.is3pdraft) {
                dojo.query('.bm_exploTileClass').connect('onclick', this, 'onClickExplo');
            }

            // ** CARDS ON PLAYER BOARDS ** //

            for (var card_id in this.gamedatas.cardsonboard) {
                let player_id = this.gamedatas.cardsonboard[card_id]['card_location_arg'];
                let monster_type = toint(this.gamedatas.cardsonboard[card_id]['card_type']);
                let monster_kind = toint(this.gamedatas.cardsonboard[card_id]['card_type_arg']);
                let mutation = toint(this.gamedatas.cardsonboard[card_id]['mutation']);
                let tileNum = (monster_type - 1 ) * 10 + monster_kind - 1;
                const [x, y, rot] = this.convert_coord(this.gamedatas.cardsonboard[card_id]['board_x'] , this.gamedatas.cardsonboard[card_id]['board_y'], monster_type); 
                this.placeTile(player_id, tileNum, card_id,  x, y, rot, 0, mutation);
            }

            // ** SCORING BOARD SETUP ** //
            if (this.isTeamPlay && this.team_defined) {
                this.setTeamsScoringBoard();
            } else if (!this.isTeamPlay) {
                // setup score board for indivudual play
                for( var player_id in gamedatas.players )
                {
                    var player = gamedatas.players[player_id];
                    // Set up scoring table in advance (helpful for testing!)
                    let splitPlayerName = '';
                    let chars = player.name.split("");
                    for (let i in chars) {
                    splitPlayerName += `<span>${chars[i]}</span>`;
                    }
                    $('scoring-row-players').innerHTML += `<td><span id="scoring-row-name-p${player_id}" style="color:#${player.color};"><span>${splitPlayerName}</span></span></td>`;
                    
                    $('scoring-row-ice').innerHTML += `<td id="scoring-row-ice-p${player_id}"></td>`;
                    $('scoring-row-bigmonster').innerHTML += `<td id="scoring-row-bigmonster-p${player_id}"></td>`;
                    $('scoring-row-lava').innerHTML += `<td id="scoring-row-lava-p${player_id}"></td>`;
                    $('scoring-row-grassland').innerHTML += `<td id="scoring-row-grassland-p${player_id}"></td>`;
                    $('scoring-row-swamp').innerHTML += `<td id="scoring-row-swamp-p${player_id}"></td>`;
                    $('scoring-row-diamonds').innerHTML += `<td id="scoring-row-diamonds-p${player_id}"></td>`;
                    $('scoring-row-explorer').innerHTML += `<td id="scoring-row-explorer-p${player_id}"></td>`;
                    $('scoring-row-medal').innerHTML += `<td id="scoring-row-medal-p${player_id}"></td>`;
                    
                    $('scoring-row-total').innerHTML += `<td id="scoring-row-total-p${player_id}"></td>`;
                }

                // Add an extra column at the end, just for padding reasons
                $('scoring-row-players').innerHTML += `<td></td>`;
                
                $('scoring-row-ice').innerHTML += `<td></td>`;
                $('scoring-row-bigmonster').innerHTML += `<td></td>`;
                $('scoring-row-lava').innerHTML += `<td></td>`;
                $('scoring-row-grassland').innerHTML += `<td></td>`;
                $('scoring-row-swamp').innerHTML += `<td></td>`;
                $('scoring-row-diamonds').innerHTML += `<td></td>`;
                $('scoring-row-explorer').innerHTML += `<td></td>`;
                $('scoring-row-medal').innerHTML += `<td></td>`;
                
                $('scoring-row-total').innerHTML += `<td></td>`;

                // remove the "team score total" row as it is individual play
                dojo.destroy('scoring-row-teamtotal');
            }
            // **** TILES AND HAND MANAGEMENT **** //
            if (!this.isSpectator) {
                // ** Create hands of tiles ** //
                if (this.nums_of_players >= 4 || this.is3pdraft) {
                    // remove board title
                    dojo.destroy('bm_title_board');
                    // remove pile card count (the rem cards is visible in hand)
                    dojo.destroy('card_left_count');
                    // remove "gridded" class
                    dojo.query('#myhand_area').removeClass('bm_gridded')
                    //TODO: ADD A COUNTER OF ROUND
                    this.playerHand = new ebg.stock(); // new stock object for hand
                    this.playerHand.create( this, $('myhand'), this.tiledwidth, this.tileheight );
                    this.playerHand.setSelectionMode( 1 ) // only one card at a time can be selected
                    this.playerHand.extraClasses='bm_margin_stock';
                    this.playerHand.image_items_per_row = this.tilePerRow;
                    var pos = 0;
                    var kind_per_type = [4,2,2,5,1,9,1,6,1];
                    for (var type = 1; type <= 8; type++) {
                        for (var kind_monster = 1; kind_monster <= kind_per_type[type-1]; kind_monster++) {
                            // Build card type id
                            var card_type_id = this.getCardUniqueId(type, kind_monster);
                            pos = (type - 1) * 9 + kind_monster - 1                
                            this.playerHand.addItemType(card_type_id, card_type_id, g_gamethemeurl + this.tiles_img_path, pos);
                        }
                    }
        
                    // ** SET CARDS ON HANDS ** //
                    for ( var i in this.gamedatas.hand) {
                        var card = this.gamedatas.hand[i];
                        var type = toint(card.type);
                        var kind_monster = toint(card.type_arg);
                        this.playerHand.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                        this.setTileToolTip(card.id, type, kind_monster);
                    }
                    // add listeners on cards on hand
                    dojo.connect( this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );
                } else {
                    // remove the ship area
                    dojo.destroy('ships');
                    // remove my hand title
                    dojo.destroy('bm_title_myhand');
                    // update the number in card remaining count
                    dojo.query('#card_counter').innerHTML(this.gamedatas.remaining_piles)
                    // rename my_hand div and add extra row for tiles selection
                    $('myhand').id='upper_row';
                    dojo.place( "<div id='lower_row' class='whiteblock bm_tileArea'></div>" , $('myhand_area'));
                    this.upper_row = new ebg.stock();
                    this.lower_row = new ebg.stock();
                    this.upper_row.create( this, $('upper_row'), this.tiledwidth, this.tileheight );
                    this.lower_row.create( this, $('lower_row'), this.tiledwidth, this.tileheight );
                    this.upper_row.setSelectionMode( 1 ) // only one card at a time can be selected
                    this.lower_row.setSelectionMode( 1 ) // only one card at a time can be selected
                    this.upper_row.extraClasses='bm_margin_stock';
                    this.lower_row.extraClasses='bm_margin_stock';
                    this.upper_row.image_items_per_row = this.tilePerRow;
                    this.lower_row.image_items_per_row = this.tilePerRow;
                    var pos = 0;
                    var kind_per_type = [4,2,2,5,1,9,1,6,1];
                    for (var type = 1; type <= 8; type++) {
                        for (var kind_monster = 1; kind_monster <= kind_per_type[type-1]; kind_monster++) {
                            // Build card type id
                            var card_type_id = this.getCardUniqueId(type, kind_monster);
                            pos = (type - 1) * 9 + kind_monster - 1                
                            this.upper_row.addItemType(card_type_id, card_type_id, g_gamethemeurl + this.tiles_img_path, pos);
                            this.lower_row.addItemType(card_type_id, card_type_id, g_gamethemeurl + this.tiles_img_path, pos);
                        }
                    }
    
                    // SET CARDS ON ROWS
                    this.active_row = this.gamedatas.active_row;
                    for ( var i in this.gamedatas.tilesonrows) {
                        var card = this.gamedatas.tilesonrows[i];
                        var row = card.location_arg;
                        var type = card.type;
                        var kind_monster = card.type_arg;
                        if (toint(row) == 1) {
                            this.upper_row.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                            this.setTileToolTip(card.id, type, kind_monster, 'upper_row');
                        } else if (toint(row) == 2) {
                            this.lower_row.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                            this.setTileToolTip(card.id, type, kind_monster, 'lower_row');
                        } else {
                            // row is equal to player ID => selected card
                            if (toint(this.active_row) == 1) {
                                var tilerow = this.upper_row;
                                var rowname = 'upper_row';
                            } else {
                                var tilerow = this.lower_row;
                                var rowname = 'lower_row';
                            }
                            tilerow.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                            dojo.addClass(rowname + '_item_'+card.id , 'selected');
                            dojo.addClass(rowname + '_item_'+card.id , 'disabled');
                            this.setTileToolTip(card.id, type, kind_monster, rowname);
                            this.selected_tile_id = toint(card.id);
                            this.changePageTitle('discard');
                            this.selected_tile_type = card.type;
                        }
                    }
                    // bottom row
/*                     for ( var i in this.gamedatas.tiles_lower_row) {
                        let card = this.gamedatas.tiles_lower_row[i];
                        let type = card.type;
                        let kind_monster = card.type_arg;
                        this.lower_row.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                        this.setTileToolTip(card.id, type, kind_monster,'lower_row');
                    } */
                    dojo.connect( this.upper_row, 'onChangeSelection', this, 'onTileInRowSelection' );
                    dojo.connect( this.lower_row, 'onChangeSelection', this, 'onTileInRowSelection' );
                    if (toint(this.active_row) == 1) {
                        var id_list = this.lower_row.getAllItems();
                        var rowname = "lower_row";
                        this.lower_row.setSelectionMode( 0 )
                    } else if (toint(this.active_row) == 2) {
                        var id_list = this.upper_row.getAllItems();
                        var rowname = "upper_row";
                        this.upper_row.setSelectionMode( 0 )
                    }
                    if (toint(this.active_row) > 0) {
                        id_list.forEach(element => {
                            dojo.addClass(rowname+'_item_'+element['id'], 'disabled');
                        });
                    }
                }
                if (gamedatas.gamestate.name == "teamSelection") {
                }
            }

            

            // **** MEDAL MANAGEMENT ***** //

            // prepare area for medals in players boards
            for (var t of Object.keys(gamedatas.players)) {
                let maDiv = this.format_block('jstpl_player_board_medal_zone', {
                    player_id : t
                });
                player_board = $('player_board_'+t)
                dojo.place( maDiv , player_board);
            }
            this.medals_status = [];

            // insert medals
            for (const i in this.gamedatas.medals) {
                if (Object.hasOwnProperty.call(this.gamedatas.medals, i)) {
                    const medal = this.gamedatas.medals[i];
                    let medal_id = toint(medal['medal_id']);
                    let info_id = toint((medal_id>10) ? Math.floor(medal_id/10):medal_id);
                    let medal_type = (medal_id>10)?2:1;
                    let location_id = medal['player_id'];
                    let back_id = medal['back_id'];
                    if (location_id == 0) {
                        // add the medal on stock of medals
                        let cardDiv = this.format_block('jstpl_medal_player_stock', {
                            medal_id : medal_id,
                            data_id: info_id,
                            type : medal_type,
                            back_id : back_id
                        });
                        medal_area = $('medals')
                        dojo.place( cardDiv , medal_area);
                        this.medals_status[medal_id] = false;
                    } else {
                        // add the medal to the player area
                        location_id_list = location_id.split(',');
                        location_id_list.forEach(element => {
                            let player_id = toint(element);
                            var player_medal_zone_div = $('ma_'+player_id);
                            dojo.place( this.format_block('jstpl_medal_player_area',{
                                medal_id : medal_id,
                                data_id: info_id,
                                player_id : player_id,
                                type : medal_type,
                                back_id : back_id} ), player_medal_zone_div );
                        });
                        this.medals_status[medal_id] = true;
                    }
                    let medal_info =  (medal_type == 1 ) ? gamedatas.help_medals[medal_id]['name'] : gamedatas.help_medals[info_id]['name_team'];
                    this.addTooltip( 'medal_'+medal_id+'_'+medal_type, medal_info, '', 10 )
                    this.addTooltip( 'back_medal_'+medal_id, _(medal_info), '', 0 )
                }
            }
            if (this.isTeamPlay) {
                let placed_groups = [];
                for (let i in this.medals_status){
                    if (!this.medals_status[i]) {
                        if (i>10) {
                            if (!placed_groups.includes(toint(Math.floor(i/10)))) {
                                let groupDiv = this.format_block('jstpl_medal_group', {
                                    medal_group : Math.floor(i/10)
                                });
                                dojo.place(groupDiv, 'medals');
                                placed_groups.push(toint(Math.floor(i/10)));
                            }
                            dojo.place('stock_'+i, 'bottom_'+Math.floor(i/10));
                            if (i % 10 == 2) {
                                dojo.query('#stock_'+i).addClass('second');
                            }
                        } else {
                            if (!placed_groups.includes(toint(i))) {
                                let groupDiv = this.format_block('jstpl_medal_group', {
                                    medal_group : i
                                });
                                dojo.place(groupDiv, 'medals');
                                placed_groups.push(toint(i));
                            }
                            dojo.place('stock_'+i, 'top_'+i);
                        }
                    }
                }
                this.rearrange_medals();
            }

            if (this.nums_of_players < 4 && $('ma_'+this.gamedatas.first_player) && this.gamedatas.first_player != 0 && !this.is3pdraft) {
                // insert first player medal
                var player_medal_zone_div = $('ma_'+this.gamedatas.first_player);
                dojo.place( this.format_block('jstpl_first_player_medal',{}), player_medal_zone_div, 'first');
                this.addTooltip( 'firstplayermedal', _('first player'), '', 10 )
            }

            // ** HELP AND OPTION ** //

            if (this.isSpectator) {
                dojo.place(jstmp_medal_face_select, document.querySelector('.player-board.spectator-mode'));
                dojo.place(jstpl_helpIcon, document.querySelector('.player-board.spectator-mode'));
                dojo.query('.player-board.spectator-mode .roundedbox_main').style('display', 'none');
            } else {
                dojo.place(jstmp_medal_face_select, 'ma_' + this.player_id);
                dojo.place(jstpl_helpIcon, 'ma_' + this.player_id);

            }
            dojo.connect($('help-icon'), 'click', () => this.displayPlayersHelp(this.nums_of_players));
            let chk = $('face_select');
            dojo.connect(chk, 'onchange', () => this.toggleMedalFace());

            // add button to show front/back
            /*  this code might be usefull...
                <label class="switch">
                <input type="checkbox">
                <span class="slider round"></span>
                </label>
            */
            // **** SHIP TILES MANAGEMENT **** //

            // ** add listeners on ship tiles ** //
            for (o = Object.keys(gamedatas.players).length - 1; o >= 0; o--) {
                dojo.query("div#ship_" + Object.keys(gamedatas.players)[o]).connect("onclick", this, "onClickShipTile")
            }
            // ** add listeners on expand/reduce ship area ** //
             dojo.connect( $('reduce_ships'), 'onclick', this, 'onClickCloseShipArea' );
            dojo.connect( $('expand_ships'), 'onclick', this, 'onClickOpenShipArea' );

            
            // ** set the selectable status of ships ** //
            var turn_n = Math.ceil(toint(gamedatas.gamestate.updateGameProgression) * 17/100) - 1;
            for (var pid in gamedatas.cardsOnShips) {
                if (gamedatas.gamestate.name == "tileSelection") {
                    this.busyShips.push(toint(pid));
                }
                dojo.place( "<div id='tileOnShip_"+ pid +"_"+turn_n+"' class='bm_tileClass backtile'></div>", "ship_" + pid, "last" );
            }


            /* Tooltips */
            if ($('card_left_count')) {
                this.addTooltip('card_left_count',_('remaing pile of cards (i.e. the number of remaining turns)'),'',50)
            }
            this.addTooltip('face_select',_('toggle side of medals'),'',50)
            this.addTooltip('help-icon', '', _('Display game help'));

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            //debug('entering stage' + stateName);
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
            case 'explorerSelection':
                explorers = args.args['_private']['explorers'];
                if (!this.isReadOnly() || g_archive_mode) {
                    // show a popup for selecting explorer
                    // active only for players (not for spectators)
                    if (explorers.length == 2 || g_archive_mode) {
                        // only process when choice is still possible (if length < 2, it means that the player already made its choice)
                        popupcontent = '<div id="bm_popup" class ="bm_popin"><h2 id="popin_playersHelp_title" class="bm_popin_title">'+_('Select a starting explorer')+'</h2><div id="selectingExploDiv">'
                        for (var i = 0; i < explorers.length; i++) {
                            popupcontent += '<div id="explo' + i + '">'
                            var explo_id = explorers[i]['explorer_id'];
                            var explo_info = explorers[i]['explorer_info'];
                            popupcontent += this.tileHtml(explo_id, explo_id, 0, 0, 1, 1, 0);
                            popupcontent += '<p id="exploInfo"> ' + _(explo_info) + '</p></div>'
                        }
                        popupcontent += '</div><button id="conf_expl_btn" class="bm_accept-pending">Confirm selection</button></div>'
                        dojo.place(popupcontent, "handmedal_area", "before");
                        for (var i = 0; i < explorers.length; i++) {
                            var explo_id = explorers[i]['explorer_id'];
                            dojo.query("#tile_" + explo_id).connect("onclick", this, "onClickStartTile")
                            this.possible_explorers.push(explo_id);
                        }
                    }
                }
                // if team mode, add the team specific assets
                if (this.isTeamPlay && !this.team_ui_setup) {
                    // add the scroll areas (where tiles are displayed) + banner on player miniboard
                    this.teams = args.args['_private']['team'];
                    this.teams_values = Object.values(this.teams).filter(this.onlyUnique)
                    this.team_ui_setup = true;
                    player_team = this.teams[this.currentPlayer];
                    for (var t of  Object.keys(this.gamedatas.players)) {
                        this.boards[t] = new Scroller(ebg.scrollmap(),t, 0);
                    }
                    this.teams_ordered = [];
                    this.teams_values.forEach(element => {
                        this.teams_ordered[element] = Object.keys(this.teams).filter(key => this.teams[key] == element); 
                    });
                    if (this.isSpectator) {
                        for (var o =  Object.keys(this.gamedatas.players).length - 1; o >= 0; o--)
                            dojo.place( Object.keys(this.gamedatas.players)[o] + "_scrollmap", "Boards", "after");
                        for (o =  Object.keys(this.gamedatas.players).length - 1; o >= 0; o--) {
                            dojo.place( Object.keys(this.gamedatas.players)[o] + "_scrollmap", "Boards", "after");
                            if ( Object.keys(this.gamedatas.players)[o] == this.currentPlayer)
                                break
                        }
                    } else {
                        dojo.query('.player_info_team').style('display','block')
                        // start by placing other teams
                        for (const team in this.teams_ordered) {
                            if (Object.hasOwnProperty.call(this.teams_ordered, team)) {
                                debug('processing team '+ team);
                                const team_members = this.teams_ordered[team];
                                debug('team_members '+ team_members);
                                team_members.forEach(e => {
                                    if (team != player_team) {
                                        debug('placing team '+ team + ' for player '+ e);
                                        let team_color = this.gamedatas["players"][this.teams_ordered[team][0]]['color']
                                        dojo.place(e + "_scrollmap_wrapper", "Boards", "after"); // place the scroll area on right place
                                        dojo.style(e + '_team_info','background-color','#'+team_color); // set the team color
                                        $(e + '_team_info').innerHTML='TEAM ' + (toint(team) + 1); // set the team name
                                        // add banner on player miniboard
                                        let tbDiv = this.format_block('jstpl_team_banner', {
                                            color : '#'+team_color,
                                            team_nr: toint(team) + 1
                                        });
                                        player_board = $('player_board_'+e)
                                        dojo.place( tbDiv , player_board);
                                    }
        
                                });
                            }
                        }
                        // place current player scroll area
                        debug('placing current player '+ this.currentPlayer);
                        let team_color = this.gamedatas["players"][this.teams_ordered[player_team][0]]['color']
                        let teammate = this.getOtherTeamMember(this.teams, player_team, this.currentPlayer)
                        dojo.place(this.player_id + "_scrollmap_wrapper", "Boards", "after"); // place current player's scroll area just after Boards
                        // styling current player scroll area
                        dojo.style(this.player_id+'_team_info','background-color','#'+team_color);
                        $(this.player_id + '_team_info').innerHTML='TEAM ' + (toint(player_team) + 1); // set the team name
                        // add banner on player miniboard
                        let tbDiv = this.format_block('jstpl_team_banner', {
                            color : '#'+team_color,
                            team_nr: toint(player_team) + 1
                        });
                        player_board = $('player_board_'+this.player_id)
                        dojo.place( tbDiv , player_board);

                        // place other teams player's scroll areas just after current player's scroll area
                        debug('placing other teams player scroll areas');
                        dojo.place(teammate + "_scrollmap_wrapper", this.player_id + "_scrollmap_wrapper", "after");
                        dojo.style(teammate+'_team_info','background-color','#'+team_color);
                        $(teammate + '_team_info').innerHTML='TEAM ' + (toint(player_team) + 1); // set the team name
                        // add banner on player miniboard
                        player_board = $('player_board_'+teammate)
                        dojo.place( tbDiv , player_board);
                        
                    }
                    for (var t of Object.keys(this.gamedatas.players)) {
                        if (this.boards[t]) {
                            this.boards[t].scrollTo(-this.SCALE / 2, -this.SCALE / 2)
                        }
                    }
                    // set the scoring table
                    this.setTeamsScoringBoard();
                }
                break;
            
            case 'tileSelection':
                if (args.args[this.player_id] == 2) {
                    this.lastTurn = true;
                    this.changePageTitle('lasttile');
                }
                debug($('bm_popup'))
                if ($('bm_popup')) {
                    // remove explo selection popup (that remains on replay mode)
                    debug('removing explo popup')
                    dojo.destroy('bm_popup')
                }
                dojo.query(".possibleMoveV").forEach(function(node, index, nodelist){
                    dojo.destroy(node);
                });
                dojo.query(".possibleMoveH").forEach(function(node, index, nodelist){
                    dojo.destroy(node);
                });
                break;
            case 'var_tileSelection':
                this.active_row = args.args[0];
                if (this.active_row == 0) {
                    // reactivate all tiles
                    this.upper_row.setSelectionMode( 1 );
                    this.lower_row.setSelectionMode( 1 );
                    dojo.query('.stockitem').removeClass('disabled')
                }
                if (this.new_turn) {
                    // update the first player token position
                    let active_player =  this.getActivePlayers()[0]
                    if (!$('firstplayermedal')) {
                        var player_medal_zone_div = $('ma_'+active_player);
                        dojo.place( this.format_block('jstpl_first_player_medal',{}), player_medal_zone_div, 'first');
                    } else {
                        this.slideToObjectRelative( "firstplayermedal", "ma_" +active_player, 1000, 0, 'first');
                    }
                    // update the number of cards remaing
                    if (toint($('card_counter').innerHTML) > 0) {
                        dojo.query('#card_counter').innerHTML(toint($('card_counter').innerHTML)-1)
                    }
                    this.new_turn=false;
                }
                if ($('bm_popup')) {
                    // remove explo selection popup (that remains on replay mode)
                    debug('removing explo popup')
                    dojo.destroy('bm_popup')
                }
                dojo.query(".possibleMoveV").forEach(function(node, index, nodelist){
                    dojo.destroy(node);
                });
                dojo.query(".possibleMoveH").forEach(function(node, index, nodelist){
                    dojo.destroy(node);
                });
                break;
            case 'placeTile':
                if (this.playerHand.items.length > 0) {
                    // some card on hand to be placed (if length = 0 means that the tile has been placed previously)
                    var pos = args.args['_private']['possibleMoves'];
                    var hdir = args.args['_private']['placement_dirH'];
                    var vdir = args.args['_private']['placement_dirV'];
                    var dir;
                    var kind_monter = Math.floor(this.playerHand.items[0].type / 10);
                    this.lastPossibleMoveIdx = [0,0]; // initiate recorded move position
                    this.dbMovepos = [0,0]; // selected pos to send to DB
    
                    // show the available places to put tiles
                    for (var idx in pos) {
                        if (kind_monter == 1) {
                            if (hdir[idx] == "X") {
                                continue;
                            }
                            dir = hdir[idx]
                        } else {
                            if (vdir[idx] == "X") {
                                continue;
                            }
                            dir = vdir[idx]
                        }
                        this.addPossiblePlacement(pos[idx][0],pos[idx][1], dir);
                    }
    
                    // connect click event
                    dojo.query('.possibleMoveV').connect('onclick', this, 'onClickPossibleMove');
                    dojo.query('.possibleMoveH').connect('onclick', this, 'onClickPossibleMove');
                } else {
                    // place tile on board that are played (but not displayed due to "last_play = 1")
                    for (const prop in args.args['_private']) {
                        let monster_type = toint(args.args['_private'][prop]['card_type']);
                        let monster_kind = toint(args.args['_private'][prop]['card_type_arg']);
                        let mutation = toint(args.args['_private'][prop]['mutation']);
                        let card_id = prop;
                        let tileNum = (monster_type - 1 ) * 10 + monster_kind - 1;
                        const [x, y, rot] = this.convert_coord(args.args['_private'][prop]['board_x'] , args.args['_private'][prop]['board_y'], monster_type);
                        this.placeTile(this.player_id, tileNum, card_id,  x, y, rot, 0, mutation);
                    }

                }
                
                break;
            case 'var_placeTile':
                dojo.query('.selected').removeClass('disabled');
                if (this.isCurrentPlayerActive()) {
                    var pos = args.args['_private']['possibleMoves'];
                    var hdir = args.args['_private']['placement_dirH'];
                    var vdir = args.args['_private']['placement_dirV'];
                    var dir;
                    var kind_monter = this.selected_tile_type;
                    this.lastPossibleMoveIdx = [0,0]; // initiate recorded move position
                    this.dbMovepos = [0,0]; // selected pos to send to DB
    
                    // show the available places to put tiles
                    for (var idx in pos) {
                        if (kind_monter == 2) {
                            if (hdir[idx] == "X") {
                                continue;
                            }
                            dir = hdir[idx]
                        } else {
                            if (vdir[idx] == "X") {
                                continue;
                            }
                            dir = vdir[idx]
                        }
                        this.addPossiblePlacement(pos[idx][0],pos[idx][1], dir);
                    }
    
                    // connect click event
                    dojo.query('.possibleMoveV').connect('onclick', this, 'onClickPossibleMove');
                    dojo.query('.possibleMoveH').connect('onclick', this, 'onClickPossibleMove');
                }
                break;

            case 'var_endTurn':
                this.selected_tile_id = 0;
                this.selected_row = 0;
                this.selected_tile_type = 0; 
                break;
            case 'bmExploTileSelection':
                if (this.isCurrentPlayerActive()) {
                    // update hand with discard tiles
                    for (var i in args.args) {
                        var card = args.args[i];
                        var type = card.type;
                        var kind_monster = card.type_arg;
                        this.playerHand.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                        this.lastTurn = true;
                    }

                }
                break;

            case 'bmExploTilePlacement':
                if (this.isCurrentPlayerActive()) {
                    // update hand with discard tiles
                    var pos = args.args['_private']['possibleMoves'];
                    var hdir = args.args['_private']['placement_dirH'];
                    var vdir = args.args['_private']['placement_dirV'];
                    var dir;
                    var kind_monter = Math.floor(this.playerHand.items[0].type / 10);
                    this.lastPossibleMoveIdx = [0,0]; // initiate recorded move position
                    this.dbMovepos = [0,0]; // selected pos to send to DB
                    // show the available places to put tiles
                    for (var idx in pos) {
                        if (kind_monter == 1) {
                            if (hdir[idx] == "X") {
                                continue;
                            }
                            dir = hdir[idx]
                        } else {
                            if (vdir[idx] == "X") {
                                continue;
                            }
                            dir = vdir[idx]
                        }
                        this.addPossiblePlacement(pos[idx][0],pos[idx][1], dir);
                    }
                    // connect click event
                    dojo.query('.possibleMoveV').connect('onclick', this, 'onClickPossibleMove');
                    dojo.query('.possibleMoveH').connect('onclick', this, 'onClickPossibleMove');
                }
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'tileSelection':
                this.busyShips = [];
                break;
            case 'var_newTurn':
                this.new_turn = true;
            }

        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
                      
            if( this.isCurrentPlayerActive())
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                    case 'placeTile':
                        if (this.tile_selected) {
                            dojo.empty('customActions');
                            this.addActionButton( 'button_conf_move', _('Place Tile'), 'onClickConfirmTilePositionButton', 'customActions' );
                        }
                        break;
                    case 'teamSelection' :
                        if (this.isTeamPlay) {
                            debug(this.gamedatas)
                            dojo.empty('customActions');
                            Object.values(this.gamedatas.playerorder).forEach(t => {
                                if (t != this.player_id) {
                                    this.addActionButton('buttonSelectPlayer' + t, this.coloredPlayerName(this.gamedatas.players[t].name), () => this.selectTeamPlayer(t), 'customActions');
                                }
                            })
                        }
                        break;
                }
            }
        },        

        
        // ** Utility methods **
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        /* 
        * Manage open and closing ship area
        */

        onClickOpenShipArea() {
            dojo.query('.ship_wrapper').toggleClass('bm_retracted');
            setTimeout(()=>{dojo.query('#ships').toggleClass('bm_hidden_ships');dojo.query('#ships').toggleClass('bm_transparent_ships')},1000)
             dojo.query('#reduce_ships').style('display', 'inline');
            dojo.query('#expand_ships').style('display', 'none'); 
        },

        onClickCloseShipArea() {
            dojo.query('#ships').toggleClass('bm_transparent_ships');
            setTimeout(()=>{dojo.query('#ships').toggleClass('bm_hidden_ships');dojo.query('.ship_wrapper').toggleClass('bm_retracted')},1000)
            dojo.query('#reduce_ships').style('display', 'none');
            dojo.query('#expand_ships').style('display', 'inline');
        }, 

        /*
        * Add a timer on an action button :
        * params:
        *  - buttonId : id of the action button
        *  - time : time before auto click
        *  - pref : 0 is disabled (auto-click), 1 if normal timer, 2 if no timer and show normal button
        */

        startActionTimer(buttonId, time, pref, autoclick = false) {
            var button = $(buttonId);
            var isReadOnly = this.isReadOnly();
            debug($(buttonId));
            if (button == null || isReadOnly || pref == 2) {
                debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly, 'prefValue=' + pref);
                return;
            }
    
            // If confirm disabled, click on button
            if (pref == 0) {
            if (autoclick) button.click();
            return;
            }
    
            this._actionTimerLabel = button.innerHTML;
            this._actionTimerSeconds = time;
            this._actionTimerFunction = () => {
            var button = $(buttonId);
            if (button == null) {
                this.stopActionTimer();
            } else if (this._actionTimerSeconds-- > 1) {
                button.innerHTML = this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')';
            } else {
                debug('Timer ' + buttonId + ' execute');
                button.click();
            }
            };
            this._actionTimerFunction();
            this._actionTimerId = window.setInterval(this._actionTimerFunction, 1000);
            debug('Timer #' + this._actionTimerId + ' ' + buttonId + ' start');
        },
    
        stopActionTimer() {
            if (this._actionTimerId != null) {
            debug('Timer #' + this._actionTimerId + ' stop');
            window.clearInterval(this._actionTimerId);
            delete this._actionTimerId;
            }
        },

        rearrange_medals() {
            let groups = dojo.query('.medal-group');
            groups.forEach(element => {
                if (element.childNodes[0].childElementCount == 0) {
                    dojo.query('#'+element.id).addClass('bottom_only');
                    debug('bottom only of '+element.id);
                }
            });
        },

        getOtherTeamMember(team_array, team_value, player_id) {
            return Object.keys(team_array).find(key => team_array[key] == team_value && key != player_id);
        },

        onlyUnique(value, index, self) {
            return self.indexOf(value) === index;
        },

        onScreenWidthChange() {
            dojo.style('page-content', 'zoom', '');
            dojo.style('page-title', 'zoom', '');
            dojo.style('right-side-first-part', 'zoom', '');
            // recentering play areras
            if (typeof this.boards !== 'undefined') {
                /* if ($('MainBoardArea').offsetWidth < 650) {
                    dojo.query('.scrollerClass').style('width','90%')
                } else {
                    dojo.query('.scrollerClass').style('width','46%')
                } */
                for (var t of Object.keys(this.gamedatas.players)) {
                    if(this.boards.length > 0) this.boards[t].scrollTo(-this.SCALE / 2, -this.SCALE / 2)
                }
            }
        },

        toggleMedalFace() {
            dojo.query('.medal_area_inner').toggleClass('flipped')
        },

        isReadOnly() {
            return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
            },

        setTeamsScoringBoard() {
            // setup score board for teams
            this.teams_ordered = [];
            this.teams_values.forEach(element => {
               this.teams_ordered[element] = Object.keys(this.teams).filter(key => this.teams[key] == element); 
            });
            // add line below on indiv score row
            dojo.query('#scoring-row-total').addClass('line-below')
            // insert team names row (just under the names)
            var table = document.getElementById("scoretable");
            var row = table.insertRow(1);
            row.setAttribute('id','team-title');
            row.setAttribute('class','line-below');
            var cell = row.insertCell(-1);
            cell.setAttribute('class','first-column');
            cell.innerHTML='TEAMS';
            for (var team in this.teams_ordered) {
                var teamno = 1;
                for (var pid in this.teams_ordered[team]) {
                    player_id = this.teams_ordered[team][pid];
                    var player = this.gamedatas.players[player_id];
                    let splitPlayerName = '';
                    let chars = player.name.split("");
                    for (let i in chars) {
                    splitPlayerName += `<span>${chars[i]}</span>`;
                    }
                    if (teamno == 2 && team != this.teams_ordered.length-1) {
                        $('scoring-row-players').innerHTML += `<td><span id="scoring-row-name-p${player_id}" class="teamsplit" style="color:#${player.color};"><span>${splitPlayerName}</span></span></td>`;
                    
                        $('scoring-row-ice').innerHTML += `<td id="scoring-row-ice-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-bigmonster').innerHTML += `<td id="scoring-row-bigmonster-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-lava').innerHTML += `<td id="scoring-row-lava-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-grassland').innerHTML += `<td id="scoring-row-grassland-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-swamp').innerHTML += `<td id="scoring-row-swamp-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-diamonds').innerHTML += `<td id="scoring-row-diamonds-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-explorer').innerHTML += `<td id="scoring-row-explorer-p${player_id}" class="teamsplit"></td>`;
                        $('scoring-row-medal').innerHTML += `<td id="scoring-row-medal-p${player_id}" class="teamsplit"></td>`;

                        $('scoring-row-total').innerHTML += `<td id="scoring-row-total-p${player_id}" class='teamsplit'></td>`;
                    } else {

                        $('scoring-row-players').innerHTML += `<td><span id="scoring-row-name-p${player_id}" style="color:#${player.color};"><span>${splitPlayerName}</span></span></td>`;
                        
                        $('scoring-row-ice').innerHTML += `<td id="scoring-row-ice-p${player_id}"></td>`;
                        $('scoring-row-bigmonster').innerHTML += `<td id="scoring-row-bigmonster-p${player_id}"></td>`;
                        $('scoring-row-lava').innerHTML += `<td id="scoring-row-lava-p${player_id}"></td>`;
                        $('scoring-row-grassland').innerHTML += `<td id="scoring-row-grassland-p${player_id}"></td>`;
                        $('scoring-row-swamp').innerHTML += `<td id="scoring-row-swamp-p${player_id}"></td>`;
                        $('scoring-row-diamonds').innerHTML += `<td id="scoring-row-diamonds-p${player_id}"></td>`;
                        $('scoring-row-explorer').innerHTML += `<td id="scoring-row-explorer-p${player_id}"></td>`;
                        $('scoring-row-medal').innerHTML += `<td id="scoring-row-medal-p${player_id}"></td>`;

                        $('scoring-row-total').innerHTML += `<td id="scoring-row-total-p${player_id}"></td>`;
                    }
                    teamno += 1;
                }
                if (team != this.teams_ordered.length-1) {
                    $('scoring-row-teamtotal').innerHTML += `<td colspan="2" id="scoring-row-team-t${team}" class='teamsplit'></td>`;
                } else {
                    $('scoring-row-teamtotal').innerHTML += `<td colspan="2" id="scoring-row-team-t${team}"></td>`;

                }
                let team_color = this.gamedatas["players"][this.teams_ordered[team][0]]['color']
                let tbDiv = this.format_block('jstpl_team_banner', {
                    color : '#'+team_color,
                    team_nr: toint(team) + 1
                });
                var cell = row.insertCell();
                cell.setAttribute('colspan',"2");
                cell.innerHTML = tbDiv;
            }
        },
        
        setTileToolTip: function(id, type, kind_monster, location='myhand') {
            debug('add tooltip to '+id+' with type : '+type+' and kind : '+kind_monster);
            if ( [3,5,7].includes(toint(type)) ) {
                var monster_name = this.gamedatas.help_monsters[toint(type)]['name'];
                var monster_descr = this.gamedatas.help_monsters[toint(type)]['descr'];
            } else {
                var monster_name = this.gamedatas.help_monsters[toint(type)][toint(kind_monster)]['name'];
                var monster_descr = this.gamedatas.help_monsters[toint(type)][toint(kind_monster)]['descr'];
            }
            if (toint(type) == 2) {
                var rot = "transform: rotate(-90deg);"
            } else {
                var rot = '';
            }
            this.addTooltipHtml( location+'_item_'+id, "<div style='width: 250px; text-align: center;'><div><h3>"+_(monster_name)+"</h3><hr></div><div class='bm_tileClass tooltip_tile tooltipWiggle' style='background-position: -"+(toint(kind_monster)-1)*100+"% -"+(toint(type)-1)*100+"%; "+rot+"'></div><br><p>"+_(monster_descr)+"</p></div>", 10 );    
        },

        tileHtml: function(e, id, s, t, b=0, i=0, m=0) {
            /* 
                Generate Html code to insert a tile on a board

                Parameters:
                e : tile number (dozen : row ; unit : col. e.g. 32 is "head" of bigmonster (right side))
                id: tile id
                s : x position 
                t : y position 
                u : user id
                b=0 : big tile (twice larger - explorer tile)
                i=0 : get html content for explorer selection
                m=0 : mutation level

                Based on a project with input image of 3 collumns and 30 rows of sprited tiles

                output example : 
                <div id="tile_66" class="tileClass" style="top: 100px; left: 0px; background-position: -200px -2100px;"></div>
            */
            if (b==1) {
                var iclass = "bm_exploTileClass"
                var l = e-1
                , a = Math.floor(l/3)  //row on the image
                , r = l % 3 // col on the image
            } else if (m==0) {
                var iclass = "bm_tileClass"
                var a = Math.floor(e/10)  //row on the image
                , r = e - Math.floor(e / 10) * 10 // col on the image
            } else if (m>0) {
                var iclass = "bm_tileClass"
                var a = 9 // last row of image
                , r = e + (m - 1) * 4; // col (m is mutation level : 1 or 2)
            }
                
            var n = "";
            var c = '"top:' + t * this.SCALE + "px; left:" + s * this.SCALE + "px; ";
            i && (c = '"position:relative; display:inline-block; margin:10px auto; cursor:pointer; ');
            return '<div id="tile_' + id + '" class="'+ iclass +'" style=' + (c += "background-position: -" + 100 * r + "% -" + 100 * a + '%"; data-x="'+100*s+'"; data-y="'+100 *t+'"') + ">" + n + "</div>"
        },
        placeTile: function(s, t, id,  o, i, l, b=0, m=0) {
            /* 
                Place a tile on board of a specific player

                Parameters
                s : player id (e.g : 0,1,2,3). Should be refereing the boards order
                t : tile number -> link to which monster it is
                id : tile ID (as in DB)
                o : x position on the board
                i : y position on the board
                l : rotation
                b(=0) : big tile to place (the explorer tile)
                m(=0) : mutated tile
            */
            if (b) {
                bti = 'e_'+id;
            } else {
                //bti = this.boardTileId(s,o,i);
                bti = id;
            }
            this.boards[s].addHtml(this.tileHtml(t, bti, o, i, b, 0, m),b);
            //this.placeOnObject("tile_" + t, "overall_player_board_" + s); // place tile on the player board for initial animation position
            var c = dojo.byId("tile_" + bti).style.transform
                , d = 0;
            "" != c && (d = c.match(/\d+/)[0]);
            dojo.animateProperty({
                node: dojo.byId("tile_" + bti),
                duration: 200,
                properties: {
                    propertyTransform: {
                        start: d,
                        end: l
                    }
                },
                onAnimate: function(s) {
                    dojo.style(this.node, "transform", "rotate(" + parseFloat(s.propertyTransform.replace("px", "")) + "deg)")
                }
            }).play();
            this.boards[s].moveIdToPos(this, "tile_" + bti, o * this.SCALE, i * this.SCALE, b, 300)
        },

        sendCardToShip: function(player_id)
        {
            var t = player_id;
            if (this.checkAction('selectShip', true) && !this.lastTurn && this.tile_selected && ((t != this.player_id && !this.busyShips.includes(toint(t))) || (this.explorer_id == 2 && !this.busyShips.includes(toint(t))) || (t == this.player_id && this.busyShips.length == Object.keys(this.gamedatas.players).length - 1))) {
                // 3 main conditions:
                //  1/ Action "selectShip" is current
                //  2/ A tile is selected in hand
                //  3/  either : A) the ship is not the one of the player and not in list of busy ships
                //               B) the explorer is black female, the player can put card on any ship if not in busyships
                //               C) the selected ship if the one of the player, and all ships are in fact busy... (we made the ship of the player busy by default)
                // retrieve the non-selected cards
                var sel_cards_list = this.playerHand.getSelectedItems();
                var unsel_cards_list = this.playerHand.getUnselectedItems();
                var unsel_cards = [];
                for (var i in unsel_cards_list) {
                    unsel_cards.push(unsel_cards_list[i]['id'])
                }
                // sent info to server
                dojo.empty('customActions');
                this.ajaxcall( '/bigmonster/bigmonster/selectShip.html', { lock: true, 
                    ship_player_id : t,
                    rem_cards : unsel_cards.toString(),
                    sel_card : sel_cards_list[0]['id']
                 }, this, function( result ) {
                    dojo.empty('customActions');
                 } );
            } else if (!this.tile_selected) {
                // the user did not selected a tile in his hand
                this.showMessage( _("You must select a tile in your hand first"), "error" )
            } else if ((t == this.player_id && this.explorer_id != 2 && this.busyShips.length != this.gamedatas.playerorder.length - 1) || this.busyShips.includes(toint(t)) ) {
                // the user is trying to put the rest of his hand on his own ship
                this.showMessage( _("You must select the ship of another player"), "error" )
            } else if (this.lastTurn) {
                this.showMessage( _("You have to click the 'Select Tile' button"), "error" )
            }
        },

        addPossiblePlacement: function(x,y,d)
        {
            if (d == "U" || d == "D") {
                className = "possibleMoveV";
            } else {
                className = "possibleMoveH";
            }            
            var html = '<div id="move_'+x+'*'+y+'" class="'+className+'" style="'+
                    'top:'+(y*this.SCALE/2)+'px; left:'+(x*this.SCALE/2)+'px;'+
                    '"></div>';
            this.boards[this.player_id].addHtml(html,1);
            $('move_'+x+'*'+y+'').dataset.posX = x;
            $('move_'+x+'*'+y+'').dataset.posY = y;
            var sel_tile = (dojo.query('.stockitem.selected')[0] == null) ? dojo.query('.stockitem')[0] : dojo.query('.stockitem.selected')[0];
            if (d == "D") {
                // expansion to "down" (default)
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    // define new position
                    if (!evt.target.classList.value.includes("selected_pos")) {
                        let pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                        let ximg = sel_tile.style.backgroundPositionX;
                        let yimg = sel_tile.style.backgroundPositionY;
                        dojo.addClass(evt.target, 'bm_tileClass');
                        dojo.style(evt.target, 'backgroundPositionX', toint(ximg)/2+'px' );
                        dojo.style(evt.target, 'backgroundPositionY', toint(yimg)+'px');
                        let otile = '#move_'+toint(pos[0])+'*'+toint(toint(pos[1])+1);
                        dojo.query(otile).addClass('hidden_pos')
                    }
                 });
            } else if (d == "U") {
                // expansion to "up" (need to change position)
                dojo.addClass($( 'move_'+x+'*'+y+''), 'up');
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    let pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                    dojo.style(evt.target, 'top', toint(pos[1]) * 100/2 - 50 + 'px'); // set new pos for upper expansion
                    let ximg = sel_tile.style.backgroundPositionX;
                    let yimg = sel_tile.style.backgroundPositionY;
                    if (!evt.target.classList.value.includes("selected_pos")) {
                        dojo.addClass(evt.target, 'bm_tileClass');
                    }
                    dojo.style(evt.target, 'backgroundPositionX', toint(ximg)/2+'px' );
                    dojo.style(evt.target, 'backgroundPositionY', toint(yimg)+'px');
                 });
            } else if (d == "R") {
                // expansion to right
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    if (!evt.target.classList.value.includes("selected_pos")) {
                        let pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                        let ximg = sel_tile.style.backgroundPositionX;
                        if (toint(ximg) == -100) {
                            dojo.addClass(evt.target, 'bm_tileClassH1');
                        } else {
                            dojo.addClass(evt.target, 'bm_tileClassH2');
                        }
                        let otile = '#move_'+toint(toint(pos[0])+1)+'*'+toint(toint(pos[1])+0);
                        dojo.query(otile).addClass('hidden_pos')
                    }
                    
                 });
            } else if (d == "L") {
                // expansion to left
                dojo.addClass($( 'move_'+x+'*'+y+''), 'left');
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    let pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                    dojo.style(evt.target, 'left', toint(pos[0]) * 100/2 - 50 + 'px'); // set new pos for left expansion
                    if (!evt.target.classList.value.includes("selected_pos")) {
                        let ximg = sel_tile.style.backgroundPositionX;
                        if (toint(ximg) == -100) {
                            dojo.addClass(evt.target, 'bm_tileClassH1');
                        } else {
                            dojo.addClass(evt.target, 'bm_tileClassH2');
                        }
                    }
                 });
            }
            // when leaving the possible place, set back all to "default"
            dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseleave', function(evt) { 
                var id = dojo.attr(evt.target, 'id');
                var pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                let classList = dojo.query('#'+id)[0].classList;
                let isSelectedMove = false;
                for (const key in classList) {
                    if (Object.hasOwnProperty.call(classList, key)) {
                        const element = classList[key];
                        if (element == "selected_pos") {
                            isSelectedMove = true;
                        }
                    }
                }
                dojo.style(evt.target, 'left', pos[0] * 100/2 + 'px');
                dojo.style(evt.target, 'top', pos[1] * 100/2 + 'px');
                dojo.removeClass(evt.target, 'bm_tileClass');
                dojo.removeClass(evt.target, 'bm_tileClassH1');
                dojo.removeClass(evt.target, 'bm_tileClassH2');
                if (!isSelectedMove) {
                    dojo.query('.possibleMoveV').removeClass('hidden_pos');
                    dojo.query('.possibleMoveH').removeClass('hidden_pos')
                }
             });
            
        },

        // Get card unique identifier based on its type and "value"
        getCardUniqueId : function(type, value) {
            return (type - 1) * 10 + (value - 1);
        },

        // replace the "stock" cards to an static html element
        cardOnShipAnimEnded : function(player_ship_id, turn_n) {
            dojo.place( "<div id='tileOnShip_"+ player_ship_id +"_"+turn_n+"' class='bm_tileClass backtile'></div>", "ship_" + player_ship_id, "last" );
        },

        activateShips : function() {
            this.removeActionButtons();
            Object.values(this.gamedatas.playerorder).forEach(t => {
                if (!this.busyShips.includes(toint(t)) && (t != this.player_id || (this.busyShips.length == this.gamedatas.playerorder.length - 1 && t == this.player_id ) || (this.explorer_id == 2 && t == this.player_id )) ) {
                    // activate the ship tiles (make them blinking)
                    dojo.addClass("ship_" + t,"bm_selectable");
                    // add button on the status bar
                    this.addActionButton('buttonSelectPlayer' + t, this.coloredPlayerName(this.gamedatas.players[t].name), () =>
                        this.sendCardToShip(t), 'customActions'
                    );
                    // activate the explorer
                    dojo.addClass('tile_e_' + this.explorers[t]['explorer_id'], 'bm_selectable');
                }
            })
        },

        desactivateShips : function() {
            this.removeActionButtons();
            dojo.empty('customActions');
            for (var t of this.gamedatas.playerorder) {
                dojo.removeClass("ship_" + t,"bm_selectable");
                dojo.removeClass('tile_e_' + this.explorers[t]['explorer_id'], 'bm_selectable');
            }
        },

        convert_coord : function(in_x, in_y ,type) {
            if (type == 2) {
                var x = (toint(in_x) / 200 + 50 * toint(in_x) + 25) / 200;
                var y = ((toint(in_y) / 200) + 50 * toint(in_y) - 25 ) / 200 ;
                var rot = -90;
            } else {
                //var x = (toint(in_x) / 200 + 50 * toint(in_x) + 25) / 200;
                //var y = ((toint(in_y) / 200) + 50 * toint(in_y) - 25 ) / 200 ;
                var x = in_x / 4;
                var y = in_y / 4;
                var rot = 0;
            }
            return [x,y,rot];
        },

        boardTileId : function(player_id, x, y){
            return player_id + '_' + x + '_' + y
        },

        /* 
            * Following utility methods comes from BGAsharedCode
            * https://github.com/elaskavaia/bga-sharedcode/blob/master/modules/sharedparent.js
        */

        /*
            * This method will remove all inline style added to element that affect positioning
        */
        stripPosition: function (token) {
            // remove any added positioning style
            token = $(token);

            token.style.removeProperty("display");
            token.style.removeProperty("top");
            token.style.removeProperty("bottom");
            token.style.removeProperty("left");
            token.style.removeProperty("right");
            token.style.removeProperty("position");
            // dojo.style(token, "transform", null);
        },
        stripTransition: function (token) {
            this.setTransition(token, "");
        },
        setTransition: function (token, value) {
            dojo.style(token, "transition", value);
            dojo.style(token, "-webkit-transition", value);
            dojo.style(token, "-moz-transition", value);
            dojo.style(token, "-o-transition", value);
        },

        attachToNewParentNoDestroy: function (mobile_in, new_parent_in, relation, place_position) {
            const mobile = $(mobile_in);
            const new_parent = $(new_parent_in);

            var src = dojo.position(mobile);
            if (place_position)
                mobile.style.position = place_position;
            dojo.place(mobile, new_parent, relation);
            mobile.offsetTop;//force re-flow
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;

            mobile.style.position = "absolute";
            mobile.style.left = left + "px";
            mobile.style.top = top + "px";
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            mobile.offsetTop;//force re-flow
            return box;
        },

        /*
         * This method is similar to slideToObject but works on object which do not use inline style positioning. It also attaches object to
         * new parent immediately, so parent is correct during animation
         */
        slideToObjectRelative: function (token, finalPlace, duration, delay, relation='first', onEnd=null) {
            token = $(token);
            this.delayedExec(() => {
                token.style.transition = "none";
                token.classList.add('moving_token');
                var box = this.attachToNewParentNoDestroy(token, finalPlace, relation, 'static');
                token.offsetHeight; // re-flow
                token.style.transition = "all " + duration + "ms ease-in-out";
                token.style.left = box.l + "px";
                token.style.top = box.t + "px";
            }, () => {
                token.style.removeProperty("transition");
                this.stripPosition(token);
                token.classList.remove('moving_token');
                if (onEnd) onEnd(token);
            }, duration, delay);
        },
        slideToObjectAbsolute: function (token, finalPlace, x, y, duration, delay, relation='first', onEnd=null) {
            token = $(token);
            this.delayedExec(() => {
                token.style.transition = "none";
                token.classList.add('moving_token');
                this.attachToNewParentNoDestroy(token, finalPlace, relation, 'absolute');
                token.offsetHeight; // re-flow
                token.style.transition = "all " + duration + "ms ease-in-out";
                token.style.left = x + "px";
                token.style.top = y + "px";
            }, () => {
                token.style.removeProperty("transition");
                token.classList.remove('moving_token');
                if (onEnd) onEnd(token);
            }, duration, delay);
        },

        slideToObjectRelativeDojo: function (token_in, finalPlace, duration, delay, relation='first', onEnd=null) {
            const token = $(token_in);

            if (duration === undefined) {
                duration = 500;
            }
            if (delay === undefined) {
                delay = 0;
            }
            if (this.instantaneousMode) {
                delay = 0;
                duration = 0;
            }

            var combinedOnEnd = (node) => { this.stripPosition(node); if (onEnd) onEnd(node); };
            this.stripPosition(token);
            var box = this.attachToNewParentNoDestroy(token, finalPlace, relation);

            var anim = dojo.fx.slideTo({
                node: token,
                top: box.t,
                left: box.l,
                delay: delay,
                duration: duration,
                unit: "px",
                onEnd: combinedOnEnd
            });
            anim.play();
            return anim;
        },

        positionObjectDirectly: function (mobileObj, x, y) {
            // do not remove this "dead" code some-how it makes difference
            dojo.style(mobileObj, "left"); // bug? re-compute style
            dojo.style(mobileObj, {
                left: x + "px",
                top: y + "px"
            });
            dojo.style(mobileObj, "left"); // bug? re-compute style
        },
        delayedExec: function (onStart, onEnd, duration, delay) {
            if (typeof duration == "undefined") {
                duration = this.defaultAnimationDuration;
            }
            if (typeof delay == "undefined") {
                delay = 0;
            }
            if (this.instantaneousMode) {
                delay = Math.min(1, delay);
                duration = Math.min(1, duration);
            }
            if (delay) {
                setTimeout(function () {
                    onStart();
                    if (onEnd) {
                        setTimeout(onEnd, duration);
                    }
                }, delay);
            } else {
                onStart();
                if (onEnd) {
                    setTimeout(onEnd, duration);
                }
            }
        },


        displayPlayersHelp(p_num) {
            if (p_num < 5) {
                new customgame.modal('playersHelp', {
                  autoShow: true,
                  title: _('Quick help'),
                  class: 'bm_popin',
                  closeIcon: 'fa-times',
                  openAnimation: true,
                  openAnimationTarget: 'help-icon',
                  contentsTpl: jstpl_helpDialog,
                  verticalAlign: 'flex-start',
                });
            } else {
                new customgame.modal('playersHelp', {
                  autoShow: true,
                  title: _('Quick help'),
                  class: 'bm_popin',
                  closeIcon: 'fa-times',
                  openAnimation: true,
                  openAnimationTarget: 'help-icon',
                  contentsTpl: jstpl_helpDialog5p,
                  verticalAlign: 'flex-start',
                });
            }
          },

        addBackMedal : function(medal_id, back_id, player_id, totdelay){
            var mia_div = $('mia_'+player_id+'_'+medal_id);
            setTimeout(() => { 
                dojo.place( this.format_block('jstpl_back_medal', {medal_id : medal_id, back_id : back_id} ), mia_div );
            }, totdelay);
            setTimeout(() => { dojo.destroy('stock_'+medal_id);}, 100);
        },

        coloredPlayerName(name, color_only=false) {
            const player = Object.values(this.gamedatas.players).find((player) => player.name == name);
            if (player == undefined) return '<!--PNS--><span class="playername">' + name + '</span><!--PNE-->';
      
            const color = player.color;
            if (color_only) return color;
            const color_bg = player.color_back
              ? 'background-color:#' + this.gamedatas.players[this.player_id].color_back + ';'
              : '';
            return (
              '<!--PNS--><span class="playername" style="color:#' + color + ';' + color_bg + '">' + name + '</span><!--PNE-->'
            );
        },

        resetPageTitle() {
            this.changePageTitle();
        },
      
        changePageTitle(suffix = null, save = false) {
            if (suffix == null) {
                suffix = 'generic';
            }
        
            if (!this.gamedatas.gamestate['descriptionmyturn' + suffix]) return;
        
            if (save) {
                this.gamedatas.gamestate.descriptionmyturngeneric = this.gamedatas.gamestate.descriptionmyturn;
                this.gamedatas.gamestate.descriptiongeneric = this.gamedatas.gamestate.description;
            }
        
            this.gamedatas.gamestate.descriptionmyturn = this.gamedatas.gamestate['descriptionmyturn' + suffix];
            if (this.gamedatas.gamestate['description' + suffix])
                this.gamedatas.gamestate.description = this.gamedatas.gamestate['description' + suffix];
            this.updatePageTitle();
        },
        //** Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        selectTeamPlayer: function(in_pID) {
            const targer_player = in_pID;
            if (this.checkAction('selectTeam', true)) {
                dojo.empty('customActions');
                this.ajaxcall( '/bigmonster/bigmonster/selectTeam.html', { lock: true, 
                    player_id : this.player_id,
                    team_player_id : targer_player
                    }, this, function( result ) {
                        dojo.empty('customActions');
                    } );
            }
        },

        onClickShipTile: function(s) {
            var t = parseInt(s.target.id.split("_")[1]);
            dojo.stopEvent(s);
            this.sendCardToShip(t);
        },

        onClickExplo: function(s) {
            var parent_id = $(s.target.id).parentNode.id;
            var t = parseInt(parent_id.split("_")[0]);
            dojo.stopEvent(s);
            this.sendCardToShip(t);
        },

        onPlayerHandSelectionChanged : function(s) {
            var items = this.playerHand.getSelectedItems();
            if (items.length > 0) {
                if (this.checkAction('selectTile', true)) {
                    if (this.lastTurn) {
                        if ($('SelConfbutton') == null) {
                            this.addActionButton( 'SelConfbutton', _('Select Tile'), 'onClickSelectLastTile' ,'customActions');
                        } else {
                            dojo.removeClass( 'SelConfbutton', 'disabled');
                        }
                    } else {
                        this.activateShips();
                    }
                    this.tile_selected = true;
                }
                else {
                    this.playerHand.unselectAll();
                }
            } else {
                // unselecting tile
                if (this.lastTurn && $('SelConfbutton') != null) {
                    dojo.addClass( 'SelConfbutton', 'disabled');
                } else {
                    this.desactivateShips();
                }
                this.tile_selected = false;
            }
        },

        onTileInRowSelection : function (s) {
            //dojo.stopEvent(s);
            if (s == 'upper_row' & (this.selected_row === 1 || this.selected_row === 0)) {
                var tilerow = this.upper_row;
                this.selected_row = 1;
            } else if (s == 'upper_row' & this.selected_row !== 1) {
                var tilerow = this.upper_row;
                this.lower_row.unselectAll();
                this.selected_row = 1;
            } else if (s == 'lower_row' & (this.selected_row === 2 || this.selected_row === 0)) {
                var tilerow = this.lower_row;
                this.selected_row = 2;
            } else if (s == 'lower_row' & this.selected_row !== 2) {
                var tilerow = this.lower_row;
                this.upper_row.unselectAll();
                this.selected_row = 2;
            }
            if (this.active_row == 0 | this.selected_row == this.active_row) {
                var items = tilerow.getSelectedItems();
                if (items.length > 0) {
                    debug('tile selected');
                    if ($('TiletoPlaybutton')) {
                        dojo.removeClass( 'TiletoPlaybutton', 'disabled');
                    } else if ($('TiletoDiscardbutton')) {
                        dojo.removeClass( 'TiletoDiscardbutton', 'disabled');
                    }
                    if (this.checkAction('var_SelectTile', true) && this.selected_tile_id == 0) {
                        // PLAY CARD SELECTION
                        if ($('TiletoPlaybutton') == null) {
                            this.addActionButton( 'TiletoPlaybutton', _('Select Tile to Play'), 'onClickSelectTile','customActions' );
                        }
                        this.tile_selected = true;
                    } else if (this.checkAction('var_SelectTile', true) && this.selected_tile_id > 0) {
                        // DISCARD TILE SELECTION
                        if (items[0]['id'] == this.selected_tile_id) {
                            // you must select another tile than the previous one
                            this.showMessage( _("You must select another tile for discard"), "error" )
                            tilerow.unselectAll();
                        } else {
                            if ($('TiletoDiscardbutton') == null) {
                                this.addActionButton( 'TiletoDiscardbutton', _('Select Tile to Discard'), 'onClickSelectTile','customActions' );
                            }
                            this.tile_selected = true;
                        }
                    }
                    else {
                        tilerow.unselectAll();
                        debug('unselecting tiles')
                        if ($('TiletoPlaybutton') || $('TiletoDiscardbutton')) {
                            if (this.selected_tile_id == 0) {
                                debug('unselecting tile -> disabling button')
                                dojo.addClass( 'TiletoPlaybutton', 'disabled');
                            } else {
                                debug('unselecting tile -> disabling button')
                                dojo.addClass( 'TiletoDiscardbutton', 'disabled');
                            }
                        }
                        if (this.gamedatas.gamestate.name == "var_tileSelection") {
                            this.showMessage( _("This is not your turn."), "info" );
                        }
                    }
                } else {
                    debug('no tile selected');
                    if ($('TiletoPlaybutton')) {
                        dojo.addClass( 'TiletoPlaybutton', 'disabled');
                    } else if ($('TiletoDiscardbutton')) {
                        dojo.addClass( 'TiletoDiscardbutton', 'disabled');
                    }
                    this.tile_selected = false;
                }
            } else {
                // should never come to this (other row disabled beforehand)
                this.showMessage( _("You must select a tile in the active row"), "error" );
                tilerow.unselectAll();
            }
        },

        onClickSelectLastTile : function (s) {
            dojo.stopEvent(s);
            if (this.checkAction('selectShip', true) && this.tile_selected && this.lastTurn) {
                var sel_cards_list = this.playerHand.getSelectedItems();
                var unsel_card = this.playerHand.getUnselectedItems();
                this.ajaxcall( '/bigmonster/bigmonster/selectShip.html', { lock: true, 
                    ship_player_id : 0, // because we used a button -> not related to another player
                    rem_cards : unsel_card[0]['id'],
                    sel_card : sel_cards_list[0]['id']
                 }, this, function( result ) {
                    dojo.empty('customActions');
                 } );
                 this.buttonAdded = false;
            } else if (!this.tile_selected) {
                this.showMessage( _("You must select a tile in your hand first"), "error" )
            }
        },

        onClickSelectTile : function (s) {
            dojo.stopEvent(s);
            if (this.checkAction('var_SelectTile', true) && this.tile_selected) {
                var tilerow = ( this.selected_row == 1 ) ? this.upper_row : this.lower_row;
                var sel_cards_list = tilerow.getSelectedItems();
                var unsel_cards_list = tilerow.getUnselectedItems();
                if (unsel_cards_list.length == 1) {
                    sel_action = 3
                    unsel_cards = unsel_cards_list[0]['id'];
                } else if (this.selected_tile_id == 0) {
                    sel_action = 0
                    var unsel_cards = [];
                    for (var i in unsel_cards_list) {
                        unsel_cards.push(unsel_cards_list[i]['id'])
                    }
                } else {
                    sel_action = 1
                    var unsel_cards = [];
                    for (var i in unsel_cards_list) {
                        card_id = unsel_cards_list[i]['id']
                        if (unsel_cards_list[i]['id'] != this.selected_tile_id) {
                            unsel_cards.push(unsel_cards_list[i]['id'])
                        }
                    }
                }
                this.ajaxcall( '/bigmonster/bigmonster/var_selectTile.html', { lock: true, 
                    source_row : this.selected_row,
                    rem_cards : unsel_cards.toString(),
                    sel_card : sel_cards_list[0]['id'],
                    sel_action : sel_action
                 }, this, function( result ) {
                    dojo.empty('customActions');
                 } );
            } else if (!this.tile_selected) {
                this.showMessage( _("You must select a tile first"), "error" ); // should never be displayed as button is normally disabled
            }
        },

        onClickStartTile: function(s) {
            var t = parseInt(s.target.id.split("_")[1]);
            dojo.stopEvent(s);
            for (let i = 0; i < this.possible_explorers.length; i++) {
                const element = this.possible_explorers[i];
                if (element == t) {
                    dojo.addClass( 'tile_' + element, 'selected' );
                    dojo.addClass("conf_expl_btn", "ctile_"+t)
                } else {
                    dojo.removeClass( 'tile_' + element, 'selected' );
                    dojo.removeClass("conf_expl_btn", "ctile_"+element)
                }
            }
            dojo.addClass("conf_expl_btn", "bm_accept")
            dojo.removeClass("conf_expl_btn", "bm_accept-pending")
            if (!this.explo_selected) {
                dojo.query("#conf_expl_btn").connect("onclick", this, "onClickConfirmStartTile")
                this.explo_selected = true;
            }
        },


         onClickConfirmStartTile: function(s) {
            var st = s.target.classList["value"].replace('ctile_','').replace('bm_accept','');
            // send information of selected tile to server
            if( this.checkAction( 'selectStartingExplorer' ) )
            {
                this.ajaxcall( "/bigmonster/bigmonster/selectStartingExplorer.html", {
                    lock: true,
                    tile: st
                }, this, function( result ) {
                    dojo.empty('customActions');
                } );
            }
            // remove bm_popup
            dojo.destroy("bm_popup");
        },

        onClickPossibleMove: function(s){
            var pos = [s.target.dataset.posX, s.target.dataset.posY];
            debug(s.target.classList.value);
            if (s.target.classList.value.includes("selected_pos")) {
                if( this.checkAction('placeTile') )
                {
                    // Remove elements: button, and placement options
                    dojo.destroy("button_conf_move");
                    dojo.query('.possibleMoveH').forEach(function(t) {dojo.style(t,'display','none')});
                    dojo.query('.possibleMoveV').forEach(function(t) {dojo.style(t,'display','none')});
    
                    // Start countdown for confirmation of tile placement
                    if ($('button_conf_move') == null) {
                        // if no previous position selected, show the confirm button
                        this.addActionButton( 'button_conf_move', _('Place Tile'), 'onClickConfirmTilePositionButton','customActions' );
                    }
                    this.startActionTimer('button_conf_move', 3, 1);
                    // add a Cancel button
                    this.addActionButton( 'cancel_placement', _('Cancel'), 'onClickCancelTilePositionButton','customActions',false,'gray' );
                    dojo.place('cancel_placement', 'customActions', 'last');
                }
            } else {
                // reset opacity to default
                dojo.query('.possibleMoveV').removeClass('hidden_pos');
                dojo.query('.possibleMoveH').removeClass('hidden_pos');
                dojo.query('.possibleMoveV').removeClass('selected_pos');
                dojo.query('.possibleMoveH').removeClass('selected_pos');
                if (typeof this.playerHand != "undefined") {
                    // draft mode (where each player has a hand)
                    var tileNum = this.playerHand.items[0].type;
                    var tileId = this.playerHand.items[0].id;
                } else {
                    // variable mode (where there is common "hands")
                    let items = (this.active_row == 1) ? this.upper_row.items : this.lower_row.items;
                    var result = items.filter(obj => {
                        return toint(obj.id) == this.selected_tile_id;
                      });
                    var tileNum = result[0].type;
                    var tileId = result[0].id;
                }
                var updir = false;
                var ldir = false;
                for (const key in s.target.classList) {
                    if (s.target.classList[key] == "up") {
                        updir = true;
                    } else if (s.target.classList[key] == "left") {
                        ldir = true;
                    }
                }
                if ($('button_conf_move') == null) {
                    // if no previous position selected, show the confirm button
                    this.addActionButton( 'button_conf_move', _('Place Tile'), 'onClickConfirmTilePositionButton','customActions' );
                } else {
                    // if a position was already selected, remove the tile there -> wouldn't be beter to move it... ?
                    //dojo.destroy("tile_" + tileId);
                }
                // place tile on board
                this.dbMovepos = pos.slice(0);
                if (Math.floor(tileNum / 10 ) != 1) {
                    var x = pos[0]/4;
                    var y = (toint(pos[1]) ) /4;
                    var rot = 0;
                    if (updir) {
                        var y = (toint(pos[1]) - 1 )/4;
                        this.dbMovepos[1] -= 1; // adapt position to send to DB
                    }
                } else {
                    var x = (pos[0] / 200 + 50 * pos[0] + 25)/200;
                    var y = ((toint(pos[1]) / 200) + 50 * toint(pos[1]) - 25) / 200;
                    var rot = -90;
                    if (ldir) {
                        var x = ((pos[0] - 1) / 200 + 50 * (pos[0] - 1) + 25)/200;
                        this.dbMovepos[0] -= 1; // adapt position to send to DB
                    }
                }
                // set to transparent the clicked position
                var otile = '#move_'+toint(pos[0])+'*'+toint(pos[1]);
                this.current_move = 'move_'+toint(pos[0])+'*'+toint(pos[1])
                dojo.query(otile).addClass('selected_pos');
                //setTimeout(() => { dojo.query(otile).addClass('hidden_pos'); }, 600);
                if ($('tile_' + tileId)) {
                    if (rot != 0) {
                        this.boards[this.player_id].moveIdToPos(this, "tile_"+tileId, (x * 2 * this.SCALE)-25, (y *2* this.SCALE)+25, 0, 300)
                    } else {
                        this.boards[this.player_id].moveIdToPos(this, "tile_"+tileId, x*2 * this.SCALE, y * 2 * this.SCALE, 0, 300)
                    }
                } else {
                    this.placeTile(this.player_id, tileNum, tileId, x , y , rot, 0);
                }
                this.lastPossibleMoveIdx = pos;
            }


        },

        onClickConfirmTilePositionButton: function( evt )
        {
            dojo.stopEvent( evt );
            if( this.checkAction('placeTile') )
            {
                // Remove elements: button, and placement options
                if ($('cancel_placement') != null) {
                    dojo.destroy("cancel_placement");
                }
                dojo.destroy("button_conf_move");
                dojo.query('.possibleMoveH').forEach(dojo.destroy);
                dojo.query('.possibleMoveV').forEach(dojo.destroy);

                // Send placement option
                this.ajaxcall("/bigmonster/bigmonster/placeTile.html",
                            {   lock: true,
                                whichMove: this.dbMovepos.toString() },
                            this, function(){
                                dojo.empty('customActions');
                            });
            }
        },

        onClickCancelTilePositionButton: function( evt )
        {
            dojo.stopEvent( evt );
            dojo.destroy("button_conf_move");
            dojo.destroy("cancel_placement");
            // reset opacity to default
            dojo.query('.possibleMoveH').forEach(function(t) {dojo.style(t,'display','block')});
            dojo.query('.possibleMoveV').forEach(function(t) {dojo.style(t,'display','block')});
            dojo.query('.possibleMoveV').removeClass('hidden_pos');
            dojo.query('.possibleMoveH').removeClass('hidden_pos');
            dojo.query('.possibleMoveV').removeClass('selected_pos');
            dojo.query('.possibleMoveH').removeClass('selected_pos');
            if (typeof this.playerHand != "undefined") {
                // 4+ players mode
                var tileId = this.playerHand.items[0].id;
            } else {
                // 2-3 players mode
                let items = (this.active_row == 1) ? this.upper_row.items : this.lower_row.items;
                var result = items.filter(obj => {
                    return toint(obj.id) == this.selected_tile_id;
                  });
                var tileId = result[0].id;
            }
            dojo.destroy("tile_" + tileId);
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your bigmonster.game.php file.
        
        */
        setupNotifications: function()
        {
            
            dojo.subscribe( 'AskTeamSelection', this, "notif_AskTeamSelection" );
            dojo.subscribe( 'selectedExplorers', this, "notif_selectedExplorers" );
            dojo.subscribe( 'updateHand', this, "notif_updateHand" );
            dojo.subscribe( 'updateTileAvail', this, "notif_updateTileAvail" );
            dojo.subscribe( 'cardsOnShip', this, "notif_cardsOnShip" );
            this.notifqueue.setSynchronous( 'cardsOnShip', 500 );
            dojo.subscribe( 'SelectedTile', this, "notif_SelectedTile" );
            dojo.subscribe( 'muted_monster', this, "notif_muted_monster" );
            dojo.subscribe( 'playedTiles', this, "notif_playedTiles" );
            dojo.subscribe( 'ZombiePlayedTile', this, "notif_ZombiePlayedTile" );
            dojo.subscribe( 'wonMedal', this, "notif_wonMedal" );
            this.notifqueue.setSynchronous( 'wonMedal', 100 );
            dojo.subscribe( 'scoreUpdate', this, "notif_scoreUpdate" );
            dojo.subscribe( 'endGame_scoring', this, "notif_endGame_scoring" );
            let num_players = Object.keys(gameui.gamedatas.players).length;
            this.notifqueue.setSynchronous( 'endGame_scoring', 5000 * num_players + 3000 );
        },  

        notif_AskTeamSelection : function (notif) {
            debug(notif)
            debug('SELECT TEAM PLEASE !!');
        },
        
        setScoringArrowRow: function(stage) {
            dojo.query('#game-scoring .arrow').style('visibility', 'hidden');
            dojo.query('.arrow', $('scoring-row-'+stage)).style('visibility', 'visible');
         },
         
         setScoringRowText: function(stage, player_id, value) {
             if (stage =='team') {
                    $('scoring-row-' + stage + '-t' + player_id).innerHTML = value;
            } else {
                    $('scoring-row-' + stage + '-p' + player_id).innerHTML = value;
             }
           },
       
          setScoringRowWinner: function(player_id, teamid=0) {
            dojo.addClass($('scoring-row-name-p' + player_id), 'wavetext');
            
            let stages = ['ice', 'bigmonster', 'lava', 'grassland', 'swamp', 'diamonds', 'explorer', 'medal', 'total'];
            for (let j in stages) {
                let stage = stages[j];
                dojo.style($('scoring-row-'+stage+'-p' + player_id), {'backgroundColor': 'rgba(255, 215, 0, 0.3)'});
            }
            if (this.isTeamPlay) {
                dojo.style($('scoring-row-team-t' + teamid), {'backgroundColor': 'rgba(255, 215, 0, 0.3)'});
            }
           },
         
         notif_finalRound: function( notif ) {
            let playerId = notif.args.player_id;
            
            this.gamedatas.game_ending_player = playerId;
            dojo.style($('last-round'), { 'display': 'block' });
         },
         
         notif_endGame_scoring: function ( notif )
         {
            debug(notif);
            let breakdowns = notif.args.breakdowns;
            let winnerIds = [toint(notif.args.winner_ids)];
            if (this.isTeamPlay) {
                teamscores = notif.args.team_scores;
                teamwinId = notif.args.winning_team;
            }
            
            // show the final scoring table
            dojo.style($('game-scoring'), {'display': 'block'});
            dojo.style($('handmedal_area'), {'display': 'none'})
            if ($('ships')) {
                dojo.style($('ships'), {'display': 'none'})
            }
            
            let stages = ['ice', 'bigmonster', 'lava', 'grassland', 'swamp', 'diamonds', 'explorer', 'medal', 'total'];
            let currentTime = 0;
            for (let i in stages) {
                let stage = stages[i];
                let breakdownStage = stage;
                if (stage == 'total') {
                    breakdownStage = 'score';
                } else if (stage == 'diamonds') {
                    breakdownStage = 'diams';
                } else if (stage == 'explorer') {
                    breakdownStage = 'explo';
                } else if (stage == 'medal') {
                    breakdownStage = 'medals';
                }
                // Set arrow to here
                setTimeout(this.setScoringArrowRow.bind(this, stage), currentTime);
                if (this.isTeamPlay) {
                    let player_list = this.teams_ordered.flat()
                    for( let i in player_list ) {
                        player_id = player_list[i];
                        setTimeout(this.setScoringRowText.bind(this, stage, player_id, breakdowns[player_id][breakdownStage]), currentTime);
                        this.setScoringRowText.bind(stage, player_id, breakdowns[player_id][breakdownStage]);
                        currentTime += 500;
                    }
                } else {
                    for( let player_id in this.gamedatas.players ) {
                        setTimeout(this.setScoringRowText.bind(this, stage, player_id, breakdowns[player_id][breakdownStage]), currentTime);
                        this.setScoringRowText.bind(stage, player_id, breakdowns[player_id][breakdownStage]);
                        currentTime += 500;
                     }
                }
            }
            if (this.isTeamPlay) {
                for (let teamid in this.teams_values) {
                    debug(teamid, teamscores[teamid])
                    setTimeout(this.setScoringRowText.bind(this, 'team', teamid, teamscores[teamid]), currentTime);
                    this.setScoringRowText.bind('team', teamid, teamscores[teamid]);
                    currentTime += 500;
                }
            }
            
            // Set winner to be animated!
            currentTime -= 250;
            if (this.isTeamPlay) {
                for (let i in teamwinId) {
                    teamid = teamwinId[i];
                    for (let j in this.teams_ordered[teamid]) {
                        player_id = this.teams_ordered[teamid][j];
                        debug(player_id)
                        setTimeout(this.setScoringRowWinner.bind(this, toint(player_id),teamid), currentTime);
                    }
                }
            } else {
                setTimeout(this.setScoringRowWinner.bind(this, winnerIds), currentTime);
            }
         },

        notif_selectedExplorers : function (notif)
        {
            var s = notif.args
            this.explorers[s.player_id] = {'player_id': s.player_id, 'explorer_id': s.explorer_id}
            if (s.player_id == this.player_id) {
                this.explorer_id = s.explorer_id;
            }
            this.placeTile(s.player_id, s.explorer_id,s.explorer_id, 0,0,0, 1,0);
            dojo.query('#tile_e_' + s.explorer_id).connect('onclick', this, 'onClickExplo');

        },

        notif_updateHand : function (notif)
        {
            var s = notif.args;
            var cards = s.cards;
            var delay = 10;
            var duration = 1000;
            var turn_n = s.turn - 1;
            if (s.event == "newTurn") {
                this.gamedatas.playerorder.forEach(player_id => {
                    if (toint(player_id) == this.player_id) {
                        this.slideToObjectAndDestroy( "tileOnShip_" + player_id + "_" + turn_n, "myhand", duration, delay );
                    } else {
                        this.slideToObjectAndDestroy( "tileOnShip_" + player_id + "_" + turn_n, "overall_player_board_" + player_id, duration, delay );
                    }
                });
            }
            for (var i in cards) {
                var card = cards[i];
                var type = toint(card.type);
                var kind_monster = toint(card.type_arg);
                this.playerHand.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                this.setTileToolTip(card.id, type, kind_monster);
            }
            if (cards.length == 2) {
                this.lastTurn = true;
            } else {
                this.lastTurn = false;
            }
        },

        notif_updateTileAvail : function (notif) {
            var s = notif.args;
            var cards = s.cards;
            if (toint(s.updated_row) > 0) {
                var tilerow = (toint(s.updated_row) == 1) ? this.upper_row : this.lower_row;
                var rowname = (toint(s.updated_row) == 1) ? 'upper_row' : 'lower_row';
                for (var i in cards) {
                    var card = cards[i];
                    var type = card.type;
                    var kind_monster = card.type_arg;
                    tilerow.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                    this.setTileToolTip(card.id, type, kind_monster, rowname);
                }
            } else {
                for (var loc of ['upper', 'lower']) {
                    loc_cards = cards[loc];
                    var tilerow = (loc == 'upper') ? this.upper_row : this.lower_row;
                    for (var i in loc_cards) {
                        var card = loc_cards[i];
                        var type = card.type;
                        var kind_monster = card.type_arg;
                        tilerow.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                        this.setTileToolTip(card.id, type, kind_monster, loc_cards+'_row');
                    }
                }
                // update the number of cards remaing (extra from new turn since 2 rows are updated here)
                if (toint($('card_counter').innerHTML) > 0) {
                    dojo.query('#card_counter').innerHTML(toint($('card_counter').innerHTML)-1);
                }
            }
        },

        notif_cardsOnShip : function (notif)
        {
            var s = notif.args
            if (this.player_id == s.player_id) {
                // process if we are the player that made the action
                // move the unselected cards to the ship of other player
                var unsel_cards_list = this.playerHand.getUnselectedItems();
                var first_card = true;
                for (const key in unsel_cards_list) {
                    if (Object.hasOwnProperty.call(unsel_cards_list, key)) {
                        const element = unsel_cards_list[key];
                        //flip card
                        dojo.style("myhand_item_" + element['id'], "transform-style", "preserve-3d")
                        dojo.style("myhand_item_" + element['id'], "transition", "transform 0.8s ease")
                        dojo.addClass("myhand_item_" + element['id'], "bm_tileClass")
                        dojo.addClass("myhand_item_" + element['id'], "backtile")
                        if (!this.lastTurn) {
                            // move card to ship
                            var anim = this.slideToObject( "myhand_item_" + element['id'], "ship_" + s.player_ship_id);
                            if (first_card) {
                                dojo.connect(anim, 'onEnd', dojo.hitch(this, 'cardOnShipAnimEnded', s.player_ship_id, s.turn));
                                first_card = false;
                            }
                            anim.play();
                        } else {
                            // don't move the card (will be destroyed when removed from stock)
                        }
                        // remove moved cards from player's hand
                        this.playerHand.removeFromStockById( element['id'] );
                        // unselect remaining cards on hand
                        this.playerHand.unselectAll();
                        // change the clickable mouse aspect on ship tiles
                        this.desactivateShips();
                    }
                }
                // update the cards on hand
            } else if(toint(s.player_ship_id) > 0) {
                // make a move animation of cards from player panel to ship
                dojo.place( "<div id='tileOnShip_"+s.player_ship_id+"_"+s.turn+"' class='bm_tileClass backtile' style='position: absolute;'></div>", "overall_player_board_"+s.player_id, "first" );
                this.slideToObjectRelative( "tileOnShip_"+s.player_ship_id+"_"+s.turn, "ship_"+s.player_ship_id, 1000, 1000, 'last' );
                //this.slideToObject( "tileOnShip_"+s.player_ship_id, "ship_"+s.player_ship_id ).play();
                // remove the callable mouse pointer of the ship where tile was moved to + add the ship to the list of busyships
                dojo.removeClass("ship_"+s.player_ship_id, 'bm_selectable')
                this.busyShips.push(toint(s.player_ship_id));
                // add back the buttons on the top bar
            }
            var items = this.playerHand.getSelectedItems();
            if (items.length > 0) {
                // the player has card selected when notif is received
                this.desactivateShips();
                if (this.lastTurn) {
                    if ($('SelConfbutton') == null) {
                        this.addActionButton( 'SelConfbutton', _('Select Tile'), 'onClickSelectLastTile','customActions' );
                    } else {
                        dojo.removeClass( 'SelConfbutton', 'disabled');
                    }
                } else {
                    setTimeout(this.activateShips(),5000);
                }
            }

            
        },

        notif_ReplayTileSelected : function (notif) {
            var s = notif.args
            if (isReadOnly()) {
                this.playerHand.selectItem( s.card_id );
            }
        },

        notif_SelectedTile : function (notif) {
            s = notif.args;
            if (s.row == 'upper') {
                var tilerow = this.upper_row;
                this.active_row = 1;
            } else {
                var tilerow = this.lower_row;
                this.active_row = 2; 
            }
            if (toint(s.action) == 0) {
                // card selected to be played
                dojo.addClass(s.row + '_row_item_' + s.card_id , 'selected');
                this.selected_tile_id = toint(s.card_id);
                this.selected_tile_type = s.monster_kind ;
                tilerow.unselectAll();
                this.tile_selected = false;
                this.changePageTitle("discard");
            } else if (toint(s.action) == 1) {
                // card selected for discard
                tilerow.removeFromStockById( s.card_id );
                tilerow.unselectAll();
                this.tile_selected = false;
            } else if (toint(s.action) == 3) {
                // card selected to play and remaining to discard
                dojo.addClass(s.row + '_row_item_' + s.card_id , 'selected');
                this.selected_tile_id = toint(s.card_id);
                this.selected_tile_type = s.monster_kind;
                tilerow.unselectAll();
                tilerow.removeFromStockById( s.discard_card_id );
                tilerow.unselectAll();
                this.tile_selected = false;
            }
            // disable tiles of other row
            if (s.row == 'upper' ) {
                var id_list = this.lower_row.getAllItems();
                var rowname = "lower_row";
                this.lower_row.setSelectionMode( 0 )
            } else {
                var id_list = this.upper_row.getAllItems();
                var rowname = "upper_row";
                this.upper_row.setSelectionMode( 0 )
            }
            id_list.forEach(element => {
                dojo.addClass(rowname+'_item_'+element['id'], 'disabled');
            });
        },

        notif_muted_monster : function (notif)
        {
            var s = notif.args
            if (notif.bIsTableMsg) {
                // public notification
                for (const element in s) {
                    if (element != "player_name") {
                        if (s[element].player_id != this.player_id) {
                            kind_monster = s[element].kind;
                            type = 1; // ice monster
                            tileNum = (toint(type) - 1) * 10 + toint(kind_monster) - 1;
                            const [x, y, rot] = this.convert_coord(s[element].x , s[element].y, type);
                            dojo.destroy("tile_"+s[element].card_id);
                            this.placeTile(s[element].player_id, tileNum, s[element].card_id, x , y , rot, 0, toint(s[element].mutation_level));
                        }
                    }
                }
            } else {
                s.forEach(element => {
                    kind_monster = element.kind;
                    type = 1; // ice monster
                    tileNum = (toint(type) - 1) * 10 + toint(kind_monster) - 1;
                    const [x, y, rot] = this.convert_coord(element.x , element.y, type);
                    dojo.destroy("tile_"+element.card_id);
                    this.placeTile(element.player_id, tileNum, element.card_id, x , y , rot, 0, toint(element.mutation_level));
                });
            }


        },

        notif_playedTiles : function (notif)
        {
            var s = notif.args
            // skip when it's the player's board
            if (this.player_id != s.player_id || g_archive_mode || typeof g_replayFrom != 'undefined') {
                // tile played by other player -> add it to its board
                kind_monster = s.kind_monster;
                type = s.type_monster;
                tileNum = (toint(type) - 1) * 10 + toint(kind_monster) - 1;
                const [x, y, rot] = this.convert_coord(s.x , s.y, type);
                // move tile from hand to board
                // create a phantom block to move the tile to
                phantomId = 'phantomplace_'+s.x+'*'+s.y;
                var html = '<div id="'+phantomId+'" class="phantomplace" style="'+
                    'top:'+(s.y*this.SCALE/2)+'px; left:'+(x*this.SCALE*2)+'px; width:50px; height:50px; position:absolute'+
                    '"></div>';
                debug('created the html snippet : '+html);
                this.boards[s.player_id].addHtml(html,0);
                debug('added the html snippet to the board');
                if (this.nums_of_players < 4 &&  !this.is3pdraft) {
                    let rowname = (this.active_row == 1) ? 'upper_row' : 'lower_row';
                    tileId = rowname+"_item_"+s.card_id;
                    clone_tile = $(tileId).cloneNode()
                    clone_tile.id = 'clone_'+tileId;
                    tileId = 'clone_'+tileId;
                    dojo.place(clone_tile,rowname); //upper_row_item_97
                } else {
                    let tmptileDiv = this.format_block('jstpl_tmp_tile', {
                        tile_id : s.card_id,
                        back_x: toint(kind_monster)*100,
                        back_y: toint(type)*100,
                        rot: rot
                    });
                    dojo.place( tmptileDiv, "overall_player_board_"+s.player_id, "first" );
                    tileId = "tmp_tile_"+s.card_id;
                }
                this.slideToObjectAndDestroy( tileId , phantomId, 1000, 0 );
                debug('slided the tile to the phantom place');
                setInterval(dojo.destroy("phantomplace_"+s.x+"*"+s.y), 1100);
                debug('destroyed the phantom place');
                if (toint(s.mutation) > 0) {
                    dojo.destroy("tile_"+s.card_id);
                }
                setInterval(this.placeTile(s.player_id, tileNum, s.card_id, x , y , rot, 0, toint(s.mutation_level) ),990);
                if (this.nums_of_players < 4 &&  !this.is3pdraft) {
                    let tilerow = (this.active_row == 1) ? this.upper_row : this.lower_row;
                    debug('update the tile row');
                    tilerow.removeFromStockById( s.card_id );
                    setInterval(tilerow.updateDisplay(),200);
                }
            } else {
                // tile played by player -> remove from hand
                if (this.nums_of_players < 4 &&  !this.is3pdraft) {
                    let tilerow = (this.active_row == 1) ? this.upper_row : this.lower_row;
                    tilerow.removeFromStockById( s.card_id );
                } else {
                    this.playerHand.removeFromStockById( s.card_id );
                }
            }

        },

        notif_ZombiePlayedTile : function (notif) {
            let tilerow = (this.active_row == 1) ? this.upper_row : this.lower_row;
            tilerow.removeFromStockById( notif.args.sel_card );
        },

        notif_wonMedal : function (notif) {
            var s = notif.args;
            var delay = 10;
            var duration = 3000;
            // move medal from medal area to the player board
            let miaDiv = this.format_block('jstpl_player_board_medal_innerzone', {
                player_id : s.player_id,
                medal_id : s.medal_id
            });
            if (this.player_id == s.player_id) {
                var target = $('help-icon')
                var position = 'before'
            } else {
                var target = $('ma_'+s.player_id)
                var position = 'last'
            }
            dojo.place( miaDiv, target, position );
            medal_team_id = (s.medal_id > 10) ? 2 : 1;
            if (!this.medals_status[s.medal_id]) {
                this.slideToObjectRelative( "medal_" + s.medal_id + "_" + medal_team_id, "mia_" + s.player_id+"_"+s.medal_id, duration, delay, 'first', this.addBackMedal(s.medal_id, s.back_id, s.player_id, duration+delay) );
                this.medals_status[s.medal_id] = true;
            } else {
                let info_id = toint((s.medal_id>10) ? Math.floor(s.medal_id/10):s.medal_id);
                let tmp_medal = this.format_block('jstpl_front_medal', {
                    medal_id : s.medal_id,
                    data_id: info_id,
                    type : medal_team_id,
                    player_id : s.player_id,
                });
                let mia_div = 'mia_'+s.player_id+'_'+s.medal_id;
                this.slideTemporaryObject( tmp_medal , mia_div, 'medals', mia_div, duration, delay );
                setTimeout(() => {
                    dojo.place(tmp_medal, mia_div, 'first')},duration+delay);
                setTimeout(() => { 
                    dojo.place( this.format_block('jstpl_back_medal', {medal_id : s.medal_id, back_id : s.back_id} ), mia_div );
                }, duration+delay+10);
            }
            if (this.isTeamPlay && s.medal_id <= 10) {
                // top medal is won -> add the bottom_only class to the medal group
                dojo.addClass( 'group_'+s.medal_id, 'bottom_only' );

            }
        },

        notif_scoreUpdate : function (notif) {
            var s = notif.args;
            if (!this.hidescore) {
                this.scoreCtrl[ s.player_id ].incValue( s.score_delta );
            }
        },



   });             
});
