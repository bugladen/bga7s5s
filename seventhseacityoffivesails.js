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
        
            this.LOCATION_CITY_DECK = 'City Deck';
            this.LOCATION_CITY_DOCKS = 'City Docks';
            this.LOCATION_CITY_FORUM = 'City Forum';
            this.LOCATION_CITY_BAZAAR = 'The Grand Bazaar';
            this.LOCATION_CITY_OLES_INN = "Ole's Inn";
            this.LOCATION_CITY_GOVERNORS_GARDEN = "Governor's Garden";
            this.LOCATION_PLAYER_HOME = 'Player Home';

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

                dojo.style( `player_score_${playerId}`, 'display', 'none' );
                //dojo.style( `icon_point_${playerId}`, 'display', 'none' );

                // Override the score with the reknown
                this.getPlayerPanelElement(playerId).innerHTML = this.format_block( 'jstpl_player_board', {
                    id: playerId,
                    reknown: player.score,
                    crewcap: player.leader?.modifiedCrewCap ?? '-',
                    panache: player.leader?.modifiedPanache ?? '-',
                });
                this.addTooltipHtml( `${playerId}-score-reknown`, `<div class='basic-tooltip'>${_('Current Reknown')}</div>` );
                this.addTooltipHtml( `${playerId}-score-crewcap`, `<div class='basic-tooltip'>${_('Current Crew Cap')}</div>` );
                this.addTooltipHtml( `${playerId}-score-panache`, `<div class='basic-tooltip'>${_('Current Leader Panache')}</div>` );

                //Display only if we are out of pre-game setup
                if (gamedatas.turnPhase > 0) {
                    // Home
                    this.createHome(playerId, player.color, player.leader);
                    dojo.addClass( `overall_player_board_${playerId}`, `home-${player.leader.faction.toLowerCase()}` );
                }
            }

            this.addTooltipHtmlToClass('first-player', `<div class='basic-tooltip'>${_('First Player')}</div>` );

            // Set up cards in home locations
            for( const index in gamedatas.homeCards )
            {
                const card = gamedatas.homeCards[index];
                const playerInfo = this.gamedatas.players[card.controllerId];
                const divId = `${card.controllerId}-${card.id}`;
                this.createCharacterCard(divId, playerInfo.color, card, card.controllerId + '-home-anchor');
            }

            // Set up cards in oles inn
            for( const index in gamedatas.oleCards )
            {
                const card = gamedatas.oleCards[index];
                const cardId = `olesinn-${card.id}`;
                this.createCard(cardId, card, 'oles-inn-endcap');
            }

            // Set up cards in the docks
            for( const index in gamedatas.dockCards )
            {
                const card = gamedatas.dockCards[index];
                const cardId = `docks-${card.id}`;
                this.createCard(cardId, card, 'dock-endcap');
            }

            // Set up cards in the forum
            for( const index in gamedatas.forumCards )
            {
                const card = gamedatas.forumCards[index];
                const cardId = `forums-${card.id}`;
                this.createCard(cardId, card, 'forum-endcap');
            }
                
            // Set up cards in the bazaar
            for( const index in gamedatas.bazaarCards )
            {
                const card = gamedatas.bazaarCards[index];
                const cardId = `bazaar-${card.id}`;
                this.createCard(cardId, card, 'bazaar-endcap');
            }

            // Create Approach deck
            this.approachDeck = new ebg.stock();
            this.approachDeck.create( this, $('approachDeck'), this.wholeCardWidth, this.wholeCardHeight ); 
            this.approachDeck.image_items_per_row = 0;
            this.approachDeck.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
            this.approachDeck.onItemCreate = dojo.hitch( this, 'setupNewStockApproachCard' ); 
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

        createCard: function( divId, card, targetDiv )
        {
            if (card.type === 'Character' || card.type === 'Leader')
            {
                if (card.controllerId !== 0) {
                    const playerInfo = this.gamedatas.players[card.controllerId];
                    this.createCharacterCard(divId, playerInfo.color, card, targetDiv);
                    dojo.style( `${divId}-wealth-cost`, 'display', 'none' );
                }
                else {
                    this.createCharacterCard(divId, '', card, targetDiv);
                    dojo.removeClass(`${divId}-player-color`, 'character-player-color');
                }
            }
            else if (card.type === 'Event')
            {
                this.createEventCard(divId, card, targetDiv);
            }
            else if (card.type === 'Attachment') {
                if (card.controllerId !== 0) {
                    const playerInfo = this.gamedatas.players[card.controllerId];
                    this.createAttachmentCard(divId, playerInfo.color, card, targetDiv);
                }
                else {
                    this.createAttachmentCard(divId, '', card, targetDiv);
                }
            }


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
                        this.addActionButton(`actEndPlanningPhase`, _('Confirm Approach Cards'), () => this.onPlanningCardsSelected());
                        dojo.addClass('actEndPlanningPhase', 'disabled');
                        break;

                    case 'playerTurn':
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
            this.addTooltipHtml( `${playerId}-panache`, `<div class='basic-tooltip'>${_('Current Leader Panache')}</div>` );
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

            const wealthCost = character.wealthCost ? character.wealthCost : '';
            const resolve = character.resolve > 0 ? character.resolve : '-';
            const combat = character.combat > 0 ? character.combat : '-';
            const finesse = character.finesse > 0 ? character.finesse : '-';
            const influence = character.influence > 0 ? character.influence : '-';

            dojo.place( this.format_block( 'jstpl_character', {
                id: divId,
                faction: character.faction.toLowerCase(),
                image: character.image,
                player_color: color,
                resolve: resolve,
                combat: combat,
                finesse: finesse,
                influence: influence,
                cost: wealthCost,
            }), location, "before" );

            if (!character.wealthCost) {
                dojo.style( `${divId}-wealth-cost`, 'display', 'none' );
            }

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + character.image}" />`, 500);
        },  

        createEventCard: function( divId, event, location )
        {
            //Set the divId of the card
            event.divId = divId;

            //Add to the card properties cache
            this.cardProperties[event.id] = event;

            // Leader
            dojo.place( this.format_block( 'jstpl_card_event', {
                id: divId,
                image: event.image,
            }), location, "before" );

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + event.image}" />`, 500);
        },  

        createAttachmentCard: function( divId, color, attachment, location )
        {
            //Set the divId of the card
            attachment.divId = divId;

            //Add to the card properties cache
            this.cardProperties[attachment.id] = attachment;

            console.log('attachment', attachment);

            // Leader
            dojo.place( this.format_block( 'jstpl_card_attachment', {
                id: divId,
                faction: attachment.faction.toLowerCase(),
                image: attachment.image,
                player_color: color,
                resolve: this.formatModifer(attachment.resolveModifier),
                combat: this.formatModifer(attachment.combatModifier),
                finesse: this.formatModifer(attachment.finesseModifier),
                influence: this.formatModifer(attachment.influenceModifier),
                cost: attachment.wealthCost,
            }), location, "before" );

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + attachment.image}" />`, 500);
        },

        formatModifer: function( modifier )
        {
            if (modifier > 0) {
                return `+${modifier}`;
            } else if (modifier < 0) {
                return modifier;
            } else {
                return '-';
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
            });        
        },    

        onPlanningCardsSelected: function()
        {
            var scheme = 0;
            var character = 0;

            var items = this.approachDeck.getSelectedItems();
            items.forEach((item) => {
                this.approachDeck.removeFromStockById(item.id);
                if (this.cardProperties[item.type].type === 'Scheme') {
                    scheme = item.id;
                } else {
                    character = item.id;
                }
            });

            this.bgaPerformAction("actDayPlanned", { 
                    'scheme' : scheme, 
                    'character' : character
                }).then(() =>  {                
                    // What to do after the server call if it succeeded
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
                ['highDramaPhase', 100],
                ['playApproachScheme', 1500],
                ['playApproachCharacter', 1500],
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

            this.createCard(`${args.player_id}-${args.leader.id}`, args.leader, `${args.player_id}-home-anchor`);

            // Update the player panel
            dojo.addClass( `overall_player_board_${args.player_id}`, `home-${args.leader.faction.toLowerCase()}` );
            $(`${args.player_id}-score-crewcap`).innerHTML = args.leader.crewCap;
            $(`${args.player_id}-score-panache`).innerHTML = args.leader.panache;

            $('pagemaintitletext').innerHTML = `${args.player_name} has selected <span style="font-weight:bold">${args.leader.name}</span> as their leader`;
        },

        notif_playApproachScheme: function( notif )
        {
            console.log( 'notif_playApproachScheme' );
            console.log( notif );

            const args = notif.args;

            this.createCard(`${args.player_id}-${args.scheme.id}`, args.scheme, `${args.player_id}-home-anchor`);

            // Update the leader with the modified panache
            // Update the player panel with the modified panache

            dojo.addClass( `overall_player_board_${args.player_id}`, `home-${args.leader.faction.toLowerCase()}` );
            $(`${args.player_id}-score-crewcap`).innerHTML = args.leader.crewCap;
            $(`${args.player_id}-score-panache`).innerHTML = args.leader.panache;

            $('pagemaintitletext').innerHTML = `${args.player_name} has selected <span style="font-weight:bold">${args.leader.name}</span> as their leader`;
        },

        notif_playApproachCharacter: function (notif) {
            console.log( 'notif_playApproachCharacter' );
            console.log( notif );
        },

        notif_approachCard: function( notif )
        {
            console.log( 'notif_approachCard' );
            console.log( notif );

            notif.args.cards.forEach((card) => {
                this.addCardToApproachDeck(card);
            });            
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
            console.log( 'notif_cityCardAddedToLocation' );
            console.log( notif );

            const args = notif.args;

            const card = args.card;
            let cardId = null;
            let location = '';
            switch (args.location) {
                case this.LOCATION_CITY_OLES_INN:
                    cardId = `oles-inn-${card.id}`;
                    location = 'oles-inn-endcap';
                    break;
                case this.LOCATION_CITY_DOCKS:
                    cardId = `docks-${card.id}`;
                    location = 'dock-endcap';
                    break;
                case this.LOCATION_CITY_FORUM:
                    cardId = `forum-${card.id}`;
                    location = 'forum-endcap';
                    break;
                case this.LOCATION_CITY_BAZAAR:
                    cardId = `bazaar-${card.id}`;
                    location = 'bazaar-endcap';
                    break;
                case this.LOCATION_CITY_GOVERNORS_GARDEN:
                    cardId = `governors-garden-${card.id}`;
                    location = 'governors-garden-endcap';
                    break;
            }

            this.createCard(cardId, card, location);

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

        notif_highDramaPhase: function( notif )
        {
            console.log( 'notif_highDramaPhase' );
            console.log( notif );

            const args = notif.args;
            $('city-day-phase').innerHTML = 'High Drama';
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

        setupNewStockApproachCard: function( cardDiv, cardTypeId, cardId )
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
                dojo.removeClass('actEndPlanningPhase', 'disabled');
            } else {
                dojo.addClass('actEndPlanningPhase', 'disabled');
            }


        },
    });      
});
