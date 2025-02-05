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
    g_gamethemeurl + 'modules/js/OnUpdateActionButtons.js',
    g_gamethemeurl + 'modules/js/OnLeavingState.js',
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
        seventhseacityoffivesails.onleavingstate,
        seventhseacityoffivesails.onupdateactionbuttons,
        seventhseacityoffivesails.setup,
        seventhseacityoffivesails.utilities,
        seventhseacityoffivesails.notifications,
        seventhseacityoffivesails.eventhandlers,
        seventhseacityoffivesails.actions,
    ],
    {
        constructor: function(){

            debug('seventhseacityoffivesails constructor');

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

            this.CARD_TOOLTIP_DELAY = 1000;

            //Card conditions
            this.ADVERSARY_OF_YEVGENI = 'Adversary of Yevgeni';
            this.CHALLENGER = 'Challenger';
            this.DEFENDER = 'Defender';

            //Global array containing cached properties of all the cards this page has had access to
            this.cardProperties = {};

            //City location selection
            this.numberOfCityLocationsSelectable = 0;
            this.numberOfCardsSelectable = 0;
            this.selectedCityLocations = [];
            this.selectedCards = [];
            this.clientStateArgs = {};

            //Connect handlers for the city locations
            this.connects = [];

            this.inDuel = false;
        },
    });      
});
