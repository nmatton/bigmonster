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
            this.tiles_img_path = 'img/monster_tiles_stock.jpg'; // tile image path for stock component
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
        
        setup: function( gamedatas )
        {
            // SETUP SCROLL AREAS
            this.currentPlayer = gamedatas.currentPlayer;
            this.nums_of_players = Object.keys(gamedatas.players).length;
            this.boards = [];
            if (this.isSpectator) {
                for (var t of  Object.keys(gamedatas.players)) {
                    this.boards[t] = new Scroller(ebg.scrollmap(),t);
                }
                for (var o =  Object.keys(gamedatas.players).length - 1; o >= 0; o--)
                    dojo.place( Object.keys(gamedatas.players)[o] + "_scrollmap", "Boards", "after");
                for (o =  Object.keys(gamedatas.players).length - 1; o >= 0; o--) {
                    dojo.place( Object.keys(gamedatas.players)[o] + "_scrollmap", "Boards", "after");
                    if ( Object.keys(gamedatas.players)[o] == this.currentPlayer)
                        break
                }
            } else {
                for (var t of gamedatas.playerorder) {
                    this.boards[t] = new Scroller(ebg.scrollmap(),t);
                }
                for (var o = gamedatas.playerorder.length - 1; o >= 0; o--)
                    dojo.place(gamedatas.playerorder[o] + "_scrollmap", "Boards", "after");
                for (o = gamedatas.playerorder.length - 1; o >= 0; o--) {
                    dojo.place(gamedatas.playerorder[o] + "_scrollmap", "Boards", "after");
                    if (gamedatas.playerorder[o] == this.currentPlayer)
                        break
                }
            }
            for (var t of Object.keys(gamedatas.players)) {
                if (this.boards.includes(t)) {
                    this.boards[t].scrollTo(-this.SCALE / 2, -this.SCALE / 2)
                }
            }           
            // **** PLAYERS BOARDS SETUP **** //

            if (this.isReadOnly() && typeof g_replayFrom == 'undefined' && !g_archive_mode) {
                dojo.destroy('myhand_wrap');
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
            }
            if (this.nums_of_players >= 4) {
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

            /** SCORING BOARD SETUP **/

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

            // **** TILES AND HAND MANAGEMENT **** //
            if (!this.isSpectator) {
                // ** Create hands of tiles ** //
                if (this.nums_of_players >= 4) {
                    // remove board title
                    dojo.destroy('bm_title_board');
                    // remove pile card count (the rem cards is visible in hand)
                    dojo.destroy('card_left_count');
                    // remove "gridded" class
                    dojo.query('#myhand_wrap').removeClass('bm_gridded')
                    // ADD A COUNTER OF ROUND : TODO !
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
                        var type = card.type;
                        var kind_monster = card.type_arg;
                        this.playerHand.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
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
                    dojo.place( "<div id='lower_row' class='whiteblock bm_tileArea'></div>" , $('myhand_wrap'));
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
                        } else if (toint(row) == 2) {
                            this.lower_row.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                        } else {
                            // row is equal to player ID => selected card
                            if (toint(this.active_row) == 1) {
                                var tilerow = this.upper_row;
                                var rowname = 'upper';
                            } else {
                                var tilerow = this.lower_row;
                                var rowname = 'lower';
                            }
                            tilerow.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                            dojo.addClass(rowname + '_row_item_'+card.id , 'selected');
                            dojo.addClass(rowname + '_row_item_'+card.id , 'disabled');
                            this.selected_tile_id = toint(card.id);
                            this.changePageTitle('discard');
                            this.selected_tile_type = card.type;
                            // disable the other row
                        }
                    }
                    // bottom row
                    for ( var i in this.gamedatas.tiles_lower_row) {
                        var card = this.gamedatas.tiles_lower_row[i];
                        var type = card.type;
                        var kind_monster = card.type_arg;
                        this.lower_row.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
                    }
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

            // insert medals
            for (const i in this.gamedatas.medals) {
                if (Object.hasOwnProperty.call(this.gamedatas.medals, i)) {
                    const medal = this.gamedatas.medals[i];
                    medal_id = toint(medal['medal_id']);
                    location_id = medal['player_id'];
                    back_id = medal['back_id'];
                    if (location_id == 0) {
                        // add the medal on stock of medals
                        let cardDiv = this.format_block('jstpl_medal_player_stock', {
                            medal_id : medal_id,
                            type : this.game_mode,
                            back_id : back_id
                        });
                        medal_area = $('medals')
                        dojo.place( cardDiv , medal_area);
                        // add the back of the tile
                        //var medal_wrap_div = $('medals_wrap');
                        //dojo.place( this.format_block('jstpl_back_medal', {medal_id : medal_id, back_id : back_id} ), medal_wrap_div );
                    } else {
                        // add the medal to the player area
                        location_id_list = location_id.split(',');
                        location_id_list.forEach(element => {
                            let player_id = toint(element);
                            var player_medal_zone_div = $('ma_'+player_id);
                            dojo.place( this.format_block('jstpl_medal_player_area',{
                                medal_id : medal_id,
                                player_id : player_id,
                                type : this.game_mode,
                                back_id : back_id} ), player_medal_zone_div );
                        });
                    }
                }
            }

            if (this.nums_of_players < 4 && $('ma_'+this.gamedatas.first_player) && this.gamedatas.first_player != 0) {
                // insert first player medal
                var player_medal_zone_div = $('ma_'+this.gamedatas.first_player);
                dojo.place( this.format_block('jstpl_first_player_medal',{}), player_medal_zone_div, 'first');
                this.addTooltip( 'firstplayermedal', _('first player'), '', 10 )
            }

            // ** HELP AND OPTION ** //

            if (this.isSpectator) {
                dojo.place(jstpl_helpIcon, document.querySelector('.player-board.spectator-mode'));
                dojo.query('.player-board.spectator-mode .roundedbox_main').style('display', 'none');
            } else {
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
            //console.log('entering stage' + stateName);
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
            case 'explorerSelection':
                explorers = args.args['_private'];
                if (!this.isReadOnly() || g_archive_mode) {
                    if (explorers.length == 2 || g_archive_mode) {
                        // only process when choice is still possible (if length < 2, it means that the player already made its choice)
                        popupcontent = '<div id="bm_popup" class ="bm_popin"><h2 id="popin_playersHelp_title" class="bm_popin_title">'+_('Select a starting explorer')+'</h2><div id="selectingExploDiv">'
                        for (var i = 0; i < explorers.length; i++) {
                            popupcontent += '<div id="explo' + i + '">'
                            var explo_id = explorers[i]['explorer_id'];
                            var explo_info = explorers[i]['explorer_info'];
                            popupcontent += this.tileHtml(explo_id, explo_id, 0, 0, 1, 1, 0);
                            popupcontent += '<p id="exploInfo"> ' + explo_info + '</p></div>'
                        }
                        popupcontent += '</div><button id="conf_expl_btn" class="bm_accept-pending">Confirm selection</button></div>'
                        dojo.place(popupcontent, "myhand_wrap", "before");
                        for (var i = 0; i < explorers.length; i++) {
                            var explo_id = explorers[i]['explorer_id'];
                            dojo.query("#tile_" + explo_id).connect("onclick", this, "onClickStartTile")
                            this.possible_explorers.push(explo_id);
                        }
                    }
                }
                break;
            
            case 'tileSelection':
                if (args.args[this.player_id] == 2) {
                    this.lastTurn = true;
                    this.changePageTitle('lasttile');
                }
                console.log($('bm_popup'))
                if ($('bm_popup')) {
                    // remove explo selection popup (that remains on replay mode)
                    console.log('removing explo popup')
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
                    dojo.query('#card_counter').innerHTML(toint($('card_counter').innerHTML)-1)
                    this.new_turn=false;
                }
                if ($('bm_popup')) {
                    // remove explo selection popup (that remains on replay mode)
                    console.log('removing explo popup')
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
                            this.addActionButton( 'button_conf_move', _('Place Tile'), 'onClickConfirmTilePositionButton' );
                        }
                        break
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        onScreenWidthChange() {
            dojo.style('page-content', 'zoom', '');
            dojo.style('page-title', 'zoom', '');
            dojo.style('right-side-first-part', 'zoom', '');
            // recentering play areras
            if (typeof this.boards !== 'undefined') {
                if ($('MainBoardArea').offsetWidth < 650) {
                    dojo.query('.scrollerClass').style('width','90%')
                } else {
                    dojo.query('.scrollerClass').style('width','46%')
                }
                for (var t of Object.keys(this.gamedatas.players)) {
                    this.boards[t].scrollTo(-this.SCALE / 2, -this.SCALE / 2)
                }
            }
        },

        toggleMedalFace() {
            dojo.query('.medal_area_inner').toggleClass('flipped')
        },

        isReadOnly() {
            return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
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
            return '<div id="tile_' + id + '" class="'+ iclass +'" style=' + (c += "background-position: -" + 100 * r + "% -" + 100 * a + '%;"') + ">" + n + "</div>"
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
                this.ajaxcall( '/bigmonster/bigmonster/selectShip.html', { lock: true, 
                    ship_player_id : t,
                    rem_cards : unsel_cards.toString(),
                    sel_card : sel_cards_list[0]['id']
                 }, this, function( result ) {} );
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
                    var pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                    var ximg = sel_tile.style.backgroundPositionX;
                    var yimg = sel_tile.style.backgroundPositionY;
                    dojo.addClass(evt.target, 'bm_tileClass');
                    dojo.style(evt.target, 'backgroundPositionX', toint(ximg)/2+'px' );
                    dojo.style(evt.target, 'backgroundPositionY', toint(yimg)+'px');
                    var otile = '#move_'+toint(pos[0])+'*'+toint(toint(pos[1])+1);
                    dojo.query(otile).addClass('hidden_pos')
                 });
            } else if (d == "U") {
                // expansion to "up" (need to change position)
                dojo.addClass($( 'move_'+x+'*'+y+''), 'up');
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    var pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                    dojo.style(evt.target, 'top', toint(pos[1]) * 100/2 - 50 + 'px'); // set new pos for upper expansion
                    var ximg = sel_tile.style.backgroundPositionX;
                    var yimg = sel_tile.style.backgroundPositionY;
                    dojo.addClass(evt.target, 'bm_tileClass');
                    dojo.style(evt.target, 'backgroundPositionX', toint(ximg)/2+'px' );
                    dojo.style(evt.target, 'backgroundPositionY', toint(yimg)+'px');
                 });
            } else if (d == "R") {
                // expansion to right
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    var ximg = sel_tile.style.backgroundPositionX;
                    if (toint(ximg) == -100) {
                        dojo.addClass(evt.target, 'bm_tileClassH1');
                    } else {
                        dojo.addClass(evt.target, 'bm_tileClassH2');
                    }
                    
                 });
            } else if (d == "L") {
                // expansion to left
                dojo.addClass($( 'move_'+x+'*'+y+''), 'left');
                dojo.connect( $( 'move_'+x+'*'+y+''), 'mouseenter', function(evt) {
                    var pos = [evt.target.dataset.posX, evt.target.dataset.posY];
                    dojo.style(evt.target, 'left', toint(pos[0]) * 100/2 - 50 + 'px'); // set new pos for left expansion
                    var ximg = sel_tile.style.backgroundPositionX;
                    if (toint(ximg) == -100) {
                        dojo.addClass(evt.target, 'bm_tileClassH1');
                    } else {
                        dojo.addClass(evt.target, 'bm_tileClassH2');
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
                        this.sendCardToShip(t),
                    );
                    // activate the explorer
                    dojo.addClass('tile_e_' + this.explorers[t]['explorer_id'], 'bm_selectable');
                }
            })
        },

        desactivateShips : function() {
            this.removeActionButtons();
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

        coloredPlayerName(name) {
            const player = Object.values(this.gamedatas.players).find((player) => player.name == name);
            if (player == undefined) return '<!--PNS--><span class="playername">' + name + '</span><!--PNE-->';
      
            const color = player.color;
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
        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */


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
                            this.addActionButton( 'SelConfbutton', _('Select Tile'), 'onClickSelectLastTile' );
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
                    if (this.checkAction('var_SelectTile', true) && this.selected_tile_id == 0) {
                        // PLAY CARD SELECTION
                        if ($('TiletoPlaybutton') == null) {
                            this.addActionButton( 'TiletoPlaybutton', _('Select Tile to Play'), 'onClickSelectTile' );
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
                                this.addActionButton( 'TiletoDiscardbutton', _('Select Tile to Discard'), 'onClickSelectTile' );
                            }
                            this.tile_selected = true;
                        }
                    }
                    else {
                        tilerow.unselectAll();
                        if ($('TiletoPlaybutton') || $('TiletoDiscardbutton')) {
                            if (this.selected_tile_id == 0) {
                                dojo.toggleClass( 'TiletoPlaybutton', 'disabled');
                            } else {
                                dojo.toggleClass( 'TiletoDiscardbutton', 'disabled');
                            }
                        }
                        if (this.gamedatas.gamestate.name == "var_tileSelection") {
                            this.showMessage( _("This is not your turn."), "info" );
                        }
                    }
                } else {
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
                 }, this, function( result ) {} );
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
                 }, this, function( result ) {} );
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
                }, this, function( result ) {} );
            }
            // remove bm_popup
            dojo.destroy("bm_popup");
        },

        onClickPossibleMove: function(s){
            var pos = [s.target.dataset.posX, s.target.dataset.posY];
            // reset opacity to default
            dojo.query('.possibleMoveV').removeClass('hidden_pos');
            dojo.query('.possibleMoveH').removeClass('hidden_pos');
            if (typeof this.playerHand != "undefined") {
                // 4+ players mode
                var tileNum = this.playerHand.items[0].type;
                var tileId = this.playerHand.items[0].id;
            } else {
                // 2-3 players mode
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
                this.addActionButton( 'button_conf_move', _('Place Tile'), 'onClickConfirmTilePositionButton' );
            } else {
                // if a position was already selected, remove the tile there -> wouldn't be beter to move it... ?
                dojo.destroy("tile_" + tileId);
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
            setTimeout(() => { dojo.query(otile).addClass('hidden_pos'); }, 600);
            this.placeTile(this.player_id, tileNum, tileId, x , y , rot, 0);            
            this.lastPossibleMoveIdx = pos;


        },

        onClickConfirmTilePositionButton: function( evt )
        {
            dojo.stopEvent( evt );
            if( this.checkAction('placeTile') )
            {
                // Remove elements: button, and placement options
                dojo.destroy("button_conf_move");
                dojo.query('.possibleMoveH').forEach(dojo.destroy);
                dojo.query('.possibleMoveV').forEach(dojo.destroy);

                // Send placement option
                this.ajaxcall("/bigmonster/bigmonster/placeTile.html",
                            {   lock: true,
                                whichMove: this.dbMovepos.toString() },
                            this, function(){});
            }
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
            console.log(notif)
            console.log('SELECT TEAM PLEASE !!');
        },
        
        setScoringArrowRow: function(stage) {
            dojo.query('#game-scoring .arrow').style('visibility', 'hidden');
            dojo.query('.arrow', $('scoring-row-'+stage)).style('visibility', 'visible');
         },
         
         setScoringRowText: function(stage, player_id, value) {
              $('scoring-row-' + stage + '-p' + player_id).innerHTML = value;
           },
       
          setScoringRowWinner: function(winner_ids) {
             for (let i in winner_ids) {
                let player_id = winner_ids[i];
                dojo.addClass($('scoring-row-name-p' + player_id), 'wavetext');
                
                let stages = ['ice', 'bigmonster', 'lava', 'grassland', 'swamp', 'diamonds', 'explorer', 'medal', 'total'];
                for (let j in stages) {
                   let stage = stages[j];
                   dojo.style($('scoring-row-'+stage+'-p' + player_id), {'backgroundColor': 'rgba(255, 215, 0, 0.3)'});
                }
             }
           },
         
         notif_finalRound: function( notif ) {
            let playerId = notif.args.player_id;
            
            this.gamedatas.game_ending_player = playerId;
            dojo.style($('last-round'), { 'display': 'block' });
         },
         
         notif_endGame_scoring: function ( notif )
         {
            let breakdowns = notif.args.breakdowns;
            let winnerIds = [toint(notif.args.winner_ids)];
            // show the final scoring table
            dojo.style($('game-scoring'), {'display': 'block'});
            dojo.style($('myhand_wrap'), {'display': 'none'})
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
               for( let player_id in this.gamedatas.players ) {
                  // timer version
                  setTimeout(this.setScoringRowText.bind(this, stage, player_id, breakdowns[player_id][breakdownStage]), currentTime);
                  this.setScoringRowText.bind(stage, player_id, breakdowns[player_id][breakdownStage]);
                  currentTime += 500;
               }
            }
            
            // Set winner to be animated!
            currentTime -= 250;
            // timer version
            setTimeout(this.setScoringRowWinner.bind(this, winnerIds), currentTime);
            //this.setScoringRowWinner(winnerIds);
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
                var type = card.type;
                var kind_monster = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
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
                for (var i in cards) {
                    var card = cards[i];
                    var type = card.type;
                    var kind_monster = card.type_arg;
                    tilerow.addToStockWithId(this.getCardUniqueId(type, kind_monster), card.id);
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
            }
            var items = this.playerHand.getSelectedItems();
            if (items.length > 0) {
                // the player has card selected when notif is received
                this.desactivateShips();
                if (this.lastTurn) {
                    if ($('SelConfbutton') == null) {
                        this.addActionButton( 'SelConfbutton', _('Select Tile'), 'onClickSelectLastTile' );
                    } else {
                        dojo.removeClass( 'SelConfbutton', 'disabled');
                    }
                } else {
                    this.activateShips();
                }
            }

            
        },

        notif_ReplayTileSelected : function (notif) {
            var s = notif.args
            console.log(s);
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
                if (toint(s.mutation) > 0) {
                    dojo.destroy("tile_"+s.card_id);
                }
                this.placeTile(s.player_id, tileNum, s.card_id, x , y , rot, 0, toint(s.mutation_level) );
                if (this.nums_of_players < 4) {
                    let tilerow = (this.active_row == 1) ? this.upper_row : this.lower_row;
                    tilerow.removeFromStockById( s.card_id );
                }
            } else {
                // tile played by player -> remove from hand
                if (this.nums_of_players < 4) {
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
            this.slideToObjectRelative( "medal_" + s.medal_id + "_" + this.game_mode, "mia_" + s.player_id+"_"+s.medal_id, duration, delay, 'first', this.addBackMedal(s.medal_id, s.back_id, s.player_id, duration+delay) );
        },

        notif_scoreUpdate : function (notif) {
            var s = notif.args;
            this.scoreCtrl[ s.player_id ].incValue( s.score_delta );
        }


   });             
});
