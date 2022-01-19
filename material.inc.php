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
 * material.inc.php
 *
 * BigMonster game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/


/*
////// TILES DESCRIPTION /////
  this array describe the different tiles of the game:
    * name / nametr : name of the tile
    * nbr : number of tiles of each kind for that type (eg : every kind of ice monster are 4 times in a 4-players mode)
              Note that : 1/ no 'nbr' is specified for "lava" and "rune" as it depends of the kind in that type
                          2/ 'nbr' is an array for later expansion to different number of players games
    * variety : number of different kind of monster for that type (eg. there is 4 different ice monsters : blue, orange, green and red)
  
*/


$this->tiles_info = array( 
  1 => array( 'name' => clienttranslate('ice'),
              'nametr' => self::_('ice') ,
              'nbr' => array(
                '2' => 4,
                '3' => 6,
                '4' => 4,
                '5' => 5,
                '6' => 6),
              'variety' => 4),
  2 => array( 'name' => clienttranslate('mutagenic'),
              'nametr' => self::_('mutagenic'),
              'nbr' => array(
              '2' => 4,
              '3' => 6,
              '4' => 4,
              '5' => 5,
              '6' => 6),
              'variety' => 2 ),
  3 => array( 'name' => clienttranslate('bigmonster'),
              'nametr' => self::_('bigmonster'),
              'nbr' => array(
                '2' => 5,
                '3' => 8,
                '4' => 5,
                '5' => 6,
                '6' => 8),
              'variety' => 2),
  4 => array( 'name' => clienttranslate('lava'),
              'nametr' => self::_('lava'),
              'variety' => 5),
  5 => array( 'name' => clienttranslate('swamp'),
              'nametr' => self::_('swamp'),
              'nbr' => array(
                '2' => 5,
                '3' => 5,
                '4' => 5,
                '5' => 5,
                '6' => 5
              ),
              'variety' => 1 ),
  6 => array( 'name' => clienttranslate('grassland'),
              'nametr' => self::_('grassland'),
              'variety' => 9),
  7 => array( 'name' => clienttranslate('desert'),
              'nametr' => self::_('desert'),
              'nbr' => array(
                '2' => 8,
                '3' => 11,
                '4' => 8,
                '5' => 9,
                '6' => 11
              ),
              'variety' => 1 ),
  8 => array( 'name' => clienttranslate('rune'),
              'nametr' => self::_('rune') ,
              'variety' => 6),
);


// Description of monster tiles (pts per tile or other monster-type specific infos)
$this->monster_infos = array(
  1 => array(
    1 => array( 'possible_mutations' => 1,
                'pts' => array(
                                0 => 1,
                                1 => 10,
                                2 => 10),
                 'name' => self::_('Blue ice monster'),
                 'descr' => self::_('This monster worth 1 points but can mute one time to worth 10 points. A second mutagenic monster does not affect this monster (maximum 1 mutation)')
                ),
    2 => array( 'possible_mutations' => 2,  
                'pts' => array(
                                0 => 2,
                                1 => 7,
                                2 => 20),
                'name' => self::_('Orange ice monster'),
                'descr' => self::_('This monster worth 2 points but can evovle to muted form two times by placing mutagenic monster above and below (1st mutation : 7 points, 2nd : 20 points)')
                ),
    3 => array( 'possible_mutations' => 2,  
                'pts' => array(
                                0 => 3,
                                1 => 8,
                                2 => 18),
                'name' => self::_('Green ice monster'),
                'descr' => self::_('This monster worth 3 points but can evovle to muted form two times by placing mutagenic monster above and below (1st mutation : 8 points, 2nd : 18 points)')
                ),
    4 => array( 'possible_mutations' => 2,  
                'pts' => array(
                                0 => 4,
                                1 => 9,
                                2 => 15),
                'name' => self::_('Red ice monster'),
                'descr' => self::_('This monster worth 4 points but can evovle to muted form two times by placing mutagenic monster above and below (1st mutation : 9 points, 2nd :1(5 points).)')
                )
                ),
  2 => array( 'pts' => 0,
              1 => array(
                 'name' => self::_('Downward mutagenic monster'),
                 'descr' => self::_('Place this monster above an ice monster to make it mute')
                ),
              2 => array(
                 'name' => self::_('Upward mutagenic monster'),
                  'descr' => self::_('Place this monster below an ice monster to make it mute')
                )
              ),
  3 => array('name' => 'Big monster',
              'descr' => '1 point alone but 11 points when bigmonster tail and head are put together to make one Big Monster'),
  4 => array(
    1 => array ('pts' => 3,
                'diamonds' => array('GL', 'GR', 'RB'),
                'name' => self::_('Blue dragoon monster'),
                'descr' => self::_('3 points. All green diamonds and red on bottom')),
    2 => array ('pts' => 3,
                'diamonds' => array('GL', 'GR', 'RT'),
                'name' => self::_('blue dragoon monster'),
                'descr' => self::_('3 points. All green diamonds and red on top')),
    3 => array ('pts' => 3,
                'diamonds' => array('BTL','BTR','BBL','BBR'),
                'name' => self::_('green dragoon monster'),
                'descr' => self::_('3 points. All blue diamonds')),
    4 => array ('pts' => 1,
                'diamonds' => array('GL', 'GR', 'RB', 'BTL','BTR','BBL','BBR'),
                'name' => self::_('Purple dragoon monster'),
                'descr' => self::_('1 points. Red diamond on bottom and all other diamonds')),
    5 => array ('pts' => 1,
                'diamonds' => array('GL', 'GR', 'RT', 'BTL','BTR','BBL','BBR'),
                'name' => self::_('Purple dragoon monster'),
                'descr' => self::_('1 points. Red diamond on top and all other diamonds'))
  ),
  5 => array('pts' => array(
                        0 => 0,
                        1 => 2,
                        2 => 8,
                        3 => 18,
                        4 => 32,
                        5 => 50),
                'name' => self::_('swamp monster'),
                'descr' => self::_("2 points per swamp monster on the player's board")),
  6 => array(
    1=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('2 points per complete green crystal')),
    2=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('2 points per earned medal')),
    3=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('1 point per complete crystal')),
    4=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('1 point per different type of monster. max 7 points')),
    5=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('2 points per rune monster')),
    6=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('1 point per lava monster')),
    7=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('4 points per complete bigmonster')),
    8=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('2 points per desert tile')),
    9=> array('name' => self::_('Grassland monster'),
              'descr' => self::_('2 points per grassland tile (this one included)')),
  ),
  7 => array('pts' => 0,
              'name' => self::_('desert monster'),
              'descr' => 'No points as such but is goal of a medal and count as monsters for penalty medal'),
  8 => array(
    1 => array( 'monsters' => 2,
                'pts' => 0,
                'diamonds' => array(),
              'name' => self::_('Rune monster'),
              'descr' => '2 Runes monsters (count as 2 monsters for the penalty medal count)'),
    2 => array( 'monsters' => 1,
                'pts' => 0,
                'diamonds' => array('GL', 'GR'),
              'name' => self::_('Rune monster'),
              'descr' => 'Count as one rune monster for penalty medal. Green diamonds on sides'),
    3 => array( 'monsters' => 1,
                'pts' => 0,
                'diamonds' => array('BTL', 'BTR'),
              'name' => self::_('rune monster'),
              'descr' => 'Count as one rune monster for penalty medal. Blue diamonds on top'),
    4 => array( 'monsters' => 1,
                'pts' => 0,
                'diamonds' => array('BBL', 'BBR'),
              'name' => self::_('rune monster'),
              'descr' => 'Count as one rune monster for penalty medal. Blue diamonds on bottom'),
    5 => array( 'monsters' => 1,
                'pts' => 0,
                'diamonds' => array('RT'),
              'name' => self::_('rune monster'),
              'descr' => 'Count as one rune monster for penalty medal. Red diamonds on top'),
    6 => array( 'monsters' => 1,
                'pts' => 0,
                'diamonds' => array('RB'),
              'name' => self::_('rune monster'),
              'descr' => 'Count as one rune monster for penalty medal. Red diamonds on bottom'),
  ),
);

$this->explorer_infos = array(
  1 => array( 'name' => clienttranslate('male blue explorer'),
              'nametr' => self::_('male blue explorer'),
              'descr' => clienttranslate('1 point per mutant monster tile'),
              'descrtr' => self::_('1 point per mutant monster tile'),
              'player_numbers' => 'any',
              'diamonds' => array()),
  2 => array( 'name' => clienttranslate('black female explorer'),
              'nametr' => self::_('black female explorer'),
              'descr' => clienttranslate('You can put your hand of tiles on your own ship (if still free)'),
              'descrtr' => self::_("You can put your hand of tiles on your own ship (if still free)"),
              'player_numbers' => '4+',
              'diamonds' => array('GR', 'BBL')),
  3 => array( 'name' => clienttranslate('black male explorer'),
              'nametr' => self::_('black male explorer'),
              'descr' => clienttranslate('At the end of the game, choose a tile in the discard pile and place it in your exploration zone'),
              'descrtr' => self::_('At the end of the game, choose a tile in the discard pile and place it in your exploration zone'),
              'player_numbers' => '4+',
              'diamonds' => array('GR')),
  4 => array( 'name' => clienttranslate('red female explorer'),
              'nametr' => self::_('red female explorer'),
              'descr' => clienttranslate('1 point per lava tile'),
              'descrtr' => self::_('1 point per lava tile'),
              'player_numbers' => 'any',
              'diamonds' => array()),
  5 => array( 'name' => clienttranslate('purple female explorer'),
              'nametr' => self::_('purple female explorer'),
              'descr' => clienttranslate('Starts with one mutagenic monster'),
              'descrtr' => self::_('Starts with one mutagenic monster'),
              'player_numbers' => 'any',
              'diamonds' => array()),
  6 => array( 'name' => clienttranslate('purple male explorer'),
              'nametr' => self::_('purple male explorer'),
              'descr' => clienttranslate('1 point per mutagenic monster'),
              'descrtr' => self::_('1 point per mutagenic monster'),
              'player_numbers' => 'any',
              'diamonds' => array('RLB', 'RRB')),
  7 => array( 'name' => clienttranslate('green female explorer'),
              'nametr' => self::_('green female explorer'),
              'descr' => clienttranslate('1 point per grassland tile'),
              'descrtr' => self::_('1 point per grassland tile'),
              'player_numbers' => 'any',
              'diamonds' => array('RLB','RRB','GR','BBL','BBR','BTR')),
  8 => array( 'name' => clienttranslate('orange female explorer'),
              'nametr' => self::_('orange female explorer'),
              'descr' => clienttranslate('2 points per medal'),
              'descrtr' => self::_('2 points per medal'),
              'player_numbers' => 'any',
              'diamonds' => array('GR','BTR','BBR')),
  9 => array( 'name' => clienttranslate('red male explorer'),
              'nametr' => self::_('red male explorer'),
              'descr' => clienttranslate('2 points per rune monster'),
              'descrtr' => self::_('2 points per rune monster'),
              'player_numbers' => 'any',
              'diamonds' => array('RLB','RRB')),
  10 => array( 'name' => clienttranslate('blue female explorer'),
              'nametr' => self::_('blue female explorer'),
              'descr' => clienttranslate('4 points per complete Big Monster'),
              'descrtr' => self::_('4 points per complete Big Monster'),
              'player_numbers' => 'any',
              'diamonds' => array('GR', 'GL')),
  11 => array( 'name' => clienttranslate('green male explorer'),
              'nametr' => self::_('green male explorer'),
              'descr' => clienttranslate('1 point per complete crystal'),
              'descrtr' => self::_('1 point per complete crystal'),
              'player_numbers' => 'any',
              'diamonds' => array('BBR', 'RRB')),
  12 => array( 'name' => clienttranslate('orange male explorer'),
               'nametr' => self::_('orange male explorer'),
               'descr' => clienttranslate('Starts with one desert tile'),
               'descrtr' => self::_('Starts with one desert tile '),
               'player_numbers' => 'any',
              'diamonds' => array('RRB'))
);


$this->medals_infos = array(
  1 => array( 'name' => clienttranslate('first player with 2 complete big monsters (5 points).'),
              'nametr' => self::_('first player with 2 complete big monsters (5 points).'),
              'name_team' => clienttranslate('first team with 4 complete big monster(5 points).'),
              'nametr_team' => self::_('first team with 4 complete big monster (5 points).'),
              'category' => 'random',
              'pts' => 5),
  2 => array( 'name' => clienttranslate('first player with 3 differents crystals (5 points).'),
              'nametr' => self::_('first player with 3 differents crystals (5 points).'),
              'name_team' => clienttranslate('first team with 3 trios of different crystals (5 points).'),
              'nametr_team' => self::_('first team with 3 trios of different crystals (5 points).'),
              'category' => 'random',
              'pts' => 5),
  3 => array( 'name' => clienttranslate('first player with 3 Desert tiles (10 points).'),
              'nametr' => self::_('first player with 3 Desert tiles (10 points).'),
              'name_team' => clienttranslate('first team with 6 Desert tiles. (10 points).'),
              'nametr_team' => self::_('first team with 6 Desert tiles. (10 points).'),
              'category' => 'permanent',
              'pts' => 10),
  4 => array( 'name' => clienttranslate('first player with 6 Ice tiles (5 points).'),
              'nametr' => self::_('first player with 6 Ice tiles (5 points).'),
              'name_team' => clienttranslate('first team with 12 Ice tiles (5 points).'),
              'nametr_team' => self::_('first team with 12 Ice tiles (5 points).'),
              'category' => 'random',
              'pts' => 5),
  5 => array( 'name' => clienttranslate('first player with 5 Lava tiles (5 points).'),
              'nametr' => self::_('first player with 5 Lava tiles (5 points).'),
              'name_team' => clienttranslate('first team with 10 Lava tiles (5 points).'),
              'nametr_team' => self::_('first team with 10 Lava tiles (5 points).'),
              'category' => 'random',
              'pts' => 5),
  6 => array( 'name' => clienttranslate('first player with 4 mutagenic monsters (5 points).'),
              'nametr' => self::_('first player with 4 mutagenic monsters (5 points).'),
              'name_team' => clienttranslate('first team with 12 mutagenic monsters (5 points).'),
              'nametr_team' => self::_('first team with 12 mutagenic monsters (5 points).'),
              'category' => 'random',
              'pts' => 5),
  7 => array( 'name' => clienttranslate('At the end of the game, player with fewest desert and rune monsters (-10 points).'),
              'nametr' => self::_('At the end of the game, player with fewest desert and rune monsters (-10 points).'),
              'name_team' => clienttranslate('At the end of the game, team with fewest desert and rune monsters (-10 points).'),
              'nametr_team' => self::_('At the end of the game, team with fewest desert and rune monsters (-10 points).'),
              'category' => 'permanent',
              'pts' => -10),
  8 => array( 'name' => clienttranslate('first player with 5 different tiles (5 points).'),
              'nametr' => self::_('first player with 5 different tiles (5 points).'),
              'name_team' => clienttranslate('first team with 7 different tiles (5 points).'),
              'nametr_team' => self::_('first team with 7 different tiles (5 points).'),
              'category' => 'random',
              'pts' => 5),
  9 => array( 'name' => clienttranslate('first player with 4 Rune monsters (5 points).'),
              'nametr' => self::_('first player with 4 Rune monsters (5 points).'),
              'name_team' => clienttranslate('first team with 8 Rune monsters (5 points).'),
              'nametr_team' => self::_('first team with 8 Rune monsters (5 points).'),
              'category' => 'permanent',
              'pts' => 5),
  10 => array( 'name' => clienttranslate('first player with 4 swamp and or grassland tiles (5 points).'),
              'nametr' => self::_('first player with 4 swamp and or grassland tiles (5 points).'),
              'name_team' => clienttranslate('first team with 8 swamp and or grassland tiles (5 points).'),
              'nametr_team' => self::_('first team with 8 swamp and or grassland tiles (5 points).'),
              'category' => 'random',
              'pts' => 5)
);


// matching table between points of a medal and its id (position on image sprite)
// first level : type of back medal :
//  1 : individual play
//  2 : team play
//  3 : neutral medals
// second level : id of back tile according to points : pts => id
$this->matching_pts_back_id = array(
  1 => array(
    -5 => 99, // this score does not exist in this version (no strech goal / angry monster)
    5 => 2,
    10 => 8,
    -10 => 5
  ),
  2 => array(
    -5 => 99, // this score does not exist in this version (no strech goal / angry monster)
    5 => 3,
    10 => 9,
    -10 => 6
  ),
  3 => array(
    -5 => 99, // this score does not exist in this version (no strech goal / angry monster)
    5 => 4,
    10 => 10,
    -10 => 7
  )
);