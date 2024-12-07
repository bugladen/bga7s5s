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

 var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
 var debug = isDebug ? console.info.bind(window.console) : function () {};
 
 define([
    "dojo",
    "dojo/_base/declare", 
    "dojo/dom-class",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    g_gamethemeurl + 'modules/js/OnEnteringState.js',
    g_gamethemeurl + 'modules/js/Setup.js',
    g_gamethemeurl + 'modules/js/Utilities.js',
    g_gamethemeurl + 'modules/js/Notifications.js',
    g_gamethemeurl + 'modules/js/EventHandlers.js',
    g_gamethemeurl + 'modules/js/PlayerActions.js',
],
function (dojo, declare) {
    return declare(
    "bgagame.seventhseacityoffivesails", 
    [
        ebg.core.gamegui, 
        seventhseacityoffivesails.onenteringstate,
        seventhseacityoffivesails.setup,
        seventhseacityoffivesails.utilities,
        seventhseacityoffivesails.notifications,
        seventhseacityoffivesails.eventhandlers,
        seventhseacityoffivesails.actions,
    ],
    {
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
            this.numberOfCharactersSelectable = 0;
            this.selectedCityLocations = [];
            this.selectedCharacters = [];

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
                case 'planningPhaseResolveSchemes_01125_1': 
                case 'planningPhaseResolveSchemes_01125_2': 
                case 'planningPhaseResolveSchemes_01125_3':
                case 'planningPhaseResolveSchemes_01126_1':
                case 'planningPhaseResolveSchemes_01126_2':
                case 'planningPhaseResolveSchemes_01150':
                    const locations = this.getListofAvailableCityLocationImages();
                    locations.forEach((location) => {
                        dojo.removeClass(location, 'selectable');
                        dojo.removeClass(location, 'selected');
                        dojo.style(location, 'cursor', 'default');
                    });
                    break;

                case 'planningPhaseResolveSchemes_01125_4':
                    for( const cardId in this.cardProperties ) {
                        card = this.cardProperties[cardId];
                        if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                            const imageElement = dojo.query('.card', card.divId)[0];
                            dojo.removeClass(imageElement, 'selectable');
                            dojo.removeClass(imageElement, 'selected');
                            dojo.style(imageElement, 'cursor', 'default');
                        }
                    }
                    break;

                case 'planningPhaseResolveSchemes_01044':
                case 'planningPhaseResolveSchemes_01045':
                    dojo.addClass('choose-container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    break;
            }

            this.selectedCityLocations = [];
            this.selectedCharacters = [];

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
                    case 'planningPhaseResolveSchemes_01126_1': 
                    case 'planningPhaseResolveSchemes_01126_2': 
                        this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                        dojo.addClass('actCityLocationsSelected', 'disabled');
                        break;

                    case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
                    case 'planningPhaseResolveSchemes_01125_3':
                        this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                        dojo.addClass('actCityLocationsSelected', 'disabled');
                        break;

                    case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
                    case 'planningPhaseResolveSchemes_01125_1': 
                    case 'planningPhaseResolveSchemes_01125_2': 
                    case 'planningPhaseResolveSchemes_01150':
                        this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                        this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                        dojo.addClass('actCityLocationsSelected', 'disabled');
                        break;

                    case 'planningPhaseResolveSchemes_01044':
                    case 'planningPhaseResolveSchemes_01045':
                        this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseStockCardConfirmed());
                        this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                        dojo.addClass('actChooseCardSelected', 'disabled');
                        break;

                    case 'planningPhaseResolveSchemes_01125_4':
                        this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseCharacterConfirmed());
                        this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                        dojo.addClass('actChooseCardSelected', 'disabled');
                        break;


                    case 'playerTurn':
                        break;
                }
            }
        },        

    });      
});
