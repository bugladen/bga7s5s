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

            //City location selection
            this.numberOfCityLocationsSelectable = 0;
            this.selectedCityLocations = [];

            //Connect handlers for the city locations
            this.connects = [];
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
            this.addTooltipHtml( 'oles-inn-image', `<div class='basic-tooltip'>${_("Ole's Inn")}</div>` );
            this.addTooltipHtml( 'dock-image', `<div class='basic-tooltip'>${_('The Docks')}</div>` );
            this.addTooltipHtml( 'forum-image', `<div class='basic-tooltip'>${_('The Forums')}</div>` );
            this.addTooltipHtml( 'bazaar-image', `<div class='basic-tooltip'>${_('The Grand Bazaar')}</div>` );
            this.addTooltipHtml( 'garden-image', `<div class='basic-tooltip'>${_("Governor's Garden")}</div>` );

            this.addTooltipHtml( 'city-discard', `<div class='basic-tooltip'>${_('City Discard Pile')}</div>` );
            this.addTooltipHtml( 'day-indicator', `<div class='basic-tooltip'>${_('The Current Day')}</div>` );
            this.addTooltipHtml( 'city-day-phase', `<div class='basic-tooltip'>${_('The Current Phase of the Day')}</div>` );
            this.addTooltipHtmlToClass('city-reknown-chip', `<div class='basic-tooltip'>${_('Current Reknown on this City Location')}</div>` );

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
                    crewcap: player.leader?.modifiedCrewCap ?? '',
                    panache: player.leader?.modifiedPanache ?? '',
                    faction: player.leader?.faction.toLowerCase() ?? '',
                });
                this.addTooltipHtml( `${playerId}-score-reknown`, `<div class='basic-tooltip'>${_('Current Reknown')}</div>` );
                this.addTooltipHtml( `${playerId}-score-crewcap`, `<div class='basic-tooltip'>${_('Current Crew Cap')}</div>` );
                this.addTooltipHtml( `${playerId}-score-panache`, `<div class='basic-tooltip'>${_('Current Panache')}</div>` );

                //Display only if we are out of pre-game setup
                if (gamedatas.turnPhase > 0) {
                    // Home
                    this.createHome(playerId, player.color, player.leader);
                    dojo.addClass( `overall_player_board_${playerId}`, `home-${player.leader.faction.toLowerCase()}` );
                    dojo.addClass( `${playerId}-score-seal`, `seal-score seal-${player.leader.faction.toLowerCase()}-score` );
                }

                const playerInfo = this.gamedatas.players[playerId];

                //Pull the home cards out of the gamedatas that are for this player.  homecards are an array that is indexed
                let homeCards = gamedatas.homeCards.filter((card) => card.controllerId === parseInt(playerId));

                //Display the scheme first
                const scheme = homeCards.find((card) => card.type === 'Scheme');
                if (scheme)
                {
                    homeCards = homeCards.filter((card) => card.type !== 'Scheme');
                    const divId = `${playerId}-${scheme.id}`;
                    this.createSchemeCard(divId, scheme, playerId + '-scheme-anchor');
                }

                //Display the leader next
                const leader = homeCards.find((card) => card.type === 'Leader');
                if (leader)
                {
                    homeCards = homeCards.filter((card) => card.type !== 'Leader');
                    const divId = `${playerId}-${leader.id}`;
                    this.createCharacterCard(divId, playerInfo.color, leader, playerId + '-home-anchor');
                }
            
                //Display the rest of the cards
                for( const index in homeCards )
                {
                    const card = homeCards[index];
                    const divId = `${playerId}-${card.id}`;
                    this.createCharacterCard(divId, playerInfo.color, card, playerId + '-home-anchor');
                }
        
            }

            // Display the first player marker if there is one
            if (gamedatas.firstPlayer) {
                dojo.addClass(`${gamedatas.firstPlayer}-first-player`, 'first-player-home');

                dojo.removeClass(`${gamedatas.firstPlayer}-score-seal-first-player`, 'first-player-hidden');
                dojo.addClass(`${gamedatas.firstPlayer}-score-seal-first-player`, 'first-player-score');

                this.addTooltipHtmlToClass('first-player-home', `<div class='basic-tooltip'>${_('First Player')}</div>` );
                this.addTooltipHtmlToClass('first-player-score', `<div class='basic-tooltip'>${_('First Player')}</div>` );
            }

            // Set up Ole's inn
            for( const index in gamedatas.oleCards )
            {
                const card = gamedatas.oleCards[index];
                const cardId = `olesinn-${card.id}`;
                this.createCard(cardId, card, 'oles-inn-endcap');
            }
            if (gamedatas.locationReknown[this.LOCATION_CITY_OLES_INN] != null) {
                $('oles-inn-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_OLES_INN];
                $('oles-inn-image').setAttribute('data-location', this.LOCATION_CITY_OLES_INN);            
            }

            // Set up The Docks
            for( const index in gamedatas.dockCards )
            {
                const card = gamedatas.dockCards[index];
                const cardId = `docks-${card.id}`;
                this.createCard(cardId, card, 'dock-endcap');
            }
            $('dock-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_DOCKS];
            $('dock-image').setAttribute('data-location', this.LOCATION_CITY_DOCKS);

            // Set up The Forum
            for( const index in gamedatas.forumCards )
            {
                const card = gamedatas.forumCards[index];
                const cardId = `forums-${card.id}`;
                this.createCard(cardId, card, 'forum-endcap');
            }
            $('forum-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_FORUM];
            $('forum-image').setAttribute('data-location', this.LOCATION_CITY_FORUM);
                
            // Set up cards in the bazaar
            for( const index in gamedatas.bazaarCards )
            {
                const card = gamedatas.bazaarCards[index];
                const cardId = `bazaar-${card.id}`;
                this.createCard(cardId, card, 'bazaar-endcap');
            }
            $('bazaar-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_BAZAAR];
            $('bazaar-image').setAttribute('data-location', this.LOCATION_CITY_BAZAAR);

            // Set up cards in the governors garden
            for( const index in gamedatas.gardenCards )
            {
                const card = gamedatas.gardenCards[index];
                const cardId = `garden-${card.id}`;
                this.createCard(cardId, card, 'garden-endcap');
            }
            if (gamedatas.locationReknown[this.LOCATION_CITY_GOVERNORS_GARDEN] != null) {
                $('garden-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_GOVERNORS_GARDEN];
                $('garden-image').setAttribute('data-location', this.LOCATION_CITY_GOVERNORS_GARDEN);
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
                this.addCardToDeck(this.approachDeck, card);
            });
            this.approachDeck.setSelectionMode(0);

            // Create the faction hand
            this.factionHand = new ebg.stock();
            this.factionHand.create( this, $('factionHand'), this.wholeCardWidth, this.wholeCardHeight ); 
            this.factionHand.image_items_per_row = 0;
            this.factionHand.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
            this.factionHand.onItemCreate = dojo.hitch( this, 'setupNewStockApproachCard' ); 
            this.factionHand.setSelectionAppearance( 'class' )
            dojo.connect( this.factionHand, 'onChangeSelection', this, 'onFactionCardSelected' );
            // For each card in the approach deck, create a stock item
            gamedatas.factionHand.forEach((card) => {
                this.addCardToDeck(this.factionHand, card);
            });
            this.factionHand.setSelectionMode(0);

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
            else if (card.type === 'Scheme') {
                this.createSchemeCard(divId, card, targetDiv);
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
                case 'dawnBeginning':
                    $('city-day-phase').innerHTML = 'Dawn';
                    dojo.style('city-day-phase', 'display', 'block');
                    break;
        
                case 'planningPhase':
                    $('city-day-phase').innerHTML = 'Planning';
                    //Enable the approach deck
                    this.approachDeck.setSelectionMode(2);
                    break;

                case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
                    if (this.isCurrentPlayerActive()) {
                        const locations = this.getListofAvailableCityLocationImages();
                        locations.forEach((location) => {
                            const imageElement = $(location);
                            const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                            const reknown = parseInt(reknownElement.innerHTML);
                            if (reknown > 0) return;
                
                            dojo.addClass(location, 'selectable');
                            dojo.style(location, 'cursor', 'pointer');
    
                            this.numberOfCityLocationsSelectable = 1;
                            const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                            this.connects.push(handle);
                        });
                    }
                    break;

                case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
                    if (this.isCurrentPlayerActive()) {
                        const locations = this.getListofAvailableCityLocationImages();
                        locations.forEach((location) => {
                            dojo.addClass(location, 'selectable');
                            dojo.style(location, 'cursor', 'pointer');
    
                            this.numberOfCityLocationsSelectable = 1;
                            const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                            this.connects.push(handle);
                        });
                    }
                    break;

                case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
                    if (this.isCurrentPlayerActive()) {
                        const locations = this.getListofAvailableCityLocationImages();
                        locations.forEach((location) => {
                            dojo.addClass(location, 'selectable');
                            dojo.style(location, 'cursor', 'pointer');
    
                            this.numberOfCityLocationsSelectable = 2;
                            const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                            this.connects.push(handle);
                        });
                    }
                    break;

                case 'planningPhaseResolveSchemes_01150':
                    if (this.isCurrentPlayerActive()) {
                        const locations = this.getListofAvailableCityLocationImages();
                        locations.forEach((location) => {
                            if (location == 'forum-image') return;

                            const imageElement = $(location);
                            const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                            const reknown = parseInt(reknownElement.innerHTML);
                            if (reknown === 0) return;
                
                            dojo.addClass(location, 'selectable');
                            dojo.style(location, 'cursor', 'pointer');
    
                            this.numberOfCityLocationsSelectable = 1;
                            const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                            this.connects.push(handle);
                        });
                    }
                    break;

                case 'highDramaPhase':
                    $('city-day-phase').innerHTML = 'High Drama';
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
                case 'planningPhase':
                    this.approachDeck.setSelectionMode(0);
                    break;
                case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
                case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
                case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
                case 'planningPhaseResolveSchemes_01150':
                    const locations = this.getListofAvailableCityLocationImages();
                    locations.forEach((location) => {
                        dojo.removeClass(location, 'selectable');
                        dojo.removeClass(location, 'selected');
                        dojo.style(location, 'cursor', 'default');
                    });
                    break;    
            }

            //Disconnect any connect handlers that were created
            this.connects.forEach((handle) => {
                dojo.disconnect(handle);
            });
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

                    case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
                        this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                        dojo.addClass('actCityLocationsSelected', 'disabled');
                        break;

                    case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
                        this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                        dojo.addClass('actCityLocationsSelected', 'disabled');
                        break;

                    case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
                    case 'planningPhaseResolveSchemes_01150':
                        this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                        this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                        dojo.addClass('actCityLocationsSelected', 'disabled');
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
            // Place the viewing player's home board closest to the city
            dojo.place( this.format_block( 'jstpl_home', {
                id: playerId,
                faction: leader.faction.toLowerCase(),
                crewcap: leader.modifiedCrewCap,
                panache: leader.modifiedPanache,
                player_color: playerColor,
            }), 
            playerId == this.player_id ? 'city' : 'home_anchor', 
            playerId == this.player_id ? 'after' : 'before' );

            this.addTooltipHtml( `${playerId}-crewcap`, `<div class='basic-tooltip'>${_('Current Crew Capacity')}</div>` );
            this.addTooltipHtml( `${playerId}-discard`, `<div class='basic-tooltip'>${_('Faction Deck Discard Pile')}</div>` );
            this.addTooltipHtml( `${playerId}-locker`, `<div class='basic-tooltip'>${_('Player Locker')}</div>` );
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

            const wealthCost = character.wealthCost ? character.wealthCost : '';
            const influence = character.influence >= 0 ? character.influence  : '-';

            dojo.place( this.format_block( 'jstpl_character', {
                id: divId,
                faction: character.faction.toLowerCase(),
                image: character.image,
                player_color: color,
                resolve: character.resolve,
                combat: character.combat,
                finesse: character.finesse,
                influence: influence,
                cost: wealthCost,
            }), location, "before" );

            if (!character.wealthCost) {
                dojo.style( `${divId}-wealth-cost`, 'display', 'none' );
            }

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + character.image}" />`, 100);
        },  

        createEventCard: function( divId, event, location )
        {
            //Set the divId of the card
            event.divId = divId;

            //Add to the card properties cache
            this.cardProperties[event.id] = event;

            dojo.place( this.format_block( 'jstpl_card_event', {
                id: divId,
                image: event.image,
            }), location, "before" );

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + event.image}" />`, 100);

            if (event.reknown > 0) {
                divId = `${divId}-reknown`;
                dojo.place( this.format_block( 'jstpl_reknown_chip', {
                    id: divId,
                    amount: event.reknown,
                }),  event.divId, 'last');
                }
        },  

        createSchemeCard: function( divId, scheme, location )
        {
            //Set the divId of the card
            scheme.divId = divId;

            //Add to the card properties cache
            this.cardProperties[scheme.id] = scheme;

            dojo.place( this.format_block( 'jstpl_card_scheme', {
                id: divId,
                image: scheme.image,
                initiative: scheme.initiative,
                panache: this.formatModifer(scheme.panacheModifier),
            }), location, "after" );

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + scheme.image}" />`, 100);
        },  

        createAttachmentCard: function( divId, color, attachment, location )
        {
            //Set the divId of the card
            attachment.divId = divId;

            //Add to the card properties cache
            this.cardProperties[attachment.id] = attachment;

            dojo.place( this.format_block( 'jstpl_card_attachment', {
                id: divId,
                faction: attachment.faction.toLowerCase(),
                image: attachment.image,
                player_color: color,
                resolve: this.attachmentFormatModifer(attachment.resolveModifier),
                combat: this.attachmentFormatModifer(attachment.combatModifier),
                finesse: this.attachmentFormatModifer(attachment.finesseModifier),
                influence: this.attachmentFormatModifer(attachment.influenceModifier),
                cost: attachment.wealthCost,
            }), location, "before" );

            this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + attachment.image}" />`, 100);
        },

        formatModifer: function( modifier )
        {
            if (modifier > 0) {
                return `+${modifier}`;
            } else {
                return modifier;
            }
        },

        attachmentFormatModifer: function( modifier )
        {
            if (modifier > 0) {
                return `+${modifier}`;
            } else if (modifier === 0) {
                return '-';
            } else {
                return modifier;
            }
        },

        getListofAvailableCityLocationImages: function()
        {
            const playerCount = Object.keys(this.gamedatas.players).length;
            let locations = ['dock-image', 'forum-image', 'bazaar-image'];
            if (playerCount > 2) {
                locations.push('oles-inn-image');
            }
            if (playerCount > 3) {
                locations.push('garden-image');
            }

            return locations;
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
            this.bgaPerformAction("actPickDeck", { 
                'deck_type':'starter',
                'deck_id':deck_id,
            }).then(() =>  {                
                // What to do after the server call if it succeeded
            });        
        },    

        onCityLocationsSelected: function() {
            let act = '';
            switch (this.gamedatas.gamestate.name) {
                case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
                case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
                case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
                    act = 'actCityLocationsForReknownSelected';
                    break;

                case 'planningPhaseResolveSchemes_01150':
                    act = 'actPlanningPhase_01150';
                    break;
            }

            const locations = this.selectedCityLocations.map((loc) => $(loc).getAttribute('data-location'));
            this.bgaPerformAction(act, { 
                'locations': JSON.stringify(locations),
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

        onPass: function()
        {
            this.bgaPerformAction("actPass", { 
            }).then(() =>  {                
                // What to do after the server call if it succeeded
            });
        },

        onCityLocationClicked: function( event )
        {
            const location = event.target.id;
            //Check to see if we are selecting or deselecting
            if (dojo.hasClass(location, 'selected')) 
            {
                dojo.removeClass(location, 'selected');
                this.selectedCityLocations = this.selectedCityLocations.filter((loc) => loc !== location);
            } 
            else 
            {
                if (this.selectedCityLocations.length < this.numberOfCityLocationsSelectable) {
                    dojo.addClass(location, 'selected');
                    this.selectedCityLocations.push(location);
                }
            }

            //Enable the confirm button if we have the right number of locations selected
            if (this.selectedCityLocations.length === this.numberOfCityLocationsSelectable) {
                dojo.removeClass('actCityLocationsSelected', 'disabled');
            } else {
                dojo.addClass('actCityLocationsSelected', 'disabled');
            }
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

        // Utlity functions

        addCardToDeck: function( deck, card )
        {
            this.cardProperties[card.id] = card;

            //Different weight depending on the type. Scheme cards go first
            const weight = card.type === "Scheme" || card.type === 'Attachment'? 1 : 2;

            //Each card is a different image, so would be considered a different type for the stock object
            deck.addItemType(card.id, weight, g_gamethemeurl + card.image, 0);

            // Type and id are the same for the approach deck stock object
            deck.addToStockWithId(card.id, card.id);
        },

        setupNewStockApproachCard: function( cardDiv, cardTypeId, cardId )
        {
            const card = this.cardProperties[cardTypeId];
            this.addTooltipHtml( cardDiv.id, `<img src="${g_gamethemeurl + card.image}" />`, 100);
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

        onFactionCardSelected: function( control_name, item_id )
        {
            var items = this.factionHand.getSelectedItems();
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
                ['approachCardsReceived', 1000],
                ['newDay', 1000],
                ['cityCardAddedToLocation', 500],
                ['playApproachScheme', 2000],
                ['playApproachCharacter', 2000],
                ['firstPlayer', 1500],
                ['panacheModified', 1000],
                ['playerReknownUpdated', 500],
                ['reknownUpdatedOnCard', 500],
                ['reknownAddedToLocation', 500],
                ['reknownRemovedFromLocation', 500],
                ['factionCardDraw', 1000],
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
            dojo.addClass( `${args.player_id}-score-seal`, `seal-score seal-${args.leader.faction.toLowerCase()}-score` );
            $(`${args.player_id}-score-crewcap`).innerHTML = args.leader.crewCap;
            $(`${args.player_id}-score-panache`).innerHTML = args.leader.panache;

            $('pagemaintitletext').innerHTML = `${args.player_name} has selected <span style="font-weight:bold">${args.leader.name}</span> as their leader`;
        },

        notif_playApproachScheme: function( notif )
        {
            console.log( 'notif_playApproachScheme' );
            console.log( notif );

            const args = notif.args;

            this.createCard(`${args.player_id}-${args.scheme.id}`, args.scheme, `${args.player_id}-scheme-anchor`);

            $('pagemaintitletext').innerHTML = `${args.player_name} has selected <span style="font-weight:bold">${args.scheme.name}</span> as their Scheme today`;
        },

        notif_playApproachCharacter: function (notif) {
            console.log( 'notif_playApproachCharacter' );
            console.log( notif );


            const args = notif.args;

            this.createCard(`${args.player_id}-${args.character.id}`, args.character, `${args.player_id}-home-anchor`);

            $('pagemaintitletext').innerHTML = `${args.player_name} has selected <span style="font-weight:bold">${args.character.name}</span> as their Approach Character today`;
        },

        notif_panacheModified: function( notif )
        {
            console.log( 'notif_panacheModified' );
            console.log( notif );

            const args = notif.args;
            $(`${args.playerId}-score-panache`).innerHTML = args.panache;
            $(`${args.playerId}-panache`).innerHTML = args.panache;
        },

        notif_approachCardsReceived: function( notif )
        {
            console.log( 'notif_approachCardsReceived' );
            console.log( notif );

            notif.args.cards.forEach((card) => {
                this.addCardToDeck(this.approachDeck, card);
            });            
        },

        notif_factionCardDraw: function( notif )
        {
            console.log( 'notif_factionCardDraw' );
            console.log( notif );

            notif.args.cards.forEach((card) => {
                this.addCardToDeck(this.factionHand, card);
            });            
        },

        notif_newDay: function( notif )
        {
            console.log( 'notif_newDay' );
            console.log( notif );

            const args = notif.args;

            $('day-indicator').innerHTML = args.day;
            dojo.style('day-indicator', 'display', 'block');
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
                    cardId = `garden-${card.id}`;
                    location = 'garden-endcap';
                    break;
            }

            this.createCard(cardId, card, location);

        },

        notif_playerReknownUpdated: function( notif )
        {
            console.log( 'notif_playerReknownUpdated' );
            console.log( notif );

            const args = notif.args;
            $(`${args.playerId}-score-reknown`).innerHTML = args.amount;
        },

        notif_reknownUpdatedOnCard: function( notif )
        {
            console.log( 'notif_reknownUpdatedOnCard' );
            console.log( notif );

            const args = notif.args;

            const card = this.cardProperties[args.cardId];
            const divId = `${card.divId}-reknown`;
            ////Delete the old element if exists
            if ($(divId)) {                
                dojo.destroy(divId);
            } 

            dojo.place( this.format_block( 'jstpl_reknown_chip', {
                id: divId,
                amount: args.amount,
            }),  card.divId, 'last');
        },

        notif_reknownAddedToLocation: function( notif )
        {
            console.log( 'notif_reknownAddedToLocation' );
            console.log( notif );

            const args = notif.args;
            //Find the image element with the attribute data-location that matches arg.location
            const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
            //Find the element with the class city-reknown-chip that is a child of the element's parent
            const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
            const reknown = parseInt(reknownElement.innerHTML) + args.amount;
            reknownElement.innerHTML = reknown;
        },

        notif_reknownRemovedFromLocation: function( notif )
        {
            console.log( 'notif_reknownRemovedFromLocation' );
            console.log( notif );

            const args = notif.args;
            //Find the image element with the attribute data-location that matches arg.location
            const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
            //Find the element with the class city-reknown-chip that is a child of the element's parent
            const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
            const reknown = parseInt(reknownElement.innerHTML) - args.amount;
            reknownElement.innerHTML = reknown;
        },

        notif_firstPlayer: function( notif )
        {
            console.log( 'notif_firstPlayer' );
            console.log( notif );

            //Remove any existing first player classes
            dojo.query('.first-player-home').removeClass('first-player-home');
            dojo.query('.first-player-score').removeClass('first-player-score');

            //Add the new classes
            const args = notif.args;
            dojo.addClass(`${args.playerId}-first-player`, 'first-player-home');
            dojo.removeClass(`${args.playerId}-score-seal-first-player`, 'first-player-hidden');
            dojo.addClass(`${args.playerId}-score-seal-first-player`, 'first-player-score');

            $('pagemaintitletext').innerHTML = `${args.player_name} is now the First Player`;
        },
    });      
});
