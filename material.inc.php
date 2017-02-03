<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * incangold implementation : © Antonio Soler <morgald.es@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * incangold game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

 //trasnslatable text will go here for convenience
 $this->resources = array(
    "gems"     => clienttranslate('gems'),
    "artifact" => clienttranslate('artifact'),
    "mummy"    => clienttranslate('mummy'),
	"rocks"    => clienttranslate('rocks'),
	"snake"    => clienttranslate('snake'),
	"spiders"  => clienttranslate('spiders'),
	"fire"     => clienttranslate('fire'),
    "explore"  => clienttranslate('explore'),
	"camp"     => clienttranslate('camp')
);

 //Papyrus (Red), Wheat (Yellow), Lettuce (Green), Castor (Brown), and Flax (Lavender),
 $this->card_types = array(
	1 => array( 'name' => $this->resources["gems"    ], 'type_id' => 1, , 'isScore' => 1, 'isHazard' => 0),
	2 => array( 'name' => $this->resources["artifact"], 'type_id' => 2, , 'isScore' => 1, 'isHazard' => 0),
	3 => array( 'name' => $this->resources["mummy"   ], 'type_id' => 3, , 'isScore' => 0, 'isHazard' => 1),
	4 => array( 'name' => $this->resources["rocks"   ], 'type_id' => 4, , 'isScore' => 0, 'isHazard' => 1),
	5 => array( 'name' => $this->resources["snake"   ], 'type_id' => 5, , 'isScore' => 0, 'isHazard' => 1),
	6 => array( 'name' => $this->resources["spiders" ], 'type_id' => 6, , 'isScore' => 0, 'isHazard' => 1),
	7 => array( 'name' => $this->resources["fire"    ], 'type_id' => 7, , 'isScore' => 0, 'isHazard' => 1),
	8 => array( 'name' => $this->resources["explore" ], 'type_id' => 8, , 'isScore' => 0, 'isHazard' => 0),
	9 => array( 'name' => $this->resources["camp"    ], 'type_id' => 9, , 'isScore' => 0, 'isHazard' => 0)//,
);


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/




