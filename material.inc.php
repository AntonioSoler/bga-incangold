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
    "gems"     => clienttranslate('Gems'),
    "tablet"   => clienttranslate('Tablet'),
    "idol"     => clienttranslate('Idol'),
    "statue"   => clienttranslate('Statue'),
    "vase"     => clienttranslate('Vase'),
    "necklace" => clienttranslate('Necklace'),
    "mummy"    => clienttranslate('Mummy'),
	"snake"    => clienttranslate('Snake'),
	"spiders"  => clienttranslate('Spiders'),
	"rocks"    => clienttranslate('Rocks'),
	"fire"     => clienttranslate('Fire'),
	"artifacts"    => clienttranslate('artifacts'),
	"score_window_title" => clienttranslate('FINAL SCORE'),
	"win_condition" => clienttranslate('The player with the most gems in their tent wins - Artifacts are a variable amount of gems')
);

 //
 
 $this->card_types = array(
	1  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  1, 'isArtifact' => 0, 'isHazard' => 0),
	2  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  2, 'isArtifact' => 0, 'isHazard' => 0),
	3  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  3, 'isArtifact' => 0, 'isHazard' => 0),
	4  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  4, 'isArtifact' => 0, 'isHazard' => 0),
	5  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  5, 'isArtifact' => 0, 'isHazard' => 0),
	6  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  6, 'isArtifact' => 0, 'isHazard' => 0),
	7  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  7, 'isArtifact' => 0, 'isHazard' => 0),
	8  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  8, 'isArtifact' => 0, 'isHazard' => 0),
	9  => array( 'name' => $this->resources["gems"    ], 'type_id' =>  9, 'isArtifact' => 0, 'isHazard' => 0),
	10 => array( 'name' => $this->resources["gems"    ], 'type_id' => 10, 'isArtifact' => 0, 'isHazard' => 0),
	11 => array( 'name' => $this->resources["gems"    ], 'type_id' => 11, 'isArtifact' => 0, 'isHazard' => 0),
	12 => array( 'name' => $this->resources["tablet"  ], 'type_id' => 12, 'isArtifact' => 1, 'isHazard' => 0, 'artifactValue' => 5),
	13 => array( 'name' => $this->resources["idol"    ], 'type_id' => 13, 'isArtifact' => 1, 'isHazard' => 0, 'artifactValue' => 7),
	14 => array( 'name' => $this->resources["statue"  ], 'type_id' => 14, 'isArtifact' => 1, 'isHazard' => 0, 'artifactValue' => 8),
	15 => array( 'name' => $this->resources["vase"    ], 'type_id' => 15, 'isArtifact' => 1, 'isHazard' => 0, 'artifactValue' => 10),
	16 => array( 'name' => $this->resources["necklace"], 'type_id' => 16, 'isArtifact' => 1, 'isHazard' => 0, 'artifactValue' => 12),
	17 => array( 'name' => $this->resources["mummy"   ], 'type_id' => 17, 'isArtifact' => 0, 'isHazard' => 1),
	18 => array( 'name' => $this->resources["snake"   ], 'type_id' => 18, 'isArtifact' => 0, 'isHazard' => 1),
	19 => array( 'name' => $this->resources["spiders" ], 'type_id' => 19, 'isArtifact' => 0, 'isHazard' => 1),
	20 => array( 'name' => $this->resources["rocks"   ], 'type_id' => 20, 'isArtifact' => 0, 'isHazard' => 1),
	21 => array( 'name' => $this->resources["fire"    ], 'type_id' => 21, 'isArtifact' => 0, 'isHazard' => 1)//,
);


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/




