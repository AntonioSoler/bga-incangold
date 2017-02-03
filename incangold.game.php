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
                "floodType" => 10,
                "floodCardId" => 11,
                "iterations" => 12,
                "gameOverTrigger" => 13,
                "deckSize" => 14,
                "plagueTrigger" => 15,
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
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // setup the initial game situation here

        self::setGameStateInitialValue( 'iterations', 0 ); //times deck has been exhausted


        //create the card deck here. There are 92 cards
        //(16 of each of the 5 types) = 80
        //10 speculation cards = 90
        //1xplague, 1xflood = 92
        $cards = array();
        $plagueType = 0;
        foreach( $this->card_types as $cardType)
        {
            if ($cardType['isHazard'] == 1)
            {
                $plagueType = $cardType["type_id"];
                $num = 1;
            }
            else if ($cardType['isScore'] == 1)
            {
                $num = 1;
            }
            else
            {
                $num = 16;
            }
            $card = array( 'type' => $cardType["type_id"], 'type_arg' => 0, 'nbr' => $num);
            array_push($cards, $card);
        }
        
        $this->cards->createCards( $cards, 'deck' );
        self::setGameStateInitialValue( 'deckSize', $this->cards->countCardsInLocation("deck"));

        //shuffle and deal each player 5 cards
        $this->cards->shuffle( 'deck' );

        //init stats for all players
        self::initStat('player', 'papyrus_number', 0);
        self::initStat('player', 'wheat_number', 0);
        self::initStat('player', 'lettuce_number', 0);
        self::initStat('player', 'castor_number', 0);
        self::initStat('player', 'flax_number', 0);

        $players = self::loadPlayersBasicInfos();

        //to avoid any player drawing the plague card in their initial hand, move it to discard before drawing, then reshuffle it in afterwards
        $plagueCards = $this->cards->getCardsOfType($plagueType);
        $this->cards->moveCards($this->getCardIds($plagueCards), "discard");

        foreach( $players as $player_id => $player )
        {
            $this->cards->pickCards( 5, 'deck', $player_id );
        }  

        $this->cards->moveCards($this->getCardIds($plagueCards), "deck");
        $this->cards->shuffle('deck');

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

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
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // Gather all information about current game situation (visible by player $current_player_id).
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );
        $result['storage'] = $this->cards->getCardsInLocation( 'storage', $current_player_id );

        //flood is visible
        //show number of cards in deck too.
        $result['cardsRemaining'] = $this->cards->countCardsInLocation('deck');
        $result['shufflesRemaining'] = count($players) - $this->getGameStateValue('iterations')-1;
        $result['floodHarvestTypes'] = $this->card_types[self::getGameStateValue( 'floodType')]['harvestTypes'];
        $result['floodType'] = self::getGameStateValue( 'floodType');
        $result['floodCardId'] = self::getGameStateValue( 'floodCardId');
        $result['cardTypeCount'] = count($this->card_types);
        
        //is the plague card still in the deck
        $cards = $this->cards->getCardsOfType( 16);
        $plague = reset($cards);
        $result['isHazardInDeck'] = $plague["location"] == "deck";
                
        //fields of all players are visible
        $result['fields'] = array();        
        foreach( $players as $player_id => $player )
        {
            $result['fields'][$player_id] = $this->cards->getCardsInLocation( 'field', $player_id );
        }      

        //tell the client which card type IDs are speculation cards, so it can validate player choices.
        $result['speculationTypes'] = array();
        foreach($this->card_types as $cardType)
        {
            if ($cardType['isScore'] == 1)
            {
                $result['speculationTypes'][] = $cardType['type_id'];
            }
        }

        $result['harvestTypes'] = array();
        foreach($this->card_types as $cardType)
        {
            $result['harvestTypes'][$cardType['type_id']] = $cardType['harvestTypes'];
        }
  
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
        //r = cards remaining in deck
        //r* = max possible remaining cards from start of game (approximate at 92 more cards per iteration)
        //c* = max possible remaining cards from current position
        //i = completed iterations
        //p = progress (0-100)
        //n = number of players
        //p=0 at i=0 & r = 92-5n ==> p=0 at r* = 92(n-1)+92-5n
        //p=100 at r*=0
        //p=100 at i=(n-1) r = 0

        //p = 100*(r*-c*)/r*
        $deckSize = self::getGameStateValue( 'deckSize');
        $iterations = self::getGameStateValue("iterations");
        $playerCount = count(self::loadPlayersBasicInfos());
        $initialRemaining = ($deckSize-5*$playerCount)*$playerCount;
        //we use an approximation here... that only cards in deck & discard/flood will be in the next reshuffle.
        //This is not 100% true (discard, plague etc
        $currentRemaining = ($this->cards->countCardsInLocation('deck')+$this->cards->countCardsInLocation('discard')+$this->cards->countCardsInLocation('flood'))*($playerCount-1-$iterations) + $this->cards->countCardsInLocation('deck');

        return 100*($initialRemaining-$currentRemaining)/$initialRemaining;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getCardIds($cards)
    {
        $cardIds = array();
        foreach($cards as $card)
        {
            $cardIds[] = $card['id'];
        }
        return $cardIds;
    }

    function isScoreCard($card)
    {
        $cardType = $this->card_types[$card['type']];
        return $cardType['isScore'] == 1;
    }

    function validateCardCount($card_ids, $count)
    {
        if (count($card_ids) != $count) {
            throw new feException("2 cards are required");
        }
    }

    function validatePlayerHasCards($card_ids, $allowStorage)
    {
        $cards = $this->cards->getCards( $card_ids );

        $active_player = self::getActivePlayerId();
        
        foreach( $cards as $card )
        {
            $isInHand = $card['location'] == 'hand' && $card['location_arg'] == $active_player;
            $isInStorage = $card['location'] == 'storage' && $card['location_arg'] == $active_player;
            if ($allowStorage)
            {
                if (!$isInHand && !$isInStorage)
                {
                    throw new feException("These cards are not in your hand or storage");
                }
            }
            else //don't allow storage, hand only
            {
                if (!$isInHand)
                {
                    throw new feException("These cards are not in your hand");
                }
            }                    
        }
    }

    function discardCards($card_ids)
    {
        $this->cards->moveCards( $card_ids, "discard");

        $active_player = self::getActivePlayerId();

        //notify this player so the cards can be removed from screen
        self::notifyPlayer( $active_player, "removeCards", "", array( "cards" => $card_ids ) );
    }

    function drawCardsWithGameOverCheck($numCards, $source, $destination, $dest_type, $isPlayerDraw)
    {
        $players = self::loadPlayersBasicInfos();

        $returnCards = array();

        while ($numCards > 0 && $this->cards->countCardInLocation('deck') > 0)
        {
            //draw as many cards as you can.
            $numToDraw = min($numCards, $this->cards->countCardInLocation('deck'));
            $newCards = $this->cards->pickCardsForLocation($numToDraw, $source, $destination, $dest_type, true);
                                    
            //announce the drawn cards.
            if ($isPlayerDraw)
            {
                self::notifyAllPlayers( "drewCards", clienttranslate( '${player_name} drew ${num} card(s)' ), array(
                    'player_name' => $players[$dest_type]['player_name'],
                    'num' => count($newCards),
                    'cardsRemaining' => $this->cards->countCardsInLocation('deck'),
                    'shufflesRemaining' => count(self::loadPlayersBasicInfos()) - $this->getGameStateValue('iterations') - 1
                ) );

                //notify this player so the cards can be added to their hand
                self::notifyPlayer( $players[$dest_type]['player_id'], "drawCards", "", array( "cards" => $newCards ) );
            }
            //update return card list and card to draw count.
            $numCards -= count($newCards);
            $returnCards = array_merge($returnCards, $newCards);

            //were any the plague card?
            foreach($newCards as $card)
            {
                if ($this->card_types[$card['type']]['isHazard'] == 1)
                {
                    self::setGameStateValue("plagueTrigger", 1);
                    
                    if ($isPlayerDraw)
                    {
                        self::notifyAllPlayers( "plagueDrawn", clienttranslate( '${player_name} has drawn The Plague Of Locusts' ), array(
                            'plagueCardId' => $card['id'],
                            'plagueType' => $card['type'],
                            'draw_type' => 'player',
                            'player_id' => $players[$dest_type]['player_id'],
                            'player_name' => $players[$dest_type]['player_name'],
                            'cardsRemaining' => $this->cards->countCardsInLocation('deck'),
                            'shufflesRemaining' => count(self::loadPlayersBasicInfos()) - $this->getGameStateValue('iterations') - 1
                        ) );
                    }
                    else
                    {      
                        self::notifyAllPlayers( "plagueDrawn", clienttranslate( 'The Plague Of Locusts has been revealed' ), array(
                            'plagueCardId' => $card['id'],
                            'plagueType' => $card['type'],
                            'draw_type' => 'deck',
                            'cardsRemaining' => $this->cards->countCardsInLocation('deck'),
                            'shufflesRemaining' => count(self::loadPlayersBasicInfos()) - $this->getGameStateValue('iterations') - 1
                        ) );
                    }

                    $this->cards->moveCard( $card['id'], "discard");

                    $numCards++; //you need to draw 1 more
                }
            }

            if ($this->cards->countCardsInLocation('deck') == 0)
            {
                if (self::getGameStateValue("iterations") == count($players)-1)
                {
                    //game over!
                    self::notifyAllPlayers( "reshuffle", clienttranslate( 'The deck has been exhausted. The game ends' ), array(
                        'cardsRemaining' => 0,
                        'shufflesRemaining' => 0
                    ) );

                    self::setGameStateValue("gameOverTrigger", 1);
                    return;
                }
                else
                {
                    $this->cards->moveAllCardsInLocation( 'discard', 'deck');
                    $this->cards->moveAllCardsInLocation('flood', 'deck');
                    $this->cards->shuffle('deck');
                    self::setGameStateValue("iterations", self::getGameStateValue("iterations")+1);
    
                    self::notifyAllPlayers( "reshuffle", clienttranslate( 'The deck has been exhausted. Discard pile and flood stack have been reshuffled to form a new deck' ), array(
                        'cardsRemaining' => $this->cards->countCardsInLocation('deck'),
                        'shufflesRemaining' => count(self::loadPlayersBasicInfos()) - $this->getGameStateValue('iterations') - 1
                    ) );
                }
            }
        }

        return $returnCards;
    }

    function drawCards($playerID, $numCards)
    {
        self::debug("drawCards".$numCards);
        self::debug("cards left in deck : ".$this->cards->countCardInLocation('deck'));

        $this->drawCardsWithGameOverCheck($numCards, 'deck', 'hand', $playerID, true);
    }

    function getFieldCount($player_id, $field_type)
    {
        return count($this->getCardsInLocationByType('field', $player_id, $field_type));
    }

    function getCardsInLocationByType($location, $location_arg, $type)
    {
        $matches = array();

        $cards = $this->cards->getCardsInLocation($location, $location_arg);
        foreach($cards as $card)
        {
            if ($card['type'] == $type)
            {
                array_push($matches, $card);
            }
        }
        return $matches;
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

    function pass()
    {
        self::checkAction( "pass" );

        $this->gamestate->nextState( 'pass' );
    }

    function market($card_ids)
    {
        self::checkAction( "market" );

        $this->validateCardCount($card_ids, 2);
        $this->validatePlayerHasCards($card_ids, true);

        self::notifyAllPlayers( "marketUsed", clienttranslate( '${player_name} used the market' ), array(
            'player_name' => self::getActivePlayerName(),
        ) );

        //move the cards
        $this->discardCards($card_ids);

        //draw a card
        $this->drawCards(self::getActivePlayerId(), 1);
        if (self::getGameStateValue("gameOverTrigger") == 1)
        {
            $this->gamestate->nextState( 'gameEnd' );
            return;
        }
        else if (self::getGameStateValue("plagueTrigger") == 1)
        {
            $this->gamestate->nextState( 'plague' );
            return;
        }

        $this->gamestate->nextState( 'market' );
    }

    function offering($card_ids)
    {
        self::checkAction( "offering" );

        $this->validateCardCount($card_ids, 2);
        $this->validatePlayerHasCards($card_ids, true);
        
        self::notifyAllPlayers( "offeringMade", clienttranslate( '${player_name} made an offering to Hapi' ), array(
            'player_name' => self::getActivePlayerName(),
        ) );

        //move the cards
        $this->discardCards($card_ids);
        
        $this->gamestate->nextState( 'offering' );
    }

    function plant($card_ids)
    {
        self::checkAction( "plant" );

        $this->validatePlayerHasCards($card_ids, false);

        /*When planting a new field, players may:
        1) Plant at least two cards of all the same crop type.
        2) Plant exactly two cards of differing crops (one of which may be planted into an existing field).
        3) Plant any number of crops to any number of fields that already exist in front of you.
        */
        //any new fields of a type MUST have more cards in than an opponent, and will cause the opponents crop to be discarded

        $players = self::loadPlayersBasicInfos();

        //first, make an array of different types of cards played, and how many of each
        $cards = $this->cards->getCards( $card_ids );
        $types = array();
        foreach($cards as $card)
        {
            if (array_key_exists($card['type'], $types))
            {
                $types[$card['type']]++;
            }
            else
            {
                $types[$card['type']] = 1;
            }
        }

        //which play type is it?
        //if all the cards types played exist in fields owned by this player, it is option 3
        //else if only one type has been played and it contains 2+ cards, it is option 1        
        //else if exactly two different cards have been played, then it is option 2
        $allExistingFields = true;
        
        foreach($types as $key => $numPlayed)
        {
            if ($this->getFieldCount(self::getCurrentPlayerId(), $key) == 0)
            {
                $allExistingFields = false;
            }
        }

        //validation part 1 - invalid cards: flooded/spec
        $floodType = self::getGameStateValue('floodType');
        foreach($cards as $card)
        {
            if ($this->isScoreCard($card))
            {
                throw new feException("Cannot plant a speculation card");
            }
            if ($card['type'] == $floodType || in_array($card['type'], $this->card_types[$floodType]['harvestTypes']))
            {
                throw new feException("Cannot plant a crop type that is flooded");
            }
        }

        //Validation part 2 - number and types of cards played:
        

        if ($allExistingFields)
        {
            self::debug("Option 3 - adding to existing fields");
        }
        else //planting new fields. Either 2+ the same type, or exactly two cards of different types.
        {
            if (count($card_ids) < 2)
            {
                throw new feException("When creating new fields, two or more cards must be played");
            }

            if (count($types) > 2)
            {
                throw new feException("When creating new fields, you must supply exactly two cards of different types, or cards of one type only");
            }

            if (count($types) == 2 && count($card_ids) != 2)
            {
                throw new feException("When creating new fields, you must supply exactly two cards of different types, or cards of one type only");
            }  
        }

        //validate part 3 - any new fields must be bigger than opposing fields of the same type
        foreach($types as $key => $numPlayed)
        {
            foreach($players as $player)
            {   
                if ($player['player_id'] != self::getActivePlayerId() && $this->getFieldCount($player['player_id'], $key) >= $numPlayed)
                {
                    throw new feException($player['player_name']." has a field of equal or greater size already");
                }
            }
        }

        //validation passed!
        
        //notify players of additions to existing fields
        foreach($types as $key => $numPlayed)
        {
            $cardsToAdd = array();
            foreach($cards as $card)
            {
                if ($card['type'] == $key)
                {
                    array_push($cardsToAdd, $card);
                }
            }
            if ($this->getFieldCount(self::getActivePlayerId(), $key) > 0)
            {
                self::notifyAllPlayers( "addToField", clienttranslate( '${player_name} adds ${numPlayed} ${resourceName} to their existing field' ), array(
                    'resourceName' => $this->card_types[$key]["name"],
                    'numPlayed' => $numPlayed,
                    'cards' => $cardsToAdd,
                    'playerId' => self::getActivePlayerId(),
                    'player_name' => $players[self::getActivePlayerId()]['player_name']
                    ) );
            }
        }

        //nofify players of new fields (and removed fields)
        //notify destroyed fields
        foreach($types as $key => $numPlayed)
        {
            $cardsToAdd = array();
            foreach($cards as $card)
            {
                if ($card['type'] == $key)
                {
                    array_push($cardsToAdd, $card);
                }
            }

            if ($this->getFieldCount(self::getActivePlayerId(), $key) == 0)
            {
                self::notifyAllPlayers( "addToField", clienttranslate( '${player_name} plays ${numPlayed} ${resourceName} in a new field' ), array(
                    'resourceName' => $this->card_types[$key]["name"],
                     'numPlayed' => $numPlayed,
                     'cards' => $cardsToAdd,
                     'playerId' => self::getActivePlayerId(),
                     'player_name' => $players[self::getActivePlayerId()]['player_name']
                    ) );

                //did you kill anyone else's field?
                foreach($players as $player)
                {
                    if ($this->getFieldCount($player['player_id'], $key) > 0)
                    {
                        //which cards?
                        $cards = $this->getCardsInLocationByType('field', $player['player_id'], $key);

                        self::notifyAllPlayers( "destroyField", clienttranslate( '${player_name}\'s ${resourceName} field is destroyed'), array(
                            'resourceName' => $this->card_types[$key]["name"],
                            'cards' => $cards,
                            'playerId' => $player['player_id'],
                            'player_name' => $players[$player['player_id']]['player_name']
                        ) ); 

                        $cardIdsToDiscard = $this->getCardIds($cards);

                        $this->cards->moveCards( $cardIdsToDiscard, "discard");                    
                    }
                }
            }
        }

        //update moved cards
        $this->cards->moveCards( $card_ids, "field", self::getActivePlayerId());
        
        $this->gamestate->nextState( 'plant' );
    }

    function speculate($card_ids)
    {
        self::checkAction( "speculate" );

        //validation - invalid cards: non-spec
        $cards = $this->cards->getCards( $card_ids );
        if (count($cards) > 2)
        {
            throw new feException("You may play only one or two speculation cards");
        }

        //validation part 2 - invalid or flooded
        $floodType = self::getGameStateValue('floodType');
        foreach($cards as $card)
        {
            if (!$this->isScoreCard($card))
            {
                throw new feException("Only speculation cards can be used to Speculate");
            }
            foreach($this->card_types[$floodType]["harvestTypes"] as $harvestType)
            {
                if (in_array($harvestType, $this->card_types[$card['type']]["harvestTypes"]))
                {
                    throw new feException("Cannot speculate on a crop type that is flooded");
                }
            }
        }

        
        $this->cards->moveCards( $card_ids, "field", self::getActivePlayerId());

        foreach($cards as $card)
        {
            self::notifyAllPlayers( "addToField", clienttranslate( '${player_name} speculates on ${name}' ), array(
                'name' => $this->card_types[$card['type']]["name"],
                'numPlayed' => 1,
                'cards' => array($card),
                'playerId' => self::getActivePlayerId(),
                'player_name' => self::getActivePlayerName()
            ) );
        }

        $this->gamestate->nextState( 'speculate' );
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

    function stFlood()
    {
        //draw the next card from the deck. Display as the flood card
        //could trigger end of game
        self::debug("stFlood");
        self::debug("cards left in deck : ".$this->cards->countCardInLocation('deck'));

        $newCards = $this->drawCardsWithGameOverCheck(1, 'deck', 'flood', 0, false);
        if (self::getGameStateValue("gameOverTrigger") == 1)
        {
            $this->gamestate->nextState('gameEnd');
            return;
        }
        else if (self::getGameStateValue("plagueTrigger") == 1)
        {
            $this->gamestate->nextState( 'plague' );
            return;
        }
        
        //$newCard = $this->cards->pickCardForLocation( 'deck', 'floodCards');
        //throw new feException(var_dump($newCard));
        foreach($newCards as $newCard) //only one
        {         
            $floodType = $newCard['type'];
            $floodCardId = $newCard['id'];
        }

        self::setGameStateValue( 'floodType', $floodType);
        self::setGameStateValue( 'floodCardId', $floodCardId);
        self::debug("set flood type : ".$floodType);
        self::debug("set flood card id : ".$floodCardId);
        
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( "flood", clienttranslate( '${resourcename} is flooded' ), array(
            'resourcename' => $this->card_types[$floodType]["name"],
            'floodHarvestTypes' => $this->card_types[$floodType]['harvestTypes'],
            'floodType' => $floodType,
            'floodCardId' => $floodCardId,
            'cardsRemaining' => $this->cards->countCardsInLocation('deck'),
            'shufflesRemaining' => count($players) - $this->getGameStateValue('iterations') - 1
        ) );

        //get a list of speculation types which were successful
        //iterate through types/players giving out bonus
        //get remaining speculation cards for all players and discard

        $successfulTypes = array();
        self::debug("spec check");
        foreach($this->card_types[$floodType]["harvestTypes"] as $harvestType)
        {
            self::debug("harvest type ".$harvestType);
            foreach($this->card_types as $cardType)
            {
                if (in_array($harvestType, $cardType["harvestTypes"]) && $cardType["isScore"] == 1)
                {
                    self::debug("match");
                    $successfulTypes[] = $cardType["type_id"];
                }
            }
        }
        self::debug("spec check done");

        foreach ($players as $player)
        {
            $numToDraw = 0;

            foreach ($successfulTypes as $type)
            {
                $cards = $this->getCardsInLocationByType( 'field', $player['player_id'], $type);
                foreach($cards as $card)
                {
                    self::notifyAllPlayers( "speculateSuccess", clienttranslate( '${player_name} correctly speculates on ${resourcename}' ), array(
                        'resourcename' => $this->card_types[$floodType]["name"],
                        'player_name' => $player['player_name'],
                        'playerId' => $player['player_id'],
                        'card' => $card,
                    ) );
                    
                    $this->cards->moveCard( $card['id'], "discard");
                    
                    $numToDraw += 3;
                }
            }

            if ($numToDraw > 0)
            {
                $this->drawCards($player['player_id'], $numToDraw);
                if (self::getGameStateValue("gameOverTrigger") == 1)
                {
                    $this->gamestate->nextState( 'gameEnd' );
                    return;
                }
                if (self::getGameStateValue("plagueTrigger") == 1)
                {
                    $this->gamestate->nextState( 'plague' );
                    return;
                }
            }
        }

        //discard any incorrect speculation cards
        foreach ($players as $player)
        {
            $toDiscard = array();
            $cardsToDiscard = array();

            foreach ($this->card_types as $cardType)
            {
                if ($cardType['isScore'] == 1)
                {
                    $cards = $this->getCardsInLocationByType( 'field', $player['player_id'], $cardType['type_id']);
                    foreach($cards as $card)
                    {
                        array_push($toDiscard, $card['id']);
                        array_push($cardsToDiscard, $card);
                    }
                }
            }

            if (count($toDiscard) > 0)
            {
                $this->cards->moveCards( $toDiscard, "discard");

                self::notifyAllPlayers( "speculateFail", clienttranslate( '${player_name} discards failed speculation cards' ), array(
                    'player_name' => $player['player_name'],
                    'playerId' => $player['player_id'],
                    'cards' => $cardsToDiscard,
                ) );
            }
        }

        //next state
        $this->gamestate->nextState('harvest');
    }

    function stHarvest()
    {
        $floodType = self::getGameStateValue( 'floodType');
        $harvestTypes = $this->card_types[$floodType]["harvestTypes"];

        //add card to storage based on current flood card
        $anyHarvested = false;
        $players = self::loadPlayersBasicInfos();
        foreach($harvestTypes as $harvestType)
        {
            $harvested = false;
            foreach($players as $player)
            {
                if ($this->getFieldCount($player['player_id'], $harvestType) > 0) //each flooded type can be harvested only one.
                {
                    //harvest! find the card
                    $cards = $this->getCardsInLocationByType( 'field', $player['player_id'], $harvestType);
                    foreach($cards as $card)
                    {
                        if (!$harvested)
                        {
                            self::notifyAllPlayers( "harvest", clienttranslate( '${player_name} harvests ${resourcename}' ), array(
                                'resourcename' => $this->card_types[$harvestType]["name"],
                                'player_name' => $player['player_name'],
                                'playerId' => $player['player_id'],
                                'card' => $card
                            ) );
        
                            $harvested = true;
                            $anyHarvested = true;
                            $this->cards->moveCard( $card['id'], "storage", $player['player_id']);    
                        }
                    }               
                }
            }
        }

        if (!$anyHarvested)
        {
            self::notifyAllPlayers( "harvest", clienttranslate( 'Nobody harvests ${resourcename}' ), array(
                'resourcename' => $this->card_types[$floodType]["name"],
            ) );
        }
        
        //next state
        $this->gamestate->nextState('');
    }

    function stPlague()
    {
        self::setGameStateValue("plagueTrigger", 0);
        
        //a bit like a harvest gone wrong.

        $max = 0;
        $players = self::loadPlayersBasicInfos();
        foreach($players as $player)
        {
            foreach($this->card_types as $cardType)
            {
                if ($cardType['isScore'] == 0 && $cardType['isHazard'] == 0)
                {
                    $count = $this->getFieldCount($player['player_id'], $cardType['type_id']);
                    $max = max($max, $count);
                }
            }
        }

        if ($max > 0)
        {
            foreach($players as $player)
            {
                foreach($this->card_types as $cardType)
                {
                    $count = $this->getFieldCount($player['player_id'], $cardType['type_id']);
                    if ($cardType['isScore'] == 0 && $cardType['isHazard'] == 0 && $count == $max)
                    {
                        $cards = $this->getCardsInLocationByType('field', $player['player_id'], $cardType['type_id']);

                        self::notifyAllPlayers( "destroyField", clienttranslate( 'The Plague of Locusts wipes out the ${resource_name} field of ${player_name}' ), array(
                            'resource_name' => $cardType["name"],
                            'playerId' => $player['player_id'],
                            'player_name' => $player['player_name'],
                            'cards' => $cards,
                        ) );

                        $cardIdsToDiscard = $this->getCardIds($cards);

                        $this->cards->moveCards( $cardIdsToDiscard, "discard");                    

                    }
                }
            }
        }
        else
        {
            self::notifyAllPlayers( "plagueFail", clienttranslate( 'The Plague of Locusts has no effect' ), array() );
        }

        self::notifyAllPlayers( "plagueEnd", "", array() );

        $this->gamestate->nextState('');
    }

    function stDrawCards()
    {
        //add two cards to the player's hand.
        $this->drawCards(self::getActivePlayerId(), 2);
        if (self::getGameStateValue("gameOverTrigger") == 1)
        {
            $this->gamestate->nextState( 'gameEnd' );
            return;
        }
        else if (self::getGameStateValue("plagueTrigger") == 1)
        {
            $this->gamestate->nextState( 'plague' );
            return;
        }

        //next state
        $this->gamestate->nextState('nextPlayer');
    }

    function stNextPlayer()
    {
        //set the next active player
        $this->activeNextPlayer();

        self::giveExtraTime(self::getCurrentPlayerId());

        //next state
        $this->gamestate->nextState('');
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
        $table[1][] = $this->resources["papyrus"];
        $table[2][] = $this->resources["wheat"];
        $table[3][] = $this->resources["lettuce"];
        $table[4][] = $this->resources["castor"];
        $table[5][] = $this->resources["flax"];
        $table[6][] = array( 'str' => '<span class=\'score\'>Score</span>', 'args' => array(), 'type' => '');

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
