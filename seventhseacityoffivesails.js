/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * seventhseacityoffivesails.js
 *
 * SeventhSeaCityOfFiveSails user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare", "dojo/dom-class",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.seventhseacityoffivesails", ebg.core.gamegui, {
        constructor: function(){
            console.log('seventhseacityoffivesails constructor');
              
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
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            console.log( "gamedatas", gamedatas );

            // Remove city sections that are not used
            const playerCount = Object.keys(gamedatas.players).length;
            if (playerCount < 4) {
                dojo.destroy('city-governors-garden');
            }
            if (playerCount < 3) {
                dojo.destroy('city-oles-inn');
            }

            // Set up the city tooltips
            this.addTooltip( 'city-discard', _('City Discard Pile'), _('Click to view') );
            this.addTooltip( 'day-indicator', _('Current Day'), '' );
            this.addTooltip( 'city-day-phase', _('Current Phase of the Day'), '' );
            this.addTooltipToClass('city-reknown', _('Current reknown on this city section'), '' );

            //If the game phase is 0 we are in pre-game setup, hide the game phase indicator            
            if (gamedatas.phase == 0) {
                dojo.style('city-day-phase', 'display', 'none');
            }
            
            // Setting up player boards
            for( const player_id in gamedatas.players )
            {
                const player = gamedatas.players[player_id];
                        
                // Home
                // this.createHome(player, player.leader);

                // Leader
                // this.createCard(player, player_id + '-home-anchor', null);
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"

 
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
            console.log( 'Entering state: '+stateName, args );
            
            switch( stateName )
            {

                case 'pickDecks':
                    break;
            
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
            console.log( 'onUpdateActionButtons: '+ stateName, args );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                 case 'pickDecks':    
                    args.availableDecks.forEach(
                        (deck) => { this.addActionButton(`actPickDeck${deck.id}-btn`, _(deck.name), () => this.onStarterDeckSelected(deck.id)) }
                    ); 
                    break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

        createHome: function( playerId, playerColor, leader )
        {
            // Home
            dojo.place( this.format_block( 'jstpl_home', {
                id: playerId,
                faction: leader.faction.toLowerCase(),
                crewcap: leader.modifiedCrewCap,
                panache: leader.modifiedPanache,
                player_color: playerColor,
            }), 'home_anchor', "before" );            
        },
        
        createCharacterCard: function( id, color, character, location )
        {
            // Leader
            dojo.place( this.format_block( 'jstpl_card_character', {
                id: id,
                faction: character.faction.toLowerCase(),
                image: character.image,
                // image: 'img/cards/7s5s/089.jpg',
                player_color: color,
                resolve: character.resolve,
                combat: character.combat,
                finesse: character.finesse,
                influence: character.influence,
                wounds: character.wounds,
            }), location, "before" );

            if (character.wounds == 0) {
                dojo.removeClass(`${id}-wounds`, 'character-wounds');
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
        
        // Example:
        
        onStarterDeckSelected: function( deck_id )
        {
            console.log( 'onDeckSelected', deck_id );

            this.bgaPerformAction("actPickDeck", { 
                'deck_type':'starter',
                'deck_id':deck_id,
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
        },    

        onCardClick: function( card_id )
        {
            console.log( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", { 
                card_id,
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
        },    

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your seventhseacityoffivesails.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            const notifs = [
                ['playLeader', 3000],
            ];
    
            notifs.forEach((notif) => {
                dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
                this.notifqueue.setSynchronous(notif[0], notif[1]);
            });

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    

        notif_playLeader: function( notif )
        {
            console.log( 'notif_playLeader' );
            console.log( notif );

            const args = notif.args;

            this.createHome(
                args.player_id, 
                args.player_color, 
                args.leader
            );

            this.createCharacterCard(
                `${args.player_id}-leader`,
                args.player_color, 
                args.leader, 
                `${args.player_id}-home-anchor`,
            );
        },
    });      
});
