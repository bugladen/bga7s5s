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
    g_gamethemeurl + 'modules/js/OnEnteringState.7s5s.js',
    g_gamethemeurl + 'modules/js/OnUpdateActionButtons.js',
    g_gamethemeurl + 'modules/js/OnUpdateActionButtons.7s5s.js',
    g_gamethemeurl + 'modules/js/OnLeavingState.js',
    g_gamethemeurl + 'modules/js/OnLeavingState.7s5s.js',
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
        seventhseacityoffivesails.onenteringstate_7s5s,
        seventhseacityoffivesails.onleavingstate,
        seventhseacityoffivesails.onleavingstate_7s5s,
        seventhseacityoffivesails.onupdateactionbuttons,
        seventhseacityoffivesails.onupdateactionbuttons_7s5s,
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
            this.LOCATION_PLAYER_DISCARD = 'Player Discard';
            this.LOCATION_PLAYER_LOCKER = 'Player Locker';

            //Recruit types
            this.NORMAL_RECRUIT_TYPE = 0;
            this.KASPAR_RECRUIT_TYPE = 1;

            //Equip types
            this.NORMAL_EQUIP_TYPE = 0;
            this.SMUGGLED_ITEM_EQUIP_TYPE = 1;
            this.LETS_HAGGLE_EQUIP_TYPE = 2;

            //Recruit types
            this.NORMAL_RECRUIT_TYPE = 0;
            this.KASPAR_RECRUIT_TYPE = 1;

            //Challenge types
            this.NORMAL_CHALLENGE_TYPE = 0;
            this.TRISKELION_CHALLENGE_TYPE = 1;
            this.EPEE_SANGLANTE_CHALLENGE_TYPE = 2;
            this.CAVALIER_HAT_CHALLENGE_TYPE = 3;
            this.DEFENDING_HONOR_CHALLENGE_TYPE = 4;

            this.CARD_TOOLTIP_DELAY = 1000;
            this.STOCK_CARD_TOOLTIP_DELAY = 100;

            //Card conditions
            this.ADVERSARY_OF_YEVGENI = 'Adversary of Yevgeni';
            this.CRYSTAL_EYE_TARGET = 'Crystal Eye Target';
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
            this.duelRound = 0;
        },
    });      
});
