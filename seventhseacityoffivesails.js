/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * seventhseacityoffivesails.js
 *
 * SeventhSeaCityOfFiveSails user interface script
 * 
 */

define([
   "dojo",
   "dojo/_base/declare", 
   "dojo/dom-class",
   "ebg/core/gamegui",
   "ebg/counter",
   "ebg/stock",
   getLibUrl('bga-animations', '1.x'),
   getLibUrl('bga-cards', '1.x'),
   g_gamethemeurl + 'modules/js/vendor/tippy-loader.js',
   g_gamethemeurl + 'modules/js/OnEnteringState.js',
   g_gamethemeurl + 'modules/js/OnEnteringState.7s5s.js',
   g_gamethemeurl + 'modules/js/OnEnteringState.tac.js',
   g_gamethemeurl + 'modules/js/OnUpdateActionButtons.js',
   g_gamethemeurl + 'modules/js/OnUpdateActionButtons.7s5s.js',
   g_gamethemeurl + 'modules/js/OnUpdateActionButtons.tac.js',
   g_gamethemeurl + 'modules/js/OnLeavingState.js',
   g_gamethemeurl + 'modules/js/OnLeavingState.7s5s.js',
   g_gamethemeurl + 'modules/js/OnLeavingState.tac.js',
   g_gamethemeurl + 'modules/js/Setup.js',
   g_gamethemeurl + 'modules/js/Utilities.js',
   g_gamethemeurl + 'modules/js/Notifications.js',
   g_gamethemeurl + 'modules/js/EventHandlers.js',
   g_gamethemeurl + 'modules/js/PlayerActions.js',
   g_gamethemeurl + 'modules/js/Templates.js',
],
function (dojo, declare, domClass, gamegui, counter, stock, BgaAnimations, bgaCards, tippyLoaderPromise)
{
    // Define isDebug and debug globally so all modules can access them
    window.isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
    
    // Store bga-cards classes globally
    // The library exports CardManager as 'Manager' and HandStock directly
    window.CardManager = bgaCards.Manager;
    window.HandStock = bgaCards.HandStock;
    window.LineStock = bgaCards.LineStock;

    window.debug = window.isDebug ? console.info.bind(window.console) : function () {};
    
    // Tippy.js loader returns a Promise that loads Popper.js then Tippy.js sequentially
    // This ensures no race conditions between the two libraries
    // The Promise resolves once both are loaded; initTippy() in Utilities.js handles the timing

    // Store BgaAnimations globally to avoid ReferenceError when used in mixed-in classes
    window.BgaAnimations = BgaAnimations;
    return declare(
    "bgagame.seventhseacityoffivesails", 
    [
        ebg.core.gamegui, 
        seventhseacityoffivesails.onenteringstate,
        seventhseacityoffivesails.onenteringstate_7s5s,
        seventhseacityoffivesails.onenteringstate_tac,
        seventhseacityoffivesails.onleavingstate,
        seventhseacityoffivesails.onleavingstate_7s5s,
        seventhseacityoffivesails.onleavingstate_tac,
        seventhseacityoffivesails.onupdateactionbuttons,
        seventhseacityoffivesails.onupdateactionbuttons_7s5s,
        seventhseacityoffivesails.onupdateactionbuttons_tac,
        seventhseacityoffivesails.setup,
        seventhseacityoffivesails.utilities,
        seventhseacityoffivesails.notifications,
        seventhseacityoffivesails.eventhandlers,
        seventhseacityoffivesails.actions,
        seventhseacityoffivesails.templates,
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

            this.MAX_CARDS_SELECTABLE = 999;

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
            this.CIRILO_RECRUIT_TYPE = 2;

            //Challenge types
            this.NORMAL_CHALLENGE_TYPE = 0;
            this.TRISKELION_CHALLENGE_TYPE = 1;
            this.EPEE_SANGLANTE_CHALLENGE_TYPE = 2;
            this.CAVALIER_HAT_CHALLENGE_TYPE = 3;
            this.DEFENDING_HONOR_CHALLENGE_TYPE = 4;
            this.LEGENDARY_REPUTATION_CHALLENGE_TYPE = 5;
            this.DANIELA_DEITRICH_CHALLENGE_TYPE = 6;
            this.MOVE_ALONG_CHALLENGE_TYPE = 7;
            this.SERVO_SCARPA_CHALLENGE_TYPE = 8;
            this.VERONICAS_GUILLE_CHALLENGE_TYPE = 9;
            this.VALERI_MIKHAILOV_CHALLENGE_TYPE = 10;
            this.IRON_AND_VELVET_CHALLENGE_TYPE = 11;
            this.ANDRIANA_DONDOLOS_CHALLENGE_TYPE = 12;
            this.WILHELM_DUNST_CHALLENGE_TYPE = 13;

            this.CARD_TOOLTIP_DELAY = 1000;
            this.STOCK_CARD_TOOLTIP_DELAY = 500;

            //User preferences
            this.USER_PREFERENCES_CARD_HOVER_TYPE = 100;

            //Card conditions
            this.ADVERSARY_OF_YEVGENI = 'Adversary of Yevgeni';
            this.CRYSTAL_EYE_TARGET = 'Crystal Eye Target';
            this.CATS_EMBARGO_TARGET = 'Cats Embargo Target';
            this.MARYAM_BENU_PLEROMA_ABILITY_USED = 'Maryam Benu Pleroma Ability Used';
            this.INDOMITABLE_WILL_CONDITION = 'Indomitable Will Condition';
            this.CHALLENGER = 'Challenger';
            this.DEFENDER = 'Defender';

            //Global array containing cached properties of all the cards this page has had access to
            this.cardProperties = {};
            this.logCardCache = {};

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

            this.log_span_num = 0;
        },

        format_string_recursive_with_injection: function (log, args) 
        {
            if (args) {
                for (const key in args) {
                    const val = args[key];
                    if (val && typeof val === 'object' && !Array.isArray(val) && val.id && val.type && !this.logCardCache[val.id]) {
                        this.logCardCache[val.id] = val;
                    }
                }
            }
            var result = this.format_string_recursive_original(log, args);
            return this.logInject(result);
        },

        logInject: function (log_entry) {
            // Current format: [card_id:card_type:card_name(image_path)]
            const typed_regex = /\[(\d+?):([^:\[\]]+?):([^\[\]]+?)\(([^()]+?)\)\]/g;
            const typed_matches = log_entry.matchAll(typed_regex);
            for (let card of typed_matches) 
            {
                const cardId = card[1];
                const cardType = card[2];
                const cardName = card[3];
                const cardImage = card[4];
                const cardSpan = this.getHTMLForLog(cardId, cardName, cardImage, 'card', cardType);
                log_entry = log_entry.replace(card[0], cardSpan);
            }

            // Legacy format: [card_id:card_name(image_path)]
            const id_regex = /\[([^:\[\]]+?):([^\[\]]+?)\(([^()]+?)\)\]/g;
            const id_matches = log_entry.matchAll(id_regex);
            for (let card of id_matches) 
            {
                const cardId = card[1];
                const cardName = card[2];
                const cardImage = card[3];
                const cardSpan = this.getHTMLForLog(cardId, cardName, cardImage, 'card');
                log_entry = log_entry.replace(card[0], cardSpan);
            }

            // Legacy format: [card_name(image_path)]
            const old_regex = /\[([^\[\]]+?)\(([^()]+?)\)\]/g;
            const old_matches = log_entry.matchAll(old_regex);
            for (let card of old_matches) 
            {
                const cardName = card[1];
                const cardImage = card[2];
                const cardSpan = this.getHTMLForLog(null, cardName, cardImage, 'card');
                log_entry = log_entry.replace(card[0], cardSpan);
            }
            return log_entry;
        },

        getHTMLForLog: function (cardId, cardName, cardImage, type, cardType) 
        {
            switch(type) 
            {
                case 'card':
                    this.log_span_num++;
                    const item_type = '_7sfs-card_tt';
                    let dataAttrs = `image="${cardImage}"`;
                    if (cardId) {
                        dataAttrs += ` data-card-id="${cardId}"`;
                        const cardData = this.cardProperties[cardId];
                        if (cardData && !this.logCardCache[cardId]) {
                            this.logCardCache[cardId] = Object.assign({}, cardData);
                        }
                    }
                    if (cardType) {
                        dataAttrs += ` data-card-type="${cardType}"`;
                    }
                    return `<span id="${this.log_span_num}_${item_type}" ${dataAttrs} class="${item_type} _7sfs-log_tooltip"><strong>${_(cardName)}</strong></span>`;
            }
        },        

        addTooltipsToLog: function() 
        {
            const item_elements = dojo.query('._7sfs-log_tooltip:not(.tt_processed)');
            Array.from(item_elements).forEach(ele => {
                const ele_id = ele.id;
                ele.classList.add('tt_processed');
                if (ele.classList.contains('_7sfs-card_tt')) 
                {
                    const cardImage = ele.getAttribute('image');
                    const cardId = ele.getAttribute('data-card-id');
                    const cardType = ele.getAttribute('data-card-type');

                    if (this.getGameUserPreference(this.USER_PREFERENCES_CARD_HOVER_TYPE) == 2) {
                        const card = cardId 
                            ? (this.cardProperties[cardId] ?? this.logCardCache[cardId]) 
                            : null;
                        const type = card?.type ?? cardType;
                        if (type) {
                            if (card) {
                                if (type === 'Character') { this.createTextTooltipForCharacter(card, ele_id); return; }
                                if (type === 'Scheme') { this.createTextTooltipForScheme(card, ele_id); return; }
                                if (type === 'Attachment') { this.createTextTooltipForAttachment(card, ele_id); return; }
                                if (type === 'Risk') { this.createTextTooltipForRisk(card, ele_id); return; }
                            } else {
                                const cardName = ele.querySelector('strong')?.textContent ?? '';
                                const html = `<div class='_7sfs-basic-tooltip'>${cardName} (${type})</div>`;
                                this.addTippyTooltip(ele_id, html, this.CARD_TOOLTIP_DELAY);
                                return;
                            }
                        }
                    }

                    this.addTippyTooltip( ele_id, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(cardImage) + cardImage}" />`, this.CARD_TOOLTIP_DELAY);
                }
            });
        },
    });      
});
