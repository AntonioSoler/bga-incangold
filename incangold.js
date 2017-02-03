/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * incangold implementation : © Antonio Soler <morgald.es@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * incangold.js
 *
 * incangold user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/stock",
    "ebg/counter",
	"ebg/zone" 
],
function (dojo, declare) {
    return declare("bgagame.incangold", ebg.core.gamegui, {
        constructor: function(){
            console.log('incangold constructor');
            this.cardWidth = 75;
            this.cardHeight = 159;
            this.cardsPerRowInImg = 8;
			
			// Zone control        	
			this.myZone = new ebg.zone();

            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
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
        
        setup: function( gamedatas ) {
            console.log("Starting game setup");
            console.log(gamedatas);

            //text resources go here
            this.resources = new Object;
            this.resources.hand = _("Hand");
            this.resources.storage = _("Storage");
            this.resources.pass = _("Pass");
            this.resources.shufflesRemaining = _("Reshuffles: ");
            this.resources.cardsRemaining = _("Cards: ");
            this.resources.noCardError = _("You have not selected any cards");

            
            this.harvestTypes = gamedatas.harvestTypes;
            console.log("harvest Types:");
            console.log(this.harvestTypes);
            console.log("end harvest Types:");
            this.speculationTypes = gamedatas.speculationTypes;
            this.cardTypeCount = gamedatas.cardTypeCount;

            //any text to render
            $('txtHand').innerHTML = this.resources.hand;
            $('txtStorage').innerHTML = this.resources.storage;
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // Setting up players boards if needed
            }
            
            // Set up your game interface here, according to "gamedatas"

            //draw pile and flood
            this.setCardsRemaining(this.gamedatas.cardsRemaining, this.gamedatas.shufflesRemaining);
            
            //plague icon
            this.addTooltip("plagueToken", this.resources.strPlagueTooltip, '');
            if (!gamedatas.isHazardInDeck)
            {
                dojo.addClass("plagueToken", "hidden");
            }
            
            //draw deck cover for draw pile
            this.placeCard(gamedatas.cardTypeCount + 1, 0, 'drawPile', 0);

            //draw flood marker card for flood pile
            this.placeCard(0, -1, 'flood', 0);
            
            //place current flood card
            this.placeCard(gamedatas.floodType, gamedatas.floodCardId, 'drawPile', 1);
            
            this.slideToObject('card_' + gamedatas.floodCardId, 'flood', 500, 0).play();
            this.floodCardId = gamedatas.floodCardId;
            this.floodCardType = gamedatas.floodType;
            this.floodHarvestTypes = gamedatas.floodHarvestTypes;

            var hand = gamedatas.hand;
            console.log(hand);

            // Player hand
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('playerHand'), this.cardWidth, this.cardHeight);
            this.playerHand.image_items_per_row = this.cardsPerRowInImg;

            // Create cards types:
            for (var color = 1; color <= this.cardTypeCount; color++) {
                var card_type_id = color;
                this.playerHand.addItemType( card_type_id, card_type_id, g_gamethemeurl+'img/cards.jpg', card_type_id );
            }

            //Cards in player's hand
            for (var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                this.playerHand.addToStockWithId(card.type, card.id);
            }

            // Player storage
            this.playerStorage = new ebg.stock();
            this.playerStorage.create(this, $('playerStorage'), this.cardWidth, this.cardHeight);
            this.playerStorage.image_items_per_row = this.cardsPerRowInImg;
            
            // Create cards types:
            for (var color = 1; color <= this.cardTypeCount; color++) {
                var card_type_id = color;
                this.playerStorage.addItemType(card_type_id, card_type_id, g_gamethemeurl + 'img/cards.jpg', card_type_id);
            }

            //Cards in player's storage
            for (var i in this.gamedatas.storage) {
                var card = this.gamedatas.storage[i];
                this.playerStorage.addToStockWithId(card.type, card.id);
            }

            //let's try a different approach... one stock per field, overlap display type
            console.log("Creating field stocks");
            this.playerFields = [];
            for (var playerID in this.gamedatas.fields) {
                for (var type = 1; type <= this.cardTypeCount; type++) {
                    this.playerFields.push({
                        player_id: playerID,
                        type: type,
                        fields: new ebg.stock()
                    });
                    this.playerFields[this.playerFields.length - 1].fields.create(this, $('playerFields_' + playerID + "_" + type), this.cardWidth, this.cardHeight);
                    this.playerFields[this.playerFields.length - 1].fields.image_items_per_row = this.cardsPerRowInImg;
                    for (var color = 1; color <= this.cardTypeCount; color++) {
                        this.playerFields[this.playerFields.length - 1].fields.addItemType(color, color, g_gamethemeurl + 'img/cards.jpg', color);
                        this.playerFields[this.playerFields.length - 1].fields.setOverlap(10,0);
                        this.playerFields[this.playerFields.length - 1].fields.setSelectionMode(0);
                    }
                }
            }

            console.log("adding cards to the fields " + this.playerFields.length);

            //add the cards to all the fields
            for (var playerID in this.gamedatas.fields) {
                for (var cardID in this.gamedatas.fields[playerID]) {
                    this.addCardToField(this.gamedatas.fields[playerID][cardID], playerID);
                }
            }

            console.log("fields:");
            console.log(this.gamedatas.fields);
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log('Entering state: ' + stateName);

            this.playerHand.setSelectionMode(0);
            this.playerStorage.setSelectionMode(0);
            this.playerHand.unselectAll();
            this.playerStorage.unselectAll();
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
                case 'playerTrade':
                    this.playerHand.setSelectionMode(2);
                    this.playerStorage.setSelectionMode(2);
                    break;
                case 'playerPlantOrSpeculate':
                    this.playerHand.setSelectionMode(2);
                    break;
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
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
                    case 'playerTrade':
                        this.addActionButton('offering', this.resources.makeOffering, 'onOffering');
                        this.addActionButton('market', this.resources.market, 'onMarket');
                        this.addActionButton('pass', this.resources.pass, 'onPass');
                        break;

                    case 'playerPlantOrSpeculate':
                        this.addActionButton('plant', this.resources.plant, 'onPlant');
                        this.addActionButton('speculate', this.resources.speculate, 'onSpeculate');
                        this.addActionButton('pass', this.resources.pass, 'onPass');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        placeCard: function (cardTypeId, cardId, destination, zIndex) {
            var xOffset = cardTypeId % this.cardsPerRowInImg;
            var yOffset = Math.floor(cardTypeId / this.cardsPerRowInImg);
            dojo.place(
                this.format_block('jstpl_cardontable', {
                    height: this.cardHeight,
                    width: this.cardWidth,
                    x: this.cardWidth * xOffset,
                    y: this.cardHeight * yOffset,
                    z: zIndex,
                    card_id: cardId
                }), destination);
        },

        getFieldCount: function(player_id, fieldType) {
            for (var i = 0; i < this.playerFields.length; i++) {
                if (player_id == this.playerFields[i].player_id && fieldType == this.playerFields[i].type) {
                    return this.playerFields[i].fields.count();
                }
            }
            return 0;
        },

        setCardsRemaining: function (cardCount, iterationCount) {
            $('iterationsText').innerHTML = this.resources.shufflesRemaining + iterationCount;
            $('remainingText').innerHTML = this.resources.cardsRemaining + cardCount;
        },

        //if the card id exists in players hand, it's their card so move it from hand to field & nuke card in hand
        //otherwise, draw it from overall player board to the field
        addCardToField: function (card, player_id)
        {
            //move from hand to field
            for (var i = 0; i < this.playerFields.length; i++) {

                if (player_id == this.playerFields[i].player_id && card.type == this.playerFields[i].type) {
                    console.log("found matching playerfield");

                    if ($('playerHand_item_' + card.id)) {
                        console.log("card is in hand, adding to field stock");
                        this.playerFields[i].fields.addToStockWithId(card.type, card.id, 'playerHand_item_' + card.id);
                        this.playerHand.removeFromStockById(card.id);
                    } else {
                        console.log("card is not in hand, adding to overall board before moving")
                        this.placeCard(card.type, card.id, 'overall_player_board_' + player_id, 0);
                        this.playerFields[i].fields.addToStockWithId(card.type, card.id, 'card_' + card.id);
                        dojo.destroy('card_' + card.id);
                    }

                    var width = 0;
                    if (this.playerFields[i].fields.count() > 0) {
                        width = (this.playerFields[i].fields.count() * 15 + this.cardWidth + 5);
                    }
                    dojo.style('playerFields_' + this.playerFields[i].player_id + "_" + card.type, "width", width + "px");                    
                    //dojo.animateProperty({
                    //    node: 'playerFields_' + this.playerFields[i].player_id + "_" + card.type,
                    //    properties: {
                    //        width: width
                    //    },
                    //    duration: 500
                    //}).play();
                    //this.playerFields[i].fields.resetItemsPosition();
                }
            }
        },
        removeCardFromField: function (card, player_id, isHarvest) {
            console.log("removeCardFromField");
            console.log(card);
            console.log(player_id);
            //if the harvest was for the current player, move card from field to storage
            //if the harvest was for an opponent, move card from field to overall game board (then destroy)
            for (var i = 0; i < this.playerFields.length; i++) {

                if (player_id == this.playerFields[i].player_id && card.type == this.playerFields[i].type) {

                    if (this.player_id == player_id) {
                        if (isHarvest) {
                            this.playerStorage.addToStockWithId(card.type, card.id, 'playerFields_' + player_id + '_' + card.type);
                        }
                        this.playerFields[i].fields.removeFromStockById(card.id);
                    } else {
                        if (isHarvest) {
                            this.placeCard(card.type, card.id, 'playerFields_' + player_id + '_' + card.type, 0);
                            this.placeOnObjectPos('card_' + card.id, 'playerFields_' + player_id + '_' + card.type);
                            this.slideToObjectAndDestroy('card_' + card.id, 'overall_player_board_' + player_id, 1000, 0);
                        }
                        this.playerFields[i].fields.removeFromStockById(card.id);
                    }

                    var width = 0;
                    if (this.playerFields[i].fields.count() > 0) {
                        width = (this.playerFields[i].fields.count() * 15 + this.cardWidth + 5);
                    }
                    //dojo.style('playerFields_' + this.playerFields[i].player_id + "_" + card.type, "width", width + "px");
                    dojo.animateProperty({
                        node: 'playerFields_' + this.playerFields[i].player_id + "_" + card.type,
                        properties: {
                            width: width
                        },
                    }).play();

                }
            }
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
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/incangold/incangold/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        onPass: function( evt )
        {
            console.log( 'pass' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if (! this.checkAction('pass')) {
                return;
            }

            this.ajaxcall("/incangold/incangold/pass.html",
                { lock: true, },
                this,
                function (result) { },
                function (is_error) { }
                );
        },

        onMarket: function (evt) {
            console.log('market');

            // Preventing default browser reaction
            dojo.stopEvent(evt);

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if (!this.checkAction('market')) {
                return;
            }

            //you must have two cards selected from your hand/storage in order to use the market
            var handCards = this.playerHand.getSelectedItems();
            var storageCards = this.playerStorage.getSelectedItems();
            var cards = handCards.concat(storageCards);

            if (cards.length == 0) {
                this.showMessage(this.resources.noCardError, 'error');
                return;
            }

            if (cards.length != 2) {
                this.showMessage(this.resources.marketError, 'error');
                return;
            }

            var card_ids = [];
            for (var i in cards) {
                console.log(cards[i]);
                card_ids.push(cards[i].id);
            }

            this.ajaxcall("/incangold/incangold/market.html",
                {
                    lock: true,
                    cards: card_ids.toString(),
                },
                this,
                function (result) { },
                function (is_error) { }
                );
        },

        onOffering: function (evt) {
            console.log('offering');

            // Preventing default browser reaction
            dojo.stopEvent(evt);

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if (!this.checkAction('offering')) {
                return;
            }

            //you must have two cards selected from your hand/storage in order to make an offering
            var handCards = this.playerHand.getSelectedItems();
            var storageCards = this.playerStorage.getSelectedItems();
            var cards = handCards.concat(storageCards);

            if (cards.length == 0) {
                this.showMessage(this.resources.noCardError, 'error');
                return;
            }

            if (cards.length != 2) {
                this.showMessage(this.resources.offeringError, 'error');
                return;
            }

            var card_ids = [];
            for (var i in cards) {
                card_ids.push(cards[i].id);
            }

            this.ajaxcall("/incangold/incangold/offering.html",
                {
                    lock: true,
                    cards: card_ids.toString(),
                },
                this,
                function (result) { },
                function (is_error) { }
                );
        },

        onPlant: function (evt) {
            console.log('plant');

            // Preventing default browser reaction
            dojo.stopEvent(evt);

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if (!this.checkAction('plant')) {
                return;
            }

            var cards = this.playerHand.getSelectedItems();
            if (cards.length == 0) {
                this.showMessage(this.resources.noCardError, 'error');
                return;
            }

            //which play type is it?
            //if all the cards types played exist in fields owned by this player, it is option 3
            //else if only one type has been played and it contains 2+ cards, it is option 1        
            //else if exactly two different cards have been played, then it is option 2

            //first, make an array of different types of cards played, and how many of each
            var types = {};
            var typeCount = 0;
            for(var i=0; i<cards.length; i++)
            {
                if (types[cards[i]['type']] != null) {
                    types[cards[i]['type']]++;
                } else {
                    typeCount++;
                    types[cards[i]['type']] = 1;
                }
            }
            
            var allExistingFields = true;
            
            for (var i = 0; i < cards.length; i++) {
                if (this.getFieldCount(this.player_id, cards[i]['type']) == 0)
                {
                    allExistingFields = false;
                }
            }

            //validation part 1 - invalid cards: flooded/spec
            for (var i = 0; i < cards.length; i++) {
                var cardType = parseInt(cards[i].type);
                if (this.speculationTypes.indexOf(cardType) >= 0) {
                    this.showMessage(this.resources.plantSpeculationError, 'error');
                    return;
                }
                if (cardType == parseInt(this.floodCardType) || this.floodHarvestTypes.indexOf(cardType) > -1) {
                    this.showMessage(this.resources.plantFloodError, 'error');
                    return;
                }
            }
            
            //Validation part 2 - number and types of cards played:
            if (allExistingFields) {
                console.log("Option 3 - adding to existing fields");
            } else //planting new fields. Either 2+ the same type, or exactly two cards of different types.
            {
                if (cards.length < 2) {
                    this.showMessage(this.resources.plantField2CardsReq, 'error');
                    return;
                }
                if (typeCount > 2) {
                    this.showMessage(this.resources.plantFieldTypeError, 'error');
                    return;
                }
                if (typeCount == 2 && cards.length != 2) {
                    this.showMessage(this.resources.plantFieldTypeError, 'error');
                    return;
                }
            }

            //validate part 3 - any new fields must be bigger than opposing fields of the same type
            for (var i = 0; i < cards.length; i++) {
                for (j = 0; j < this.playerFields.length; j++) {
                    if (this.playerFields[j].type == cards[i].type &&
                        this.playerFields[j].player_id != this.player_id &&
                        this.playerFields[j].fields.count() >= parseInt(types[cards[i].type])) {
                        this.showMessage(this.resources.plantFieldSizeError, 'error');
                        return;
                    }
                }
            }

            var card_ids = [];
            for (var i in cards) {
                console.log(cards[i]);
                card_ids.push(cards[i].id);
            }

            this.ajaxcall("/incangold/incangold/plant.html",
                {
                    lock: true,
                    cards: card_ids.toString(),
                },
                this,
                function (result) { },
                function (is_error) { }
                );
        },

        onSpeculate: function (evt) {
            console.log('speculate');

            // Preventing default browser reaction
            dojo.stopEvent(evt);

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if (!this.checkAction('speculate')) {
                return;
            }

            //validation - invalid cards: non-spec
            var cards = this.playerHand.getSelectedItems();
            if (cards.length == 0) {
                this.showMessage(this.resources.noCardError, 'error');
                return;
            }
            if (cards.length > 2) {
                this.showMessage(this.resources.speculationCardCountError, 'error');
                return;
            }

            //validation part 2 - invalid or flooded
            
            var card_ids = [];
            console.log("Speculation validation");
            for (var i in cards) {
                console.log("card:");
                console.log(cards[i]);
                card_ids.push(cards[i].id);

                //validation - invalid cards: non-spec
                if (this.speculationTypes.indexOf(parseInt(cards[i].type)) < 0)
                {
                    this.showMessage(this.resources.speculationError, 'error');
                    return;
                }

                for (var h in this.floodHarvestTypes) {
                    console.log("flood harvest type: " + this.floodHarvestTypes[h]);
                    console.log("harvest types for this card:");
                    console.log(this.harvestTypes[cards[i].type]);
                    if (this.harvestTypes[cards[i].type].indexOf(this.floodHarvestTypes[h]) > -1) {
                        this.showMessage(this.resources.speculationFloodError, 'error');
                        return;
                    }   
                }
            }
            console.log("end speculation validation");

            this.ajaxcall("/incangold/incangold/speculate.html",
                {
                    lock: true,
                    cards: card_ids.toString(),
                },
                this,
                function (result) { },
                function (is_error) { }
                );
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your incangold.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 

            dojo.subscribe('reshuffle', this, 'notif_reshuffle');
            this.notifqueue.setSynchronous('reshuffle', 1500);

            dojo.subscribe('plagueDrawn', this, 'notif_plagueDrawn');
            this.notifqueue.setSynchronous('plagueDrawn', 1500);

            dojo.subscribe('plagueEnd', this, 'notif_plagueEnd');
            this.notifqueue.setSynchronous('plagueEnd', 1000);

            dojo.subscribe('harvest', this, "notif_harvest");
            this.notifqueue.setSynchronous('harvest', 2000);

            dojo.subscribe('drawCards', this, "notif_drawCards");
            this.notifqueue.setSynchronous('drawCards', 1500);

            dojo.subscribe('drewCards', this, "notif_drewCards"); //somebody drew cards. Update remaining count
            this.notifqueue.setSynchronous('drewCards', 1000);
            
            dojo.subscribe('removeCards', this, "notif_removeCards");
            this.notifqueue.setSynchronous('removeCards', 500);

            dojo.subscribe('flood', this, "notif_flood");
            this.notifqueue.setSynchronous('flood', 2000);

            dojo.subscribe('addToField', this, "notif_addToField");
            this.notifqueue.setSynchronous('addToField', 1000);

            dojo.subscribe('destroyField', this, "notif_destroyField");
            this.notifqueue.setSynchronous('destroyField', 1500);

            dojo.subscribe('speculateFail', this, "notif_speculateFail");
            this.notifqueue.setSynchronous('speculateFail', 1000);

            dojo.subscribe('speculateSuccess', this, "notif_speculateSuccess");
            this.notifqueue.setSynchronous('speculateSuccess', 1000);

            dojo.subscribe('tableWindow', this, "notif_finalScore");
            this.notifqueue.setSynchronous('tableWindow', 3000);
        },

        // from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // play the card in the user interface.
        },    
        
        */

        notif_finalScore: function (notif) {
            console.log('**** Notification : finalScore');
            console.log(notif);

            // Update score
            //this.scoreCtrl[notif.args.player_id].incValue(notif.args.score_delta);
        },

        notif_drewCards: function(notif) {
            this.setCardsRemaining(notif.args.cardsRemaining, notif.args.shufflesRemaining);
        },

        notif_drawCards: function (notif) {

            var cards = notif.args.cards;
            console.log("adding: ");
            for (var i in cards) {
                var card = cards[i];
                console.log(card);
                this.placeCard(card.type, card.id, 'drawPile', 0);
                this.playerHand.addToStockWithId(card.type, card.id, 'card_' + card.id);
                dojo.destroy('card_' + card.id);
            }
        },

        notif_removeCards: function (notif) {
            
            var cards = notif.args.cards;
            console.log("removing: " + cards);
            for (var i in cards) { //seems to be safe to do this without checking if the card exists first
                this.playerHand.removeFromStockById(cards[i]);
                this.playerStorage.removeFromStockById(cards[i]);
            }
        },

        notif_plagueEnd: function(notif) {

            //delete plague card
            if (this.plagueCardId != null && this.plagueCardId > 0) {
                this.fadeOutAndDestroy('card_' + this.plagueCardId);
            }
        },

        notif_flood: function (notif) {
            console.log("flooding:");
            console.log(notif.args);
            this.placeCard(notif.args.floodType, notif.args.floodCardId, 'drawPile', 1);
            this.slideToObject('card_' + notif.args.floodCardId, 'flood', 500, 100).play();

            if (this.floodCardId != null && this.floodCardId > 0) {
                this.fadeOutAndDestroy('card_' + this.floodCardId);
            }
            this.floodCardId = notif.args.floodCardId;
            this.floodCardType = notif.args.floodType;
            this.floodHarvestTypes = notif.args.floodHarvestTypes;

            this.setCardsRemaining(notif.args.cardsRemaining, notif.args.shufflesRemaining);
        },

        notif_reshuffle: function (notif) {
            //clear the flood stack
            dojo.removeClass("plagueToken", "hidden");
            
            if (this.floodCardId != null && this.floodCardId > 0) {
                this.fadeOutAndDestroy('card_' + this.floodCardId);
            }
            this.floodCardId = null;
            this.floodCardType = null;
            this.setCardsRemaining(notif.args.cardsRemaining, notif.args.shufflesRemaining);
        },

        notif_plagueDrawn: function (notif) {
            this.setCardsRemaining(notif.args.cardsRemaining, notif.args.shufflesRemaining);
            this.plagueCardId = notif.args.plagueCardId;
            this.plagueCardType = notif.args.plagueType;
            
            dojo.addClass("plagueToken","hidden");

            //draw the plague card on the flood.
            //if it's from a flood draw, move it from the deck
            //if it's from another player's draw, do the same
            //if it's from your draw, move it from your hand
            if (notif.args.draw_type == 'deck') {
                this.placeCard(notif.args.plagueType, notif.args.plagueCardId, 'drawPile', 1);
                this.slideToObject('card_' + notif.args.plagueCardId, 'gameArt', 500, 100).play();
            }
            if (notif.args.draw_type == 'player') {
                this.plaguePlayerId = notif.args.player_id;
                if (notif.args.player_id == this.player_id) {
                    this.placeCard(notif.args.plagueType, notif.args.plagueCardId, 'playerHand', 1);
                    this.placeOnObject('card_' + notif.args.plagueCardId, 'playerHand_item_' + notif.args.plagueCardId);
                    this.slideToObject('card_' + notif.args.plagueCardId, 'gameArt', 500, 100).play();
                    this.playerHand.removeFromStockById(this.plagueCardId);
                } else {
                    //this insisted on aligning wrongly on the flood card when moved from the overall player board
                    //so I'll just do it from the draw pile instead.
                    this.placeCard(notif.args.plagueType, notif.args.plagueCardId, 'drawPile', 1);
                    this.slideToObject('card_' + notif.args.plagueCardId, 'gameArt', 500, 100).play();
                }
            }
        },

        notif_harvest: function (notif) {
            var card = notif.args.card;
            if (card != null) {
                this.removeCardFromField(card, notif.args.playerId, true);
            }
        },

        notif_addToField: function (notif) {
            console.log("add to field");
            console.log(notif.args);
            for (var k in notif.args.cards) {
                this.addCardToField(notif.args.cards[k], notif.args.playerId);
            }
            console.log("add to field ok");
        },

        notif_destroyField: function(notif) {            
            for (var k in notif.args.cards) {
                this.removeCardFromField(notif.args.cards[k], notif.args.playerId, false);
            }
        },
        notif_speculateFail: function (notif) {
            for (var k in notif.args.cards) {
                this.removeCardFromField(notif.args.cards[k], notif.args.playerId, false);
            }
        },

        notif_speculateSuccess: function(notif) {            
            this.removeCardFromField(notif.args.card, notif.args.playerId, false);
        }
   });             
});
