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
				"artifactspicked" => 13,
				
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
		self::reattributeColorsBasedOnPreferences( $players, array(  /* LIST HERE THE AVAILABLE COLORS OF YOUR GAME INSTEAD OF THESE ONES */"ff0000", "008000", "0000ff", "ffa500", "773300" , "ffffff" ) );
		self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        
		self::initStat( 'table', 'cards_drawn', 1 );    // Init a table statistics
        self::initStat( 'table', 'artifacts_drawn', 0 );
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
        self::setGameStateInitialValue( 'artifactspicked', 0 );
		
        //shuffle 
        
		
		self::DbQuery( "UPDATE cards set card_location = 'removed' WHERE card_type in (12,13,14,15,16)" ); //take artifacts out
		
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
        $result['current_player_id'] = $current_player_id;
		$players = self::loadPlayersBasicInfos();
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_field field , Count(card_id) artifacts FROM player LEFT OUTER JOIN cards On player_id=card_location GROUP BY player_id";
		
        $result['players'] = self::getCollectionFromDb( $sql ); //fields of all players are visible 
		
		$sql = "SELECT player_tent FROM player WHERE player_id='$current_player_id'";
        $result['tent'] = self::getUniqueValueFromDB( $sql );  //only you can see your tent
        
        //show number of cards in deck too.
        $result['cardsRemaining'] = $this->cards->countCardsInLocation('deck');
        $result['iterations'] = $this->getGameStateValue('iterations');
        $result['exploringPlayers'] = $this->getExploringPlayers();
		$sql = "SELECT COUNT(*) FROM cards WHERE card_location ='temple' AND card_type in (12,13,14,15,16)"; 
		$result['templeartifacts'] = self::getUniqueValueFromDB( $sql );
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
		$result = ( ($iterations -1) * 20 ) + $cardsDrawn ;
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
		$sql = "SELECT player_id id, player_name playerName , player_color playerColor FROM player WHERE player_exploring=1";
        //$playersIds = self::getObjectListFromDB( $sql );
		$playersIds = self::getCollectionFromDB( $sql );	
		self::debug ("******* getExploringPlayers   ".$playersIds);
        return $playersIds;
    }
	function getExploringPlayersList()
    {
        $playersIds = array();
		$sql = "SELECT player_id id FROM player WHERE player_exploring=1";
        $playersIds = self::getObjectListFromDB( $sql );
		//$playersIds = self::getCollectionFromDB( $sql );	
		self::debug ("******* getExploringPlayers   ".$playersIds);
        return $playersIds;
    }
	
	function setExploringPlayer ( $playerId , $exploringValue )
    {
		$sql = "UPDATE player SET player_exploring=$exploringValue WHERE player_id=$playerId";
        self::DbQuery( $sql ); 
    }
	
	function getLeavingPlayers()
    {
        $playersIds = array();
		$sql = "SELECT player_id id, player_name playerName , player_color playerColor FROM player WHERE player_leaving=1";
        //$playersIds = self::getObjectListFromDB( $sql );
		$playersIds = self::getCollectionFromDB( $sql );	
		self::debug ("******* getExploringPlayers   ".$playersIds);
        return $playersIds;
    }
	
	function setLeavingPlayer ( $playerId , $leavingValue )
    {
		$sql = "UPDATE player SET player_leaving=$leavingValue WHERE player_id=$playerId";
        self::DbQuery( $sql ); 
    }
	
	function setGemsPlayer ( $playerId , $location , $value )  // location can be 'tent' or 'field'
    {
		$sql = "UPDATE player SET player_$location=$value WHERE player_id=$playerId";
        self::DbQuery( $sql ); 
    }
	
	function getGemsPlayer ( $playerId , $location )  //returns the number of gems in a location
	{
		$sql = $sql = "SELECT player_$location FROM player WHERE player_id=$playerId";
		$value=self::getUniqueValueFromDB( $sql );
		return $value;
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
	$current_player_id = self::getCurrentPlayerId(); 	
	$this->gamestate->setPlayerNonMultiactive( $current_player_id, '' );
    }

    function voteLeave()
    {
	$current_player_id = self::getCurrentPlayerId(); 
	$this->setLeavingPlayer ( $current_player_id  , 1 );
	$this->gamestate->setPlayerNonMultiactive( $current_player_id, '' );	
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

	////////////////////////////////////////////////////////////////////////////

    function streshuffle()
	{
	$this->cards->moveAllCardsInLocation( 'table', 'deck' );  //collect all cards to the deck and reshuffle
	self::DbQuery( "UPDATE cards set card_location = 'deck' WHERE card_type in (12,13,14,15,16) AND card_location = 'removed' LIMIT 1 " ); // PUT 1 ARTIFACT MORE
	$this->cards->shuffle( 'deck' );
	$cardsRemaining = $this->cards->countCardsInLocation('deck');
	$iterations = 1 + $this->getGameStateValue('iterations');	
	
	if  ( $iterations <= 4 ) 
	{
	self::notifyAllPlayers( "reshuffle", clienttranslate( '<b>All explorers returned to camp. Any artifact not picked is now lost forever. The deck is shuffled. This is the expedition number ${iterations}</b>'), array( 'iterations' => $iterations , 'cardsRemaining' => $cardsRemaining )) ;
	}
	if  ( $iterations == 5 ) 
	{ 
    self::notifyAllPlayers( "reshuffle", clienttranslate( '<b>All explorers returned to camp. Any artifact not picked is now lost forever. The deck is reshufled. This is the FINAL expedition.</b>' ), array( 'iterations' => $iterations , 'cardsRemaining' => $cardsRemaining )) ;
	}
	if  ( $iterations > 5 ) 
	{ 
    self::notifyAllPlayers( "reshuffle", clienttranslate( '<b>All explorers returned to camp. END OF THE GAME</b>'), array( 'iterations' => $iterations , 'cardsRemaining' => $cardsRemaining )) ;
	}
	
	$players = self::loadPlayersBasicInfos();
	foreach( $players as $player_id => $player )
	{
		$this->setExploringPlayer($player_id , 1);   // All players are now exploring
	}
	$sql = "UPDATE player SET player_leaving=0 WHERE 1";  // Reset players votes
    self::DbQuery( $sql ); 
		
	$iterations = self::getGameStateValue("iterations");
	
	if ( $iterations == 5 ) 
		{
			$this->gamestate->nextState( 'gameEndScoring' );
		}
	else
		{
			$iterations++ ;
			self::setGameStateInitialValue( 'iterations', $iterations );
			$this->gamestate->nextState( 'explore' );
		}
	}
////////////////////////////////////////////////////////////////////////////
	function stexplore()
	{
	self::incStat(1, 'cards_drawn' );
	$exploringPlayers = $this->getExploringPlayers();
	for ($i = 1; $i <= 1; $i++)   // silly loop for test, draw more of one card at the time
		{
			
		$TopCard = $this->cards->getCardOnTop( 'deck' ) ; //look at the top card of the deck
			if ( $TopCard['type_arg'] > 0 )  // is it a gems card?
				{
				$gems = $TopCard['type_arg'] % sizeof( $exploringPlayers );  //calculate the remaining gems on the card
				}
			else
				{
				$gems=0;	
				}
		$PlayedCard = $this->cards->pickCardForLocation( 'deck', 'table', $gems ); //  Draw a card
		$gemsSplit = floor( $PlayedCard['type_arg'] / sizeof( $exploringPlayers )); //and gems to split on the players
		foreach($exploringPlayers as $player_id => $player )    // Add gems to the fields
			{  
				$thisid = $player['id'] ;
				$temp = $this->getGemsPlayer( $thisid  , 'field' )  ;
				$this->setGemsPlayer( $thisid , 'field' , $temp + $gemsSplit ) ;
			}
		} 
	$thisTypeid = $PlayedCard['type']; 	
	$cardPlayedName	= $this->card_types[$thisTypeid]['name'];
	if  ( $PlayedCard['type_arg'] > 0) 
	{
		$cardPlayedName = $cardPlayedName ." ". $PlayedCard['type_arg'] ;
	}
	$cardsontable = $this->cards->countCardsInLocation( 'table' );
	self::notifyAllPlayers( "playCard", clienttranslate( 'A new card is drawn: ${card_played_name}' ), array(
                'card_played' => $PlayedCard,
				'card_played_name' => $cardPlayedName
            ) );
	
	
	$HazardsDrawn = self::getCollectionFromDB("SELECT COUNT(*) c FROM cards WHERE card_location ='table' AND card_type > 12 GROUP BY card_type HAVING c > 1 ");
	if (sizeof( $HazardsDrawn )>=1)
		{
			self::notifyAllPlayers( "stcleanpockets", clienttranslate( 'This is the second ${card_played_name} drawn. Players in the temple lost their gems. One card of this kind is removed.' ), array(
                'card_played' => $PlayedCard,
				'card_played_name' => $cardPlayedName
            ) ); 
		$this->cards->moveCard( $PlayedCard['id'], 'temple'  );   // Remove 1 hazard to the temple
		
		$this->gamestate->nextState( 'cleanpockets' );	
		}
	else
		{ 
		if ( $gemsSplit > 0 ) 
			{ 
				self::notifyAllPlayers( "ObtainGems", clienttranslate( 'The loot is divided. All explorers in the temple obtain ${gems} gems.' ), array(
					'gems' => $gemsSplit,
					'card_played' => $PlayedCard,
					'players' => $exploringPlayers
				) );				
			}
		$this->gamestate->nextState( 'vote' );	
		}
	}
////////////////////////////////////////////////////////////////////////////
	function stcleanpockets()
	{
		$sql = "UPDATE player SET player_field= 0 ";  // All players loose their gems
        self::DbQuery( $sql );
		$sql = "SELECT card_id AS id FROM cards WHERE card_location ='table' AND card_type in ( 12,13,14,15,16)";  
		$cards = self::getCollectionFromDB($sql);
		$artifactsOnTable = sizeof($cards);
		self::incGameStateValue( 'artifactspicked', $artifactsOnTable  );		// the 4th and 5th artifacts have bonus	
		$sql = "UPDATE cards SET card_location ='temple' WHERE card_location = 'table' AND card_type in ( 12,13,14,15,16)";
		self::DbQuery( $sql );	            //Remove the artifacts left behind
		
	    $this->gamestate->nextState( 'reshuffle' );	
	}
////////////////////////////////////////////////////////////////////////////
	function stvote()
	{
		    $activePlayersId = array();
			$getExploringPlayers = $this->getExploringPlayers() ;
            /* $players = $this->loadPlayersBasicInfos();*/
			
            foreach($getExploringPlayers as $playerId => $player) 
			{
						$activePlayersId[] = $playerId;
						self::giveExtraTime( $playerId );
            }
            $this->gamestate->setPlayersMultiactive($activePlayersId, 'processleavers');
        
    }
////////////////////////////////////////////////////////////////////////////
	function stprocessLeavers()
	{
		    $players = self::loadPlayersBasicInfos();
			
			$leavingPlayers = $this->getLeavingPlayers() ;
			$leavingPlayersNum = sizeof($leavingPlayers) ;
			
			self::notifyAllPlayers ( "votingend", clienttranslate( 'Voting has ended and ${leavingPlayersNum} players decided to return to camp' ) , 
				    array( 	'leavingPlayersNum' => $leavingPlayersNum 
					) );
			$sql = "SELECT sum( card_location_arg ) gemsonthetable FROM cards WHERE card_location = 'table'";
			$gemsonthetable = self::getUniqueValueFromDB( $sql );
			
			$gemsSplit=0 ;
			if ( $leavingPlayersNum >= 1 )
			{
					$remaininggemsoncard = $gemsonthetable % $leavingPlayersNum;
					$gemsSplit = floor( $gemsonthetable /  $leavingPlayersNum ); //and gems to split on the players
					
					$sql = "UPDATE cards SET card_location_arg = 0 WHERE card_location = 'table'";
					self::DbQuery( $sql );
					$sql = "UPDATE cards SET card_location_arg = $remaininggemsoncard WHERE card_location = 'table' AND card_type < 12 LIMIT 1";
					self::DbQuery( $sql ); 		
			}
			
			foreach($leavingPlayers as $playerId => $player )
			{
				$thisid = $player['id'] ;
				$thisPlayerName = $players[$thisid]['player_name'];
								
				if ( $leavingPlayersNum < 2  )    // pick artifacts
					{
					$sql = "SELECT card_id AS id FROM cards WHERE card_location ='table' AND card_type in ( 12,13,14,15,16)";
					$cards = self::getCollectionFromDB($sql);
					$artifactsOnTable = sizeof($cards);
					
					if ( $artifactsOnTable >0)
						{
							$extra=0;
							self::incGameStateValue( 'artifactspicked', $artifactsOnTable  );
							$artifactspicked=self::getGameStateValue( 'artifactspicked' );
							if ( $artifactspicked == 4  )  
							{
							$extra=5;	
							}
							if ( $artifactspicked == 5 ) 
							{
								if ( $artifactsOnTable == 1 )
								{
									$extra=5;	
								}
								if ( $artifactsOnTable > 1 ) 
								{
									$extra=10;	
								}
							}
							$sql = "UPDATE cards SET card_location ='".$thisid."' WHERE card_location = 'table' AND card_type in ( 12,13,14,15,16)";
							self::DbQuery( $sql );
							
							if ($extra > 0) 
							{
							self::notifyAllPlayers ( "artifactspicked", clienttranslate( '${player_name} is the only player returning to camp this turn and has picked some artifacts, this is the 4th or 5th artifact drawn and obtains ${extra} gems)' ) , 
								array( 'thisid' => $thisid ,
									  'player_name' => $thisPlayerName ,
									  'cards' => $cards,
									  'extra' => $extra
								) );
							}
							else 
							{
								self::notifyAllPlayers ( "artifactspicked", clienttranslate( '${player_name} is the only player returning to camp this turn and has picked some artifacts' ) , 
								array( 'thisid' => $thisid ,
									  'player_name' => $thisPlayerName ,
									  'cards' => $cards,
									  'extra' => $extra
								) );
							}
						}	
					} ;
				$this->setExploringPlayer( $thisid  , 0);
				
				$gems = $this->getGemsPlayer ( $thisid , 'field') ;  
				$gems = $gems + $this->getGemsPlayer ( $thisid , 'tent') + $gemsSplit;
				
				$this->setGemsPlayer ( $thisid , 'tent', $gems );
				$this->setGemsPlayer ( $thisid , 'field', 0 );
				
				self::notifyAllPlayers ( "playerleaving", clienttranslate( '${player_name} returned to camp may grab some gems left on the floor' ) , 
				    array( 'thisid' => $thisid ,
					      'player_name' => $thisPlayerName,
						  'gems' => $gemsSplit 
					) );
				$this->setLeavingPlayer( $thisid  , 0);
		 	}
			
			$exploringPlayers = $this->getExploringPlayers();
			foreach($exploringPlayers as $playerId => $player )
			{
				$thisid = $player['id'] ;
				self::incStat(1, 'cards_seen', $thisid);
				$thisPlayerName = $players[$thisid]['player_name'];
				self::notifyAllPlayers ( "playerexploring", clienttranslate( '${player_name} decided to continue exploring ' ) , 
				    array( 'thisid' => $thisid ,
					      'player_name' => $thisPlayerName 
					) );		
		 	}		
			if ( sizeof( $exploringPlayers ) == 0 )
				{
					
				$this->gamestate->nextState( 'reshuffle' );	
				}
			else
			{
				$this->gamestate->nextState( 'explore' );
			}
	}

////////////////////////////////////////////////////////////////////////////

    function displayScores()
    {
        $players = self::loadPlayersBasicInfos();

        $table[] = array();
        
        //left hand col
        $table[0][] = array( 'str' => ' ', 'args' => array(), 'type' => 'header');
        $table[1][] = $this->resources["gems"    ];
        $table[2][] = $this->resources["artifacts"  ];
        
		$table[3][] = array( 'str' => '<span class=\'score\'>Score</span>', 'args' => array(), 'type' => '');

        foreach( $players as $player_id => $player )
        {
            $table[0][] = array( 'str' => '${player_name}',
                                 'args' => array( 'player_name' => $player['player_name'] ),
                                 'type' => 'header'
                               );
            $table[1][] = $this->getGemsPlayer( $player_id, 'tent' );
            $table[2][] = $this->cards->countCardsInLocation( $player['player_id']);

            $gems = $this->getGemsPlayer( $player_id, 'tent' ) ;
			$artifacts = $this->cards->countCardsInLocation( $player_id );
			
			$score = $gems + 5 * $artifacts ;
			
			self::setStat( $gems , "gems_number", $player_id );
			self::setStat( $artifacts , "artifacts_number", $player_id );
			
			$sql = "UPDATE player SET player_score = ".$score." WHERE player_id=".$player['player_id'];
            self::DbQuery( $sql );
			
			$sql = "UPDATE player SET player_score_aux = ".$this->cards->countCardsInLocation( $player['player_id'])." WHERE player_id=".$player['player_id'];
            self::DbQuery( $sql );
				
            $table[3][] = array( 'str' => '<span class=\'score\'>${player_score}</span>',
                                 'args' => array( 'player_score' => $score ),
                                 'type' => ''
                               );
        }
		self::setStat( self::getGameStateValue( 'artifactspicked') , "artifacts_drawn" );

        $this->notifyAllPlayers( "tableWindow", '', array(
            "id" => 'finalScoring',
            "title" => $this->resources["score_window_title"],
            "table" => $table,
            "header" => '<div>'.$this->resources["win_condition"].'</div>',
			"closing" => clienttranslate( "OK" )
            //"closelabel" => clienttranslate( "Closing button label" )
        ) ); 
    }

////////////////////////////////////////////////////////////////////////////

    function stGameEndScoring()
    {
        //stats for each player, we want to reveal how many gems they have in tent
        //In the case of a tie, check amounts of artifacts. Set auxillery score for this

        //stats first

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
    	
        $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
}
