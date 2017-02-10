/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * incangold implementation : © <Your name here> <Your email address here>
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
    "ebg/counter",
    "ebg/stock",
	"ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.incangold", ebg.core.gamegui, {
        constructor: function(){
            console.log('incangold constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
			this.cardwidth = 159;
            this.cardheight = 247;

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
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player in this.gamedatas.players )
            {
                
                document.getElementById("gem_icon_"+this.gamedatas.players[player].id).innerHTML=this.gamedatas.players[player].field;
				 
                // TODO: Setting up players boards if needed
            }
            document.getElementById("tent_"+this.gamedatas.current_player_id).innerHTML=this.gamedatas.tent;
			
  
			
            // TODO: Set up your game interface here, according to "gamedatas"
            this.table = new ebg.stock();
            this.table.create( this, $('table'), this.cardwidth, this.cardheight );
            this.table.image_items_per_row = 7;
			this.table.setSelectionMode( 0 );
            
            // Create cards types:
            for( var i=1;i<=21;i++ )
            {
             
            this.table.addItemType( i, 1, g_gamethemeurl+'img/cards.jpg', i-1 );
              
            }
			for( var i in this.gamedatas.table )
            {
                var card = this.gamedatas.table[i];
                this.table.addToStockWithId( card.type , "tablecard_"+card.id ,  'templecard'+this.gamedatas.iterations  );
				
				 for ( var g=card.location_arg ; g>0 ; g-- )
				{
					this.placeGem( card.id+"_"+g, "table_item_tablecard_"+card.id   ) ;					
				}
            }
			for ( var i=1;i<=gamedatas.iterations;i++ )
			{
					document.getElementById("templecard"+i).className = "on";
			}
            
			addTooltipToClass( "templePanel", _( "This idicates the number of expeditions remaining" ), "" );
			
			addTooltipToClass( "tent", _( "Here is where you safely store your gems after each expedition, once in your tent the gems cannot be lost " ), "" );
			
			addTooltipToClass( "gems", _( "gems are divided among the players exploring the temple " ), "" );
			
			addTooltipToClass( "votecard", _( "each round players can vote to leave or to stay exploring, the leavers can pick the gems rest of the gems left on the cards " ), "" );
			
			
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
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
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
			    case 'vote':
                    this.addActionButton( 'explore_button', _('Explore the temple'), 'voteExplore' );
					this.addActionButton( 'leave_button', _('Leave to camp'), 'voteLeave' ); 
                    break;
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        /* fsno and fstype controls the css style to load, boardloc controls on which predefine div should the tile slides to. */

        placeGem: function ( gem_id, destination) 
		{
		dojo.place(
                this.format_block('jstpl_gem', {
                    id: gem_id ,
                }), destination);
        },
		
		moveGem: function ( source, destination ,amount) 
		{
			var animspeed=300;
			for (var i = 1 ; i<= amount ; i++)
			{
				this.slideTemporaryObject( '<div class="gem"></div>', 'page-content', source, destination, 2000 , animspeed );
			}
			for (var i = 1 ; i<= amount ; i++)
			{
				document.getElementById(destination).innerHTML++;
				animspeed += 300;
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

		voteExplore: function( evt )
        {
            console.log( 'voteExplore' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'voteExplore' ) )
            {   return; }

            this.ajaxcall( "/incangold/incangold/voteExplore.html", {  }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },
        
		voteLeave: function( evt )
        {
            console.log( 'voteLeave' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'voteLeave' ) )
            {   return; }

            this.ajaxcall( "/incangold/incangold/voteLeave.html", {  }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
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
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            //dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            dojo.subscribe( 'playCard', this, "notif_cardPlayed" );
			this.notifqueue.setSynchronous( 'playCard', 2000 );
			dojo.subscribe( 'ObtainGems', this, "notif_ObtainGems" );
            this.notifqueue.setSynchronous( 'ObtainGems', 2000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        */
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
			var card = notif.args.card_played;
            this.table.addToStockWithId( card.type , "tablecard_"+card.id ,  'templecard'+this.gamedatas.iterations  );
				
				 for ( var g=card.location_arg ; g>0 ; g-- )
				{
					this.placeGem( card.id+"_"+g, "table_item_tablecard_"+card.id   ) ;					
				}		
        },
		
        notif_ObtainGems: function( notif )
        {
            console.log( 'notif_explorersObtainGems' );
            console.log( notif );
			var card = notif.args.card_played;
			for (i in notif.args.players)
			{
			
				 this.moveGem ( "table_item_tablecard_"+card.id , "gem_icon_"+this.gamedatas.players[i].id , notif.args.gems )	
			
			}
		    
        },
        
   });             
});
