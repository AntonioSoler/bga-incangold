/**
 *------
 * BGA framework: (c) Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * incangold implementation : (c) Antonio Soler Morgalad.es@gmail.com
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
             // transform: rotateX(20deg) translate(-100px, -130px) rotateZ(0deg) scale3d(0.8, 0.8, 0.8); min-width: 0px;
            
			this.cardwidth = 159;
            this.cardheight = 247;
			if (!dojo.hasClass("ebd-body", "mode_3d")) {
            //dojo.addClass("ebd-body", "mode_3d");
            //dojo.addClass("ebd-body", "enableTransitions");
				
				this.control3dxaxis = 25;  // rotation in degrees of x axis (it has a limit of 0 to 80 degrees in the frameword so users cannot turn it upsidedown)
				this.control3dzaxis = 0;   // rotation in degrees of z axis
				this.control3dxpos = -130;   // center of screen in pixels
				this.control3dypos = -100;   // center of screen in pixels
				this.control3dscale = 0.8;   // zoom level, 1 is default 2 is double normal size, 
				this.control3dmode3d = false ;  			// is the 3d enabled	
				// $("globalaction_3d").innerHTML = "2D";   // controls the upper right button 
				// $("game_play_area").style.transform = "rotatex(" + this.control3dxaxis + "deg) translate(" + this.control3dypos + "px," + this.control3dxpos + "px) rotateZ(" + this.control3dzaxis + "deg) scale3d(" + this.control3dscale + "," + this.control3dscale + "," + this.control3dscale + ")";
			}

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
            this.param=new Array();
            // Setting up player boards
            for( var player in this.gamedatas.players )
            {
                dojo.byId("gem_field_"+this.gamedatas.players[player].id).innerHTML=this.gamedatas.players[player].field;
				for ( var i=0 ; i < this.gamedatas.players[player].artifacts ;i++)
					{
						dojo.place( "<div class='artifacticon'></div>" , "field_" + this.gamedatas.players[player].id, "last" );
						this.addTooltipToClass( "artifacticon", _( "Each artifact is worth 5 gems, the 4th and 5th drawn give 5 extra gems at the moment of collection" ), "" );
					} 
            }
			
			for ( var i=0 ; i < this.gamedatas.templeartifacts ;i++)
			{
				dojo.place( "<div class='artifacticon removed'></div>" , "templeleft", "last" );
				this.addTooltipToClass( "removed", _( "This artifact was not picked by the explorers and now is lost forever in the temple" ), "" );
			} 
		
			if( ! this.isSpectator )
			{
				dojo.place("<span id='tentcount'>"+this.gamedatas.tent+"</span>" , "tent_"+this.gamedatas.current_player_id , "last");
			}
			
			dojo.byId("decksize").innerHTML=this.gamedatas.cardsRemaining;
			
            for (i in this.gamedatas.exploringPlayers)
			{			
				 dojo.addClass( "votecard_"+i , "votecardBack" );
			}
			
			for (i in this.gamedatas.campPlayers)
			{			
				 dojo.addClass( "votecard_"+i , "votecardLeave" );
			}
			
            this.tablecards = new ebg.stock();
            this.tablecards.create( this, $('tablecards'), this.cardwidth, this.cardheight );
            this.tablecards.image_items_per_row = 7;
			this.tablecards.setSelectionMode( 0 );
            
            // Create cards types:
            for(  i=1;i<=21;i++ )
            {
             
            this.tablecards.addItemType( i, 1, g_gamethemeurl+'img/cards.jpg', i-1 );
              
            }
			for( var i in this.gamedatas.table )
            {
                var card = this.gamedatas.table[i];
                this.tablecards.addToStockWithId( card.type , "tablecard_"+card.id , 'deck' );
				if ( card.type >=12 && card.type <=16 ) 
				    {
						dojo.addClass( "tablecards_item_tablecard_"+card.id , "isartifact" )
					}
				 for ( var g=card.location_arg ; g>0 ; g-- )
				{
					this.placeGem( card.id+"_"+g, "tablecards_item_tablecard_"+card.id   ) ;					
				}
				this.cardTooltip (card.id , card.type);
            }
			for ( var i=1;i<=gamedatas.iterations;i++ )
			{
					dojo.addClass( "templecard"+i ,"on");
			}
            
			this.addTooltipToClass( "templeclass", _( "Expeditions remaining" ), "" );
			
			this.addTooltipToClass( "tent", _( "Gems are stored here after each expedition. Once in your tent, the gems are safe.<br><b>The content of your tent is hidden from other players</b>" ), "" );
			
			this.addTooltipToClass( "gemfield", _( "These are your share of Gems obtained on the current expedition, you need to return to the camp to safely store them" ), "" );
			
			this.addTooltipToClass( "cardback", _( "Each round players vote to return to camp or to keep exploring. " ), "" );
			
			this.addTooltipToClass( "votecardLeave", _( "This player has voted to return to camp and has stored his gems in the tent" ), "" );
			
			this.addTooltipToClass( "votecardExplore", _( "This player has voted to continue exploring" ), "" );
			
			
			
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
           
		    case 'reshuffle':

            
                // Show some HTML block at this game state
                dojo.query('.votecardExplore').removeClass('votecardExplore') ;
                
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
			    case 'vote':
                    this.addActionButton( 'explore_button', _('Explore the temple'), 'voteExplore' );
					this.addActionButton( 'leave_button', _('Return to camp'), 'voteLeave' ); 
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
				id: gem_id 
			}), destination);
		this.addTooltipToClass( "cardgem", _( "Some gems were left on the floor because they could not be didvidied evenly among the explorers.<p> The players could pick this when returning on the way back to camp" ), "" );
        },
		
		placeVotecard: function ( player_id, action) 
		{	
			a1= this.MySlideToObject('votecard_'+player_id, 'overall_player_board_'+player_id, 400, 0);
			a2= dojo.fadeOut( {      node: 'votecard_'+player_id,
                                    onEnd: function( node ) {
															dojo.replaceClass(node,'votecard'+action); 
                                                             } 
                                  } );
			a3= dojo.fadeIn( {      node: 'votecard_'+player_id} );
               
			a4= this.MySlideToObject( 'votecard_'+player_id , 'cardholder_'+player_id , 800,0 );	
            var anim = dojo.fx.chain( [ a1,  a2 , a3 ,a4]);
		 anim.play();
        },
		
		moveGem: function ( source, destination ,amount) 
		{
			var animspeed=0;
			for (var i = 1 ; i<= amount ; i++)
			{
				this.slideTemporaryObjectAndIncCounter( '<div class="gem spining"></div>', 'playArea', source, destination, 800 , animspeed );
				animspeed += 500;
			}
        },
		
		cardTooltip: function ( card_id, card_type) 
		{
			if ( card_type <=11 )
			{
				 this.addTooltip( "tablecards_item_tablecard_"+card_id, _( "This is a gems card, the loot is divided and any remaining gems are left on the floor, these could be picked by the adventurers returning to camp" ), "" );
			}
			if ( card_type >=12 && card_type <=16 )
			{
				 this.addTooltip( "tablecards_item_tablecard_"+card_id, _( "This is an artifact card, it can be picked if only ONE adventurer returns that turn to camp" ), "" );
			}
			if ( card_type >=17 )
			{
				 this.addTooltip( "tablecards_item_tablecard_"+card_id, _( "This is a hazzard card, if two of the same kind are drawn the explorers are exppelled from the temple and they loose the gems not stored" ), "" );
			}	 
        },
		
		moveCard: function ( id , destination , isartifact ) 
		{
			dojo.addClass( "tablecards_item_tablecard_"+id ,"animatedcard") ;
			this.tablecards.removeFromStockById( "tablecard_"+id , destination  );	
			if ( isartifact == 1 ) 
				{
					dojo.place( "<div class='artifacticon'></div>" , destination  , "last");
					this.addTooltipToClass( "artifacticon", _( "Each artifact is worth 5 gems, the 4th and 5th drawn give 5 extra gems at the moment of collection" ), "" );
				}
		},
		
		slideToObjectAndDestroyAndIncCounter: function( mobile_obj , to, duration, delay ) 
		{
			var obj = dojo.byId(mobile_obj );
			dojo.style(obj, "position", "absolute");
			dojo.style(obj, "left", "0px");
			dojo.style(obj, "top", "0px");
			var anim = this.MySlideToObject(obj, to, duration, delay );
			
			this.param.push(to);
            
			dojo.connect(anim, "onEnd", this, 'incAndDestroy' );
			anim.play();
			return anim;
			},
		
		slideTemporaryObjectAndIncCounter: function( mobile_obj_html , mobile_obj_parent, from, to, duration, delay ) 
		{
			var obj = dojo.place(mobile_obj_html, mobile_obj_parent );
			dojo.style(obj, "position", "absolute");
			dojo.style(obj, "left", "0px");
			dojo.style(obj, "top", "0px");
			this.placeOnObject(obj, from);
			
			var anim = this.MySlideToObject(obj, to, duration, delay );
			
			this.param.push(to);
            
			dojo.connect(anim, "onEnd", this, 'incAndDestroy' );
			anim.play();
			return anim;
			},
		
		MySlideToObject: function (mobile, target, duration, delay)
		{
			if (mobile === null)
			{
				console.error("slideToObject: mobile obj is null");
			}
			if (target === null)
			{
				console.error("slideToObject: target obj is null");
			}
			var tgt = dojo.position(target);
			var src = dojo.position(mobile);
			if (typeof duration == "undefined")
			{
				duration = 500;
			}
			if (typeof delay == "undefined")
			{
				delay = 0;
			}
			if (this.instantaneousMode)
			{
				delay = Math.min(1, delay);
				duration = Math.min(1, duration);
			}
			var left = dojo.style(mobile, "left");
			var top = dojo.style(mobile, "top");
			
			left = left + tgt.x - src.x + (tgt.w - src.w) / 2;
			top = top + tgt.y - src.y + (tgt.h - src.h) / 2;
			travelanim= function(node){
						
						dojo.removeClass(node,"traveller");
						dojo.addClass(node,"traveller");
						}
			slideanim = dojo.fx.slideTo(
			{
				node: mobile,
				top: top,
				left: left,
				delay: delay,
				duration: duration,
				unit: "px" 
			});
			dojo.connect(slideanim, "onPlay", dojo.hitch(this, travelanim, mobile )) ;
			return slideanim;
			
		},
		MySlideTemporaryObject: function (_a47, _a48, from, to, _a49, _a4a)
					{
						var obj = dojo.place(_a47, _a48);
						dojo.style(obj, "position", "absolute");
						dojo.style(obj, "left", "0px");
						dojo.style(obj, "top", "0px");
						
						this.placeOnObject(obj, from);
						var anim = this.MySlideToObject(obj, to, _a49, _a4a);
						var _a4b = function (node)
						{
							dojo.destroy(node);
						};
						dojo.connect(anim, "onEnd", _a4b);
						anim.play();
						return anim;
					},
		
		incAndDestroy : function(node) 
		{				
				dojo.destroy(node);
				target=this.param.shift();
				if ( dojo.byId(target) != null )
				{
					dojo.byId(target).innerHTML=eval(dojo.byId(target).innerHTML) + 1;
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
            dojo.subscribe('playCard', this, "notif_cardPlayed" );
			this.notifqueue.setSynchronous( 'playCard', 2000 );
			dojo.subscribe('ObtainGems', this, "notif_ObtainGems" );
            this.notifqueue.setSynchronous( 'ObtainGems', 3000 );
			dojo.subscribe('finalScore', this, "notif_finalScore");
            this.notifqueue.setSynchronous('notif_finalScore',10000);
			dojo.subscribe('reshuffle', this, "notif_reshuffle");
            this.notifqueue.setSynchronous('reshuffle', 4000);
			dojo.subscribe('playerleaving', this, "notif_playerleaving");
            this.notifqueue.setSynchronous('playerleaving', 3000);
			dojo.subscribe('artifactspicked', this, "notif_artifactspicked");
            this.notifqueue.setSynchronous('artifactspicked', 800);
			dojo.subscribe('playerexploring', this, "notif_playerexploring");
            this.notifqueue.setSynchronous('playerexploring', 400);
			dojo.subscribe('stcleanpockets', this, "notif_stcleanpockets");
            this.notifqueue.setSynchronous('stcleanpockets', 4000);
			
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
            this.tablecards.addToStockWithId( card.type , "tablecard_"+card.id , 'deck' );
			if ( card.type >=12 && card.type <=16 ) 
			   {
					dojo.addClass( "tablecards_item_tablecard_"+card.id , "isartifact" )
					//dojo.attr("tablecards_item_tablecard_"+card.id, "title", card.id)
				}
			for ( var g=card.location_arg ; g>0 ; g-- )
				{
					this.placeGem( card.id+"_"+g, "tablecards_item_tablecard_"+card.id   ) ;					
				}
			this.cardTooltip (card.id , card.type);	
			dojo.byId("decksize").innerHTML=eval(dojo.byId("decksize").innerHTML)-1;
        },
		
		notif_playerleaving: function( notif )
        {
            console.log( 'notif_playerleaving' );
			notif.args=this.notifqueue.playerNameFilterGame(notif.args);
            console.log( notif );
			this.placeVotecard ( notif.args.thisid , "Leave" );
			this.addTooltipToClass( "votecardLeave", _( "This player has voted to return to camp and has stored his gems in the tent" ), "" );
            var animspeed=0;
			
			gems = dojo.byId("gem_field_"+notif.args.thisid).innerHTML
			if ( gems >=1 )
			{
				for ( var g=1 ; g<=gems  ; g++ )
				{
					if (this.gamedatas.current_player_id == notif.args.thisid) 
						{
						this.slideTemporaryObjectAndIncCounter ( '<div class="gem spining"></div>', 'playArea', "gem_field_"+notif.args.thisid , "tentcount" , 600 , animspeed );
						}
					else 
						{
						this.MySlideTemporaryObject( '<div class="gem spining"></div>', 'playArea', "gem_field_"+notif.args.thisid , "tent_"+notif.args.thisid, 600 , animspeed );
						}
					animspeed += 300;
				}
			}
			if ( notif.args.gems >=1 )
			{	
				animspeed=0;
				gemarray=dojo.query('[id^=tablecards_] > *');
				for ( var g=0 ; g<= notif.args.gems-1  ; g++ )
				{   
			        try {    //Surrounding this with a TRY-CATCH because there is a race condition, if the user presses F5 during this process the gamedatas would give us 0 gems on the cards to pick.
					    pickedgem=gemarray[g];
						source=pickedgem.parentElement.id
					    dojo.destroy( pickedgem.id );
						
						if (this.gamedatas.current_player_id == notif.args.thisid) 
							{
							this.slideTemporaryObjectAndIncCounter ( '<div class="gem spining"></div>', 'playArea', source , "tentcount" , 1000 , animspeed );
							}
						else 
							{
							this.MySlideTemporaryObject( '<div class="gem spining"></div>', 'playArea', source , "tent_"+notif.args.thisid , 1000 , animspeed );
							}
						animspeed += 500;
					}
					catch(err) {}
					
				}
			}
			dojo.byId("gem_field_"+notif.args.thisid).innerHTML = 0 ;
        },
		
		notif_playerexploring: function( notif )
        {
            console.log( 'notif_playerexploring' );
			notif.args=this.notifqueue.playerNameFilterGame(notif.args);
            console.log( notif );
			this.placeVotecard ( notif.args.thisid , "Explore" );			
			
			this.addTooltipToClass( "votecardExplore", _( "This player has voted to continue exploring" ), "" );
        },
		
		notif_artifactspicked: function( notif )
        {
            console.log( 'notif_artifactspicked' );
			notif.args=this.notifqueue.playerNameFilterGame(notif.args);
            console.log( notif );
			if ( notif.args.extra >0 )
			{
				animspeed = 0;
				for ( var g=1 ; g<=notif.args.extra  ; g++ )
				{
					if (this.gamedatas.current_player_id == notif.args.thisid) 
							{
							this.slideTemporaryObjectAndIncCounter ( '<div class="gem spining"></div>', 'playArea', 'templePanel' , "tentcount" , 1000 , animspeed );
							}
						else 
							{
							this.MySlideTemporaryObject( '<div class="gem spining"></div>', 'playArea', 'templePanel'  , "tent_"+notif.args.thisid, 1000 , animspeed );
							}
					animspeed += 500;
				}
			}	
			for ( card_id in notif.args.cards )				
			{    
				this.moveCard ( notif.args.cards[card_id].id ,'field_'+notif.args.thisid , 1 );
			}
        },
		
		notif_stcleanpockets: function( notif )
        {
            console.log( 'notif_stcleanpockets' );
            console.log( notif );
			var card = notif.args.card_played;
            
			this.moveCard ( card.id ,'templePanel', 0);
			dojo.query(".isartifact").addClass("animatedcard");
			artifacts=document.getElementsByClassName("isartifact");
			for (i=0 ; i< artifacts.length ; i++ )
			    {
					this.slideToObjectAndDestroy ( artifacts[i].id,'templePanel', 500 ,0);
					dojo.place( "<div class='artifacticon removed'></div>" , 'templeleft' , "last");
					this.addTooltipToClass( "removed", _( "This artifact was not picked by the explorers and now is lost forever in the temple" ), "" );
				}	
        },
		
        notif_ObtainGems: function( notif )
        {
            console.log( 'notif_ObtainGems' );
            console.log( notif );
			var card = notif.args.card_played;
			for (i in notif.args.players)
			{			
				 this.moveGem ( "tablecards_item_tablecard_"+card.id , "gem_field_"+this.gamedatas.players[i].id , notif.args.gems )
			}
		    
        },
		notif_reshuffle: function( notif )
        {
            console.log( 'notif_reshuffle' );
            console.log( notif );
			dojo.query(".isartifact").addClass("animatedcard");
			artifacts=document.getElementsByClassName("isartifact");
			for (i=0 ; i< artifacts.length ; i++ )
			    {
					this.slideToObjectAndDestroy ( artifacts[i].id,'templePanel', 500 ,0);
					dojo.place( "<div class='artifacticon removed'></div>" , 'templeleft' , "last");
					this.addTooltipToClass( "removed", _( "This artifact was not picked by the explorers and now is lost forever in the temple" ), "" );
				}
			if (notif.args.iterations <=5 )
			{
				for (i in this.gamedatas.players )
				{			
					dojo.byId( "gem_field_"+this.gamedatas.players[i].id ).innerHTML=0;
					dojo.replaceClass('votecard_'+this.gamedatas.players[i].id,'votecardBack');  
				}
				this.tablecards.removeAllTo('deck');
				
				this.MySlideTemporaryObject( "<div  class='templecard t"+ notif.args.iterations +" on spining'></div>" , 'templePanel', 'templePanel', "templecard"+notif.args.iterations, 500, 0);  
				dojo.addClass( "templecard"+notif.args.iterations ,"on")
				dojo.byId("decksize").innerHTML=notif.args.cardsRemaining;
			}
			
        },
		
		notif_finalScore: function (notif) 
		{
            console.log( 'notif_finalScore' );
            console.log( notif );
			
			for (i in this.gamedatas.players )
				{			
					dojo.byId( "gem_field_"+this.gamedatas.players[i].id ).innerHTML=0;
					dojo.place( "<div class='gemtent'>"+ notif.args.players[i].tent +"</div>" , "tentholder_"+this.gamedatas.players[i].id , "last" );
					this.slideToObjectAndDestroy ( "tent_"+this.gamedatas.players[i].id ,'templePanel', 1000 ,0 );
					this.scoreCtrl[ i ].setValue( notif.args.players[i].score );
				}

            // Update score
            //this.scoreCtrl[notif.args.player_id].incValue(notif.args.score_delta);
        },
        
   });             
});