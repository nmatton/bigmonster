{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BigMonster implementation : © Nicolas Matton (nicolas@locla.be)
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    bigmonster_bigmonster.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->
<div id='stars1' class="stars"></div>
<div id='stars2' class="stars"></div>
<div id="game-scoring" class="whiteblock">
  <table id='scoretable'>
    <tr id="scoring-row-players" class="line-below">
      <td class="first-column"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user-alt" width="50px" height="50px" class="svg-inline--fa fa-user-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 288c79.5 0 144-64.5 144-144S335.5 0 256 0 112 64.5 112 144s64.5 144 144 144zm128 32h-55.1c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16H128C57.3 320 0 377.3 0 448v16c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48v-16c0-70.7-57.3-128-128-128z"></path></svg></td>
    </tr>
    <tr id="scoring-row-ice" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-ice-icon" class="icon icon-ice"></i></td>
    </tr>
    <tr id="scoring-row-bigmonster" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-bigmonster-icon" class="icon icon-bigmonster"></i></td>
    </tr>
    <tr id="scoring-row-lava" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-lava-icon" class="icon icon-lava"></i></td>
    </tr>
    <tr id="scoring-row-grassland" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-grassland" class="icon icon-grassland"></i></td>
    </tr>
    <tr id="scoring-row-swamp" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-swamp" class="icon icon-swamp"></i></td>
    </tr>
    <tr id="scoring-row-diamonds" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-diamonds" class="icon icon-diamonds"></i></td>
    </tr>
    <tr id="scoring-row-explorer" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-explorer" class="icon icon-explorer"></i></td>
    </tr>
    <tr id="scoring-row-medal" class="line-below">
      <td class="first-column"><span class="arrow">→</span><i id="scoring-medal" class="icon icon-medal"></i></td>
    </tr>
    <tr id="scoring-row-total">
      <td id="text-total" class="first-column"><svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" viewBox="0 0 234 278">
        <path id="path62" fill="black" stroke="black" stroke-width="1" d="M 217.29,66.72
                 C 226.52,66.72 234.00,59.25 234.00,50.04
                   234.00,50.04 234.00,16.68 234.00,16.68
                   234.00,12.29 232.22,7.99 229.10,4.89
                   225.99,1.78 221.69,0.00 217.29,0.00
                   217.29,0.00 16.71,0.00 16.71,0.00
                   10.26,0.00 4.37,3.72 1.61,9.54
                   -1.15,15.35 -0.31,22.26 3.77,27.24
                   3.77,27.24 95.40,139.00 95.40,139.00
                   95.40,139.00 3.78,250.76 3.78,250.76
                   -0.31,255.74 -1.15,262.65 1.61,268.46
                   4.37,274.28 10.26,278.00 16.71,278.00
                   16.71,278.00 217.29,278.00 217.29,278.00
                   221.69,278.00 225.99,276.22 229.10,273.11
                   232.22,270.01 234.00,265.71 234.00,261.32
                   234.00,261.32 234.00,227.96 234.00,227.96
                   234.00,218.75 226.52,211.28 217.29,211.28
                   208.05,211.28 200.57,218.75 200.57,227.96
                   200.57,227.96 200.57,244.64 200.57,244.64
                   200.57,244.64 51.98,244.64 51.98,244.64
                   51.98,244.64 129.93,149.56 129.93,149.56
                   134.98,143.40 134.98,134.60 129.93,128.44
                   129.93,128.44 51.98,33.36 51.98,33.36
                   51.98,33.36 200.57,33.36 200.57,33.36
                   200.57,33.36 200.57,50.04 200.57,50.04
                   200.57,59.25 208.05,66.72 217.29,66.72
                   217.29,66.72 217.29,66.72 217.29,66.72 Z"></path>
      </svg></td>
    </tr>
    <tr id="scoring-row-teamtotal">
      <td id="text-team" class="first-column"><span class="arrow">→</span><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="user-friends" class="svg-inline--fa fa-user-friends fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" width="50px" height="50px"><path fill="currentColor" d="M192 256c61.9 0 112-50.1 112-112S253.9 32 192 32 80 82.1 80 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C51.6 288 0 339.6 0 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zM480 256c53 0 96-43 96-96s-43-96-96-96-96 43-96 96 43 96 96 96zm48 32h-3.8c-13.9 4.8-28.6 8-44.2 8s-30.3-3.2-44.2-8H432c-20.4 0-39.2 5.9-55.7 15.4 24.4 26.3 39.7 61.2 39.7 99.8v38.4c0 2.2-.5 4.3-.6 6.4H592c26.5 0 48-21.5 48-48 0-61.9-50.1-112-112-112z"></path></svg></td>
    </tr>
  </table>
</div>

<div id="ships" class="bm_flex-container whiteblock">
    <!-- BEGIN ShipBlock -->
    <div id="ship_{PLAYER_ID}" class="bm_fitin" style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</div>
    <!-- END ShipBlock -->
</div>
<div id="handmedal_area" class="handmedal_area">
  <div class="medal_wrapper">
    <div class="scrollmap_player_info" style="padding-left: 15px;">
      <div class="player_info_name tab">{MEDALS}</div>
    </div>
    <div id="medals_area" class="whiteblock">
      <div id="medals" class="medal_stock"></div>
    </div>
  </div>
  <div class="hand_wrapper">
    <div class="scrollmap_player_info" style="padding-left: 15px;">
      <div id='bm_title_myhand' class="player_info_name tab">{TITLE_MY_HAND}</div>
      <div id='bm_title_board' class="player_info_name tab">{TITLE_BOARD}</div>
    </div>
    <div id="myhand_area" class="whiteblock bm_gridded">
        <div id="myhand" class="whiteblock bm_tileArea">
        </div>
      <div id='card_left_count' class="card card-top-left"><div id="card_counter" class="card-inner bm_tileClass backtile"></div></div>
    </div>
  </div>
</div>


<div id="MainBoardArea" class="scrollBoards">
<div id="Boards">
   <!-- BEGIN ScrollerBlock -->
   <div id="{PLAYER_ID}_scrollmap_wrapper" class='scrollmap_wrapper' id='...'>
    <div class='scrollmap_player_info'>
      <div class='player_info_name tab' style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</div>
      <div id="{PLAYER_ID}_team_info" class='player_info_team tab' style="background-color:#AAAAA; display:none">TEAM 0</div>
    </div>
    <div id="{PLAYER_ID}_scrollmap" class="scrollerClass">
      <div id="{PLAYER_ID}_scrollmap_noclick" class="scroller_noclick"> </div>
      <div class="scroller_surface">
        <div class="movedown"> </div>
        <div class="movetop"> </div>
        <div class="moveleft"> </div>
        <div class="moveright"> </div>
      </div>
      <div id="{PLAYER_ID}_scrollmap_clickable" class="scroller_clickable"> </div>
    </div>
   </div>
   <!-- END ScrollerBlock -->
</div>
</div>




<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';
*/
var jstpl_medal_player_area = '<div class="medal_area_inner" id="mia_${player_id}_${medal_id}">\
    <div id="medal_${medal_id}_${type}" class="medal" medal-id="${data_id}" medal-type="${type}"></div>\
    <div id="back_medal_${medal_id}" class="back_medal" data-id="${back_id}"></div>\
    </div>';
var jstpl_player_board_medal_zone = '<div class="medal_area" id="ma_${player_id}"></div>';
var jstpl_player_board_medal_innerzone = '<div class="medal_area_inner" id="mia_${player_id}_${medal_id}">';
var jstpl_medal_player_stock = '<div class="medal_area_inner" id="stock_${medal_id}">\
    <div id="medal_${medal_id}_${type}" class="medal" medal-id="${data_id}" medal-type="${type}"></div>\
    <div id="back_medal_${medal_id}" class="back_medal" data-id="${back_id}"></div>\
    </div>';
var jstpl_back_medal = '<div class="back_medal" id="medal_${medal_id}"" data-id="${back_id}"></div>';
var jstpl_front_medal = '<div id="medal_${medal_id}_${type}" class="medal" medal-id="${data_id}" medal-type="${type}"></div>';
var jstpl_medal_group ='<div class="medal-group" id="group_${medal_group}"><div class="top-level" id="top_${medal_group}"></div><div class="bottom-level" id="bottom_${medal_group}"></div></div>';
var jstpl_helpIcon = `
<div id='help-icon'>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
  <g class="fa-group">
    <path class="fa-secondary" fill="currentColor" d="M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h352a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48zM224 430a46 46 0 1 1 46-46 46.06 46.06 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.81-25.27-33-45.7-33-27.19 0-39.44 13.13-57.3 35.79a12 12 0 0 1-16.67 2.13L116.82 170a12 12 0 0 1-2.71-16.26C141.4 113 176.16 90 230.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path>
    <path class="fa-primary" fill="currentColor" d="M224 338a46 46 0 1 0 46 46 46.05 46.05 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.66 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.67-32.54 34C215.13 238.53 184 254.94 184 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path>
  </g>
</svg>
</div>
`;
var jstmp_medal_face_select = `
<div id="face_select" class="bm_faceSelector">
  <i class="fa fa-qq"></i><label class="bm_switch">
    <input type="checkbox">
    <span class="bm_slider round"></span>
    </label>
    <span class="fa-stack">
      <i id="circl_pts" class="fa fa-circle-o fa-stack-2x"></i><strong class="fa-stack-1x">?</strong>
    </span>
</div>
`;
var jstpl_helpDialog = `
<div id="dialog-playerhelp"><div class="help_tile-img"></div></div>
`;
var jstpl_helpDialog5p = `
<div id="dialog-playerhelp"><div class="help_tile-img fiveplayers"></div></div>
`;
var jstpl_first_player_medal = '<div id="firstplayermedal" class="medal first_player"></div>';
var jstpl_team_banner = '<div class="team_banner" style="background-color: ${color};">TEAM ${team_nr}</div>';
var jstpl_tmp_tile = '<div id="tmp_tile_${tile_id}" class="bm_tileClass" style="background-position: -{back_x}% -{back_y}%; transform: rotate(${rot}deg);"></div>'
$(function() {
    $('.bm_fitin div').css('font-size', '1em');
    
    while( $('.bm_fitin div').height() > $('.bm_fitin').height() ) {
        $('.bm_fitin div').css('font-size', (parseInt($('.bm_fitin div').css('font-size')) - 1) + "px" );
    }
});
</script>  

{OVERALL_GAME_FOOTER}
