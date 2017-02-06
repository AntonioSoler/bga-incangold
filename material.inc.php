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
    "tablet"   => clienttranslate('tablet'),
    "idol"     => clienttranslate('idol'),
    "statue"   => clienttranslate('statue'),
    "vase"     => clienttranslate('vase'),
    "necklace" => clienttranslate('necklace'),
    "mummy"    => clienttranslate('mummy'),
	"rocks"    => clienttranslate('rocks'),
	"snake"    => clienttranslate('snake'),
	"spiders"  => clienttranslate('spiders'),
	"fire"     => clienttranslate('fire')
);

 //
 
 $this->card_types = array(
	1 => array( 'name' => $this->resources["gems"    ], 'type_id' => 1, , 'isArtifact' => 0, 'isHazard' => 0),
	2 => array( 'name' => $this->resources["tablet"  ], 'type_id' => 2, , 'isArtifact' => 1, 'isHazard' => 0),
	3 => array( 'name' => $this->resources["idol"    ], 'type_id' => 3, , 'isArtifact' => 1, 'isHazard' => 0),
	4 => array( 'name' => $this->resources["statue"  ], 'type_id' => 4, , 'isArtifact' => 1, 'isHazard' => 0),
	5 => array( 'name' => $this->resources["vase"    ], 'type_id' => 5, , 'isArtifact' => 1, 'isHazard' => 0),
	6 => array( 'name' => $this->resources["necklace"], 'type_id' => 6, , 'isArtifact' => 1, 'isHazard' => 0),
	7 => array( 'name' => $this->resources["mummy"   ], 'type_id' => 7, , 'isArtifact' => 0, 'isHazard' => 1),
	8 => array( 'name' => $this->resources["rocks"   ], 'type_id' => 8, , 'isArtifact' => 0, 'isHazard' => 1),
	9 => array( 'name' => $this->resources["snake"   ], 'type_id' => 9, , 'isArtifact' => 0, 'isHazard' => 1),
	10 => array( 'name' => $this->resources["spiders" ], 'type_id' => 10, , 'isArtifact' => 0, 'isHazard' => 1),
	11 => array( 'name' => $this->resources["fire"    ], 'type_id' => 11, , 'isArtifact' => 0, 'isHazard' => 1)//,
);


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/




