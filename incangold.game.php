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
  * incangold.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

class incangold extends Table
{
	function incangold( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array( 
                "iterations" => 10,
                "gameOverTrigger" => 11,
                "deckSize" => 12,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );
        $this->cards = self::getNew( "module.common.deck" );
		$this->cards->init( "cards" );
	}
	
    protected function getGameName( )
    {
        return "incangold";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown   and now white
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" , "ffffff" );

 
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
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        
		self::initStat( 'table', 'cards_drawn', 0 );    // Init a table statistics
        
		self::initStat( 'player', 'cards_seen' , 0 );  // Init a player statistics (for all players)
		self::initStat( 'player', 'artifacts_number' , 0 );  // Init a player statistics (for all players)
		self::initStat( 'player', 'gems_number' , 0 );  // Init a player statistics (for all players)
		
		
        // setup the initial game situation here

        self::setGameStateInitialValue( 'iterations', 0 ); //times deck has been exhausted


        //create the card deck here. There are 34 cards
        // (3 of each of the 5 types of hazard) = 15
        // 14 gems cards = 14
        // 5 artifacts one of each type = 5
        $cards = array();

        foreach( $this->card_types as $cardType)
        {
			if ($cardType['type_id']  <= 11)
            {
                $cardValues = array( 1, 2, 3, 4, 5, 7, 9,11,13,14,15); 
				$cardNumbers = array(1, 1, 1, 1, 2, 2, 1, 2, 1, 1, 1); 
                $type_id = $cardType["type_id"]-1 ;
				$card = array( 'type' => $cardType["type_id"], 'type_arg' => $cardValues[$type_id] , 'nbr' => $cardNumbers[$type_id]);
				array_push($cards, $card);
            }
            if ($cardType['isArtifact'] == 1)
            {	
                $card = array( 'type' => $cardType["type_id"], 'type_arg' => 0, 'nbr' => 1);
                array_push($cards, $card);
            }
			if ($cardType['isHazard'] == 1)   // 3 of each hazard
            {
                $card = array( 'type' => $cardType["type_id"], 'type_arg' => 0, 'nbr' => 3);
                array_push($cards, $card);
            }
        }
        
        $this->cards->createCards( $cards, 'deck' );
		
		self::setGameStateInitialValue( 'deckSize', $this->cards->countCardsInLocation("deck"));

        //shuffle 
        $this->cards->shuffle( 'deck' );

        $players = self::loadPlayersBasicInfos();

        // Activate first player (which is in general a good idea :) )
       // $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
        $players = self::loadPlayersBasicInfos();
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_field field FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
		
		$sql = "SELECT player_tent FROM player WHERE player_id='$current_player_id'";
        $result['tent'] = self::getUniqueValueFromDB( $sql );
        
        //show number of cards in deck too.
        $result['cardsRemaining'] = $this->cards->countCardsInLocation('deck');
        $result['shufflesRemaining'] = 4 - $this->getGameStateValue('iterations');
                
        //fields of all players are visible 
		
        $result['table'] = $this->cards->getCardsInLocation( 'table' );
              

        
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
        //Compute and return the game progression
        // there are 5 iterations so each one is a 20% of the game + aproximately 1% for each card drawn in this iteration.

        $iterations = self::getGameStateValue("iterations");
        $cardsDrawn = $this->cards->countCardsInLocation( 'table' );
		$result = ( $iterations * 20 ) + $cardsDrawn ;
        return ($result);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

	function getExploringPlayers()
    {
        $playersIds = array();
		$sql = "SELECT player_id id FROM player WHERE player_exploring=1";
        $playersIds = self::getCollectionFromDb( $sql );
        return $playersIds;
    }
	
	function setExploringPlayer ( $playerId , $exploringValue )
    {
		$sql = "UPDATE player SET player_exploring='$exploringValue' WHERE player_id='$playerId'";
        self::DbQuery( $sql ); 
    }
	
	function setGemsPlayer ( $playerId , $location ,$value )  // 'tent' or 'field'
    {
		$sql = "UPDATE player SET player_'$location'='$value' WHERE player_id='$playerId'";
        self::DbQuery( $sql ); 
    }
	
	function getGemsPlayer ( $playerId , $location )  //returns the number of gems in a location
	{
		$sql = $sql = "SELECT player_'$location'='$value' WHERE player_id='$playerId'"
	}
	
	
//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in incangold.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
*/    

    function voteExplore()
    {
   
    }

    function voteLeave()
    {

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

    function streshuffle()
	{
	$this->cards->moveAllCardsInLocation( 'table', 'deck' );  //collect all cards to the deck and reshuffle
	$this->cards->shuffle( 'deck' );
	
	$players = self::loadPlayersBasicInfos();
	foreach( $players as $player_id => $player )
	{
		setExploringPlayer($player_id , 1);   // All players are now exploring
	}
	$iterations = self::getGameStateValue("iterations");
	$iterations++ ;
	self::setGameStateInitialValue( 'iterations', $iterations )
	if ( $iterations > 5 ) 
		{
			$this->gamestate->nextState( 'gameEndScoring' );
		}
	else
		{
			$this->gamestate->nextState( 'explore' );
		}
	}
	
	function stexplore()
	{
		
	$players = self::loadPlayersBasicInfos();
	for ($i = 1; $i <= 20; $i++) 
		{
			
		$TopCard = $this->cards->getCardOnTop( 'deck' ) ;
			if ( $TopCard['type'] <= 11 )
				{
				$gems = $TopCard['type_arg'] % sizeof( $players );
				}
			else
				{
				$gems=0;	
				}
		$this->cards->pickCardForLocation( 'deck', 'table', $gems );
		}
	$this->gamestate->nextState( 'vote' );	
	}
	
	function stcleanpockets()
	{
		
	}
	
	function stvote()
	{
		
	}
	
	function stprocessLeavers()
	{
		
	}

    function displayScores()
    {
        $players = self::loadPlayersBasicInfos();

        $table[] = array();
        $table[] = array();
        $table[] = array();
        $table[] = array();
        $table[] = array();
        $table[] = array();
        $table[] = array();
        
        //left hand col
        $table[0][] = array( 'str' => ' ', 'args' => array(), 'type' => 'header');
        $table[1][] = $this->resources["gems"    ];
        $table[2][] = $this->resources["tablet"  ];
        $table[3][] = $this->resources["idol"    ];
        $table[4][] = $this->resources["statue"  ];
        $table[5][] = $this->resources["vase"    ];
        $table[6][] = $this->resources["necklace"];
		$table[7][] = array( 'str' => '<span class=\'score\'>Score</span>', 'args' => array(), 'type' => '');

        foreach( $players as $player_id => $player )
        {
            $table[0][] = array( 'str' => '${player_name}',
                                 'args' => array( 'player_name' => $player['player_name'] ),
                                 'type' => 'header'
                               );
            $table[1][] = count($this->getCardsInLocationByType('storage', $player['player_id'], 1));
            $table[2][] = count($this->getCardsInLocationByType('storage', $player['player_id'], 2));
            $table[3][] = count($this->getCardsInLocationByType('storage', $player['player_id'], 3));
            $table[4][] = count($this->getCardsInLocationByType('storage', $player['player_id'], 4));
            $table[5][] = count($this->getCardsInLocationByType('storage', $player['player_id'], 5));
            $score = self::getObjectFromDB( "SELECT player_score FROM player WHERE player_id='".$player_id."'" );
            $table[6][] = array( 'str' => '<span class=\'score\'>${player_score}</span>',
                                 'args' => array( 'player_score' => $score['player_score'] ),
                                 'type' => ''
                               );
        }

        $this->notifyAllPlayers( "tableWindow", '', array(
            "id" => 'finalScoring',
            "title" => $this->resources["score_window_title"],
            "table" => $table,
            "header" => '<div>'.$this->resources["win_condition"].'</div>',
            //"closelabel" => clienttranslate( "Closing button label" )
        ) ); 
    }

    function stGameEndScoring()
    {
        //stats for each player, we want to reveal how many of each crop they have in storage
        //then set their final score to whichever is lowest.
        //In the case of a tie, check next smallest pile and so on. Set auxillery score for this

        //stats first
        $players = self::loadPlayersBasicInfos();

        //$maxAuxScore = 0;

        $playerTempScores = array();

        foreach($players as $player)
        {
            $papyrusCount = count($this->getCardsInLocationByType('storage', $player['player_id'], 1));
            $wheatCount = count($this->getCardsInLocationByType('storage', $player['player_id'], 2));
            $lettuceCount = count($this->getCardsInLocationByType('storage', $player['player_id'], 3));
            $castorCount = count($this->getCardsInLocationByType('storage', $player['player_id'], 4));
            $flaxCount = count($this->getCardsInLocationByType('storage', $player['player_id'], 5));

            self::setStat($papyrusCount, 'papyrus_number', $player['player_id']);
            self::setStat($wheatCount, 'wheat_number', $player['player_id']);
            self::setStat($lettuceCount, 'lettuce_number', $player['player_id']);
            self::setStat($castorCount, 'castor_number', $player['player_id']);
            self::setStat($flaxCount, 'flax_number', $player['player_id']);
            
            $scores = array();
            $scores[] = $papyrusCount;
            $scores[] = $wheatCount;
            $scores[] = $lettuceCount;
            $scores[] = $castorCount;
            $scores[] = $flaxCount;
                        
            sort($scores);

            $sql = "UPDATE player SET player_score = ".$scores[0]." WHERE player_id=".$player['player_id'];
            self::DbQuery( $sql );            

            //aux is int(1), max value is 4294967295
            $aux_score = $scores[4]+$scores[3]*20+$scores[2]*20*20+$scores[1]*20*20*20+$scores[0]*20*20*20*20;

            array_push($playerTempScores, $aux_score);
        }

        $i = 0;
        foreach($players as $player)
        {
            //set aux score to the count of the number of players with lower scores
            $beaten = 0;
            foreach($playerTempScores as $score)
            {
                if ($playerTempScores[$i] > $score)
                {
                    $beaten++;
                }
            }

            $sql = "UPDATE player SET player_score_aux = ".$beaten." WHERE player_id=".$player['player_id'];
            self::DbQuery( $sql );
            $i++;
        }

        $this->displayScores();
    
        $this->gamestate->nextState('');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
}
