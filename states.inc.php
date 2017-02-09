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
 * states.inc.php
 *
 * incangold game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    2 => array(
        "name" => "reshuffle",
        "type" => "game",
        "action" => "streshuffle",
        "updateGameProgression" => true,
        "transitions" => array( "explore" => 3, "gameEndScoring" => 90 ) //game ends if iterations are 5
    ),

    3 => array(
        "name" => "explore",  // a card is drawn and gems splitted if necessary
        "type" => "game",
        "action" => "stexplore",
        "updateGameProgression" => true,
        "transitions" => array("cleanpockets" => 4, "vote" => 5) //4 after seeing the card if the 2nd hazard is drawn or 5 the remaining players vote to stay or to continue exploring
    ),

    4 => array(
	    "name" => "cleanpockets", 
        "description" => clienttranslate('2nd hazard of the same type was drawn and all explorers in the temple flee droping their pouches'),
        "type" => "game",
        "action" => "stcleanpockets",
        "updateGameProgression" => true,
        "transitions" => array("reshuffle" => 2)  // iterations++
    ),

    5 => array(
        "name" => "vote",
        "description" => clienttranslate('Players must vote to stay exploring or to leave to camp'),
        "descriptionmyturn" => clienttranslate('${you} must vote to stay exlporing or to leave to camp'),
        "type" => "multipleactiveplayer",
		"action" => "stvote",
        "possibleactions" => array( "voteExplore", "voteLeave" ),
        "updateGameProgression" => true,
        "transitions" => array( "processLeavers" => 6   ) //iteration ends if no players are still exploring
    ),
	
	6 => array(
	    "name" => "processLeavers", 
        "description" => clienttranslate('processing player actions acording their votes'),
        "type" => "game",
        "action" => "stprocessLeavers",
        "updateGameProgression" => true,
        "transitions" => array( "explore" => 3,"shufle" => 2) 
    ),
    
    90 => array(
        "name" => "gameEndScoring",
        "type" => "game",
        "action" => "stGameEndScoring",
        "updateGameProgression" => true,
        "transitions" => array( "" => 99 )
    ),
    
/*
    Examples:
    
    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),
    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/    
   
    // Final state.
    // Please do not modify.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);


