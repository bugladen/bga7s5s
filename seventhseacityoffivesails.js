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
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.seventhseacityoffivesails", ebg.core.gamegui, {
        constructor: function(){

            console.log('seventhseacityoffivesails constructor');

            this.wholeCardWidth = 72;
            this.wholeCardHeight = 98;
            this.cardImageWidth = 495;
            this.cardImageHeight = 675;

            //Global array containing cached properties of all the cards this page has had access to
            this.cardProperties = {};
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
            this.addTooltipHtml( 'city-oles-inn', `<div class='basic-tooltip'>${_("Ole's Inn")}</div>` );
            this.addTooltipHtml( 'city-docks', `<div class='basic-tooltip'>${_('The Docks')}</div>` );
            this.addTooltipHtml( 'city-forum', `<div class='basic-tooltip'>${_('The Forums')}</div>` );
            this.addTooltipHtml( 'city-bazaar', `<div class='basic-tooltip'>${_('The Grand Bazaar')}</div>` );
            this.addTooltipHtml( 'city-governors-garden', `<div class='basic-tooltip'>${_("Governor's Garden")}</div>` );

            this.addTooltipHtml( 'city-discard', `<div class='basic-tooltip'>${_('City Discard Pile')}</div>` );
            this.addTooltipHtml( 'day-indicator', `<div class='basic-tooltip'>${_('The Current Day')}</div>` );
            this.addTooltipHtml( 'city-day-phase', `<div class='basic-tooltip'>${_('The Current Phase of the Day')}</div>` );
            this.addTooltipHtmlToClass('city-reknown', `<div class='basic-tooltip'>${_('Current Reknown on this City Location')}</div>` );

            //Update the day
            if (gamedatas.day > 0) {
                $('day-indicator').innerHTML = gamedatas.day;
                dojo.style('day-indicator', 'display', 'block');
            }

                //Update the game phase indicator            
            if (gamedatas.turnPhase > 0) {
                switch (gamedatas.turnPhase) {
                    case 1: $('city-day-phase').innerHTML = 'Dawn'; break;
                    case 2: $('city-day-phase').innerHTML = 'Planning'; break;
                    case 3: $('city-day-phase').innerHTML = 'High Drama'; break;
                    case 4: $('city-day-phase').innerHTML = 'Plunder'; break;
                    case 5: $('city-day-phase').innerHTML = 'Dusk'; break;
                }
                
                dojo.style('city-day-phase', 'display', 'block');
            }            
            
            // Setting up player home boards
            for( const playerId in gamedatas.players )
            {
                const player = gamedatas.players[playerId];

                //Display only if we are out of pre-game setup
                if (gamedatas.turnPhase > 0) {
                    // Home
                    this.createHome(playerId, player.color, player.leader);
                }
            }

            // Set up cards in home locations
            for( const index in gamedatas.homeCards )
            {
                const cardArray = gamedatas.homeCards[index];
                const playerInfo = this.gamedatas.players[cardArray.playerId];
                const cardId = `${cardArray.playerId}-${cardArray.card.id}`;
                this.createCharacterCard(cardId, playerInfo.color, cardArray.card, cardArray.playerId + '-home-anchor');
            }
            
            // Create Approach deck
            this.approachDeck = new ebg.stock();
            this.approachDeck.create( this, $('approachDeck'), this.wholeCardWidth, this.wholeCardHeight ); 
            this.approachDeck.image_items_per_row = 0;
            this.approachDeck.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
            this.approachDeck.onItemCreate = dojo.hitch( this, 'setupNewApproachCard' ); 
            this.approachDeck.setSelectionAppearance( 'class' )
            dojo.connect( this.approachDeck, 'onChangeSelection', this, 'onApproachCardSelected' );


            // For each card in the approach deck, create a stock item
            gamedatas.approachDeck.forEach((card) => {
                this.addCardToApproachDeck(card);
            });
 
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

                case 'planningPhase':
                    this.addActionButton(`actEndPlanningPhase`, _('Confirm Approach Cards'), () => this.bgaPerformAction("actEndPlanningPhase"));
                    document.getElementById('actEndPlanningPhase').classList.add('disabled');
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

            this.addTooltipHtml( `${playerId}-crewcap`, `<div class='basic-tooltip'>${_('Current Crew Capacity')}</div>` );
            this.addTooltipHtml( `${playerId}-discard`, `<div class='basic-tooltip'>${_('Faction Deck Discard Pile')}</div>` );
            this.addTooltipHtml( `${playerId}-locker`, `<div class='basic-tooltip'>${_('Player Locker Pile')}</div>` );
            this.addTooltipHtml( `${playerId}-panache`, `<div class='basic-tooltip'>${_('Current Panache')}</div>` );
        },

        getCardPropertiesByDivId: function( divId )
        {
            for( const cardId in this.cardProperties )
            {
                if (this.cardProperties[cardId]?.divId === divId) {
                    return this.cardProperties[cardId];
                }
            }

            return null;
        },
        
        createCharacterCard: function( divId, color, character, location )
        {
            //Set the divId of the card
            character.divId = divId;

            //Add to the card properties cache
            this.cardProperties[character.id] = character;

            // Leader
            dojo.place( this.format_block( 'jstpl_card_character', {
                id: divId,
                faction: character.faction.toLowerCase(),
                image: character.image,
                player_color: color,
                resolve: character.resolve,
                combat: character.combat,
                finesse: character.finesse,
                influence: character.influence,
                wounds: character.wounds,
            }), location, "before" );

            if (character.wounds == 0) {
                dojo.removeClass(`${divId}-wounds`, 'character-wounds');
            }

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + character.image}" />`, 500);
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
                ['playLeader', 1500],
                ['approachCard', 500],
                ['dawn', 1000],
                ['cityCardAddedToLocation', 500],
                ['playCityCard', 1500],
                ['planningPhase', 100],
            ];
    
            notifs.forEach((notif) => {
                dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
                this.notifqueue.setSynchronous(notif[0], notif[1]);
            });
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

            $('pagemaintitletext').innerHTML = `${args.player_name} has selected <span style="font-weight:bold">${args.leader.name}</span> as their leader`;
        },

        notif_approachCard: function( notif )
        {
            console.log( 'notif_approachCard' );
            console.log( notif );

            this.addCardToApproachDeck(notif.args.card);
    },

        notif_dawn: function( notif )
        {
            console.log( 'notif_dawn' );
            console.log( notif );

            const args = notif.args;

            $('day-indicator').innerHTML = args.day;
            dojo.style('day-indicator', 'display', 'block');

            $('city-day-phase').innerHTML = 'Dawn';
            dojo.style('city-day-phase', 'display', 'block');
        },

        notif_cityCardAddedToLocation: function( notif )
        {
            console.log( 'notif_playCityCard' );
            console.log( notif );
        },

        notif_playCityCard: function( notif )
        {
            console.log( 'notif_playCityCard' );
            console.log( notif );
        },

        notif_planningPhase: function( notif )
        {
            console.log( 'notif_planningPhase' );
            console.log( notif );

            const args = notif.args;
            $('city-day-phase').innerHTML = 'Planning';
        },


        // Utlity functions
        addCardToApproachDeck: function( card )
        {
            this.cardProperties[card.id] = card;

            //Different weight depending on the type. Scheme cards go first
            const weight = card.type === "Scheme" ? 1 : 2;

            //Each card is a different image, so would be considered a different type for the stock object
            this.approachDeck.addItemType(card.id, weight, g_gamethemeurl + card.image, 0);

            // Type and id are the same for the approach deck stock object
            this.approachDeck.addToStockWithId(card.id, card.id);
        },

        setupNewApproachCard: function( cardDiv, cardTypeId, cardId )
        {
            const card = this.cardProperties[cardTypeId];
            this.addTooltipHtml( cardDiv.id, `<img src="${g_gamethemeurl + card.image}" />`, 500);
        },

        onApproachCardSelected: function( control_name, item_id )
        {
            var items = this.approachDeck.getSelectedItems();
            // Grab the type of card from the properties cache and make sure we are only selecting 1 of each type
            const types = {};
            items.forEach((item) => {
                const type = this.cardProperties[item.type].type;
                if (types[type]) {
                    this.approachDeck.unselectItem(item_id);
                } else {              
                    types[type] = true;
                }
            });

            var items = this.approachDeck.getSelectedItems();

            // Enable the confirm button if we have 2 cards selected
            if (items.length === 2) {
                document.getElementById('actEndPlanningPhase').classList.remove('disabled');
            } else {
                document.getElementById('actEndPlanningPhase').classList.add('disabled');
            }


        },
    });      
});
