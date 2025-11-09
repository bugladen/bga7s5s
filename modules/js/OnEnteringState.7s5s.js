/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
    return declare('seventhseacityoffivesails.onenteringstate_7s5s', null, {

    // 7s5s Core Set methods only
    onEnteringState_7s5s: function( stateName, args )
    {
        const methods = {
            'setupTable_01006': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Red Hand Thugs in Your Faction Deck');

                    // For each Brute card in the players deck, create a stock item
                    args.args._private.args.thugs.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },
            'setupTable_01006_2': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Chosen Red Hand Thug');
    
                //Wait a second for stock object to catch up?
                setTimeout(() => {
                    this.addCardToDeck(this.chooseList, args.args.args.card);
                    this.chooseList.setSelectionMode(0);
                }, 500);
            },

            'planningPhaseResolveSchemes_01016': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01016_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Red Hand Thugs in Your Faction Deck');
    
                    // For each Red Hand Thug card in the players deck, create a stock item
                    args.args._private.args.thugs.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },
    
            'planningPhaseResolveSchemes_01016_3': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Chosen Red Hand Thug');
    
                //Wait a second for stock object to catch up?
                setTimeout(() => {
                    this.addCardToDeck(this.chooseList, args.args.card);
                    this.chooseList.setSelectionMode(0);
                }, 500);
            },    
    
            'planningPhaseResolveSchemes_01044': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Your Discard Pile');
    
                    // For each card in the players discard pile, create a stock item
                    const player = this.gamedatas.players[this.getActivePlayerId()];      
                    player.discard.forEach((card) => {
                        if (card.type === 'Attachment')
                            this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
    
                    if (this.chooseList.count() > 0) 
                        dojo.addClass('actPass', 'disabled');
                }
            },

            'planningPhaseResolveSchemes_01045': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Mercenaries in the City Deck Discard Pile');
    
                    // For each card in the city discard pile, create a stock item
                    this.gamedatas.cityDiscard.forEach((card) => {
                        if (card.traits.includes('Mercenary')) {
                            this.addCardToDeck(this.chooseList, card);
                        }                            
    
                    });
                    this.chooseList.setSelectionMode(1);
    
                    if (this.chooseList.count() > 0) 
                        dojo.addClass('actPass', 'disabled');
                }
            },
    
            'planningPhaseResolveSchemes_01071': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01072': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (reknown > 0) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01098': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01125': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01125_2': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    let count = 0;
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (reknown == 0) return;
    
                        this.makeCityLocationSelectable(location);
                        count++;
                    });
    
                    if (count > 0) {
                        dojo.addClass('actPass', 'disabled');
                    }
                }
            },
    
            'planningPhaseResolveSchemes_01125_3': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];
    
                    const locations = this.getListOfLocationsAdjacentToLocation(selectedLocationElement.id);
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01125_4': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    let count = 0;
                    for( const cardId in this.cardProperties ) {
                        card = this.cardProperties[cardId];
                        if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                            //Get the element that is a child of card.divId with the class 'card'
                            const image = dojo.query('._7sfs-card', card.divId)[0];
                            this.makeCardSelectable(image);
    
                            count++;
                        }
                    }
                    if (count > 0) {
                        dojo.addClass('actPass', 'disabled');
                    }
                }
            },
    
            'planningPhaseResolveSchemes_01126': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofOutermostCityLocations();
                    this.numberOfCityLocationsSelectable = 1;
    
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
                    
    
            'planningPhaseResolveSchemes_01126_2_client': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = $(this.clientStateArgs.selectedCityLocations[0]);
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01143': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01144': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01144_2': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];
    
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01145': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    let count = 0;
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (reknown == 0) return;
    
                        this.makeCityLocationSelectable(location);
                        count++;
                    });
    
                    if (count > 0) {
                        dojo.addClass('actPass', 'disabled');
                    }
                }
            },
    
            'planningPhaseResolveSchemes_01145_2_client': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = $(this.clientStateArgs.selectedCityLocations[0]);
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01147' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
    
                card = this.cardProperties[args.args.args.letsHaggleId];
                let image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.letsHaggleId = args.args.args.letsHaggleId;
    
                var translated = _("Let's Haggle: Revealed Cards: ");
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },
    
            'planningPhaseResolveSchemes_01150': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (location == 'forum-image' || reknown === 0)
                            return;

                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01152': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01152_2': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    let count = 0;
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (reknown == 0) return;
    
                        this.makeCityLocationSelectable(location);
                        count++;
                    });
                }
            },
    
            'planningPhaseResolveSchemes_01152_3': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = dojo.query(`[data-location="${args.args.args.location}"]`)[0];
    
                    const locations = this.getListOfLocationsAdjacentToLocation(selectedLocationElement.id);
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
            
            'planningPhaseEnd_01098_2': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Revealed Card');
    
                // For each card in the players discard pile, create a stock item
                this.addCardToDeck(this.chooseList, args.args.card);
                this.chooseList.setSelectionMode(0);
            },
        
            'highDramaBeginning_01144': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.discount = args.args.discount;
                    for( const cardId in this.cardProperties ) {
                        card = this.cardProperties[cardId];
                        if (card.type === 'Character' && !card.controllerId && this.isCardInCity(card.id) ) {
                            const image = $(`${card.divId}_image`);
                            this.makeCardSelectable(image);
    
                            const cost = $(`${card.divId}_wealth_cost`);
                            let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
                            discountedCost = discountedCost < 0 ? 0 : discountedCost;
                            cost.innerHTML = parseInt(discountedCost);
                            dojo.addClass(cost, '_7sfs-discounted-wealth-cost');
                        }
                    }
                }
            },
    
            'highDramaBeginning_01144_client': () => {
                const card = this.cardProperties[this.clientStateArgs.selectedCards[0]];
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-selectable');
    
                const cost = $(`${card.divId}_wealth_cost`);
                let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                this.clientStateArgs.discountedCost = discountedCost;
                cost.innerHTML = parseInt(discountedCost);
                dojo.addClass(cost, '_7sfs-discounted-wealth-cost');
    
                this.factionHand.setSelectionMode(2);
            },

            'highDramaPhase01008': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Revealed Card');
    
                this.addCardToDeck(this.chooseList, args.args.args.card);
                this.chooseList.setSelectionMode(0);
            },
            'highDramaPhase01008_4': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Revealed Card');
    
                //Wait a second for stock object to catch up?
                setTimeout(() => {
                    this.addCardToDeck(this.chooseList, args.args.args.card);
                    this.chooseList.setSelectionMode(0);
                }, 500);
            },

            'highDramaPhase01011': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01012': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01015': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.schemeId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.schemeId = args.args.args.schemeId;

                    card = this.cardProperties[args.args.args.performerId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },
    
            'highDramaPhase01017': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },

            'highDramaPhase01019': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },

            'highDramaPhase01020': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },

            'highDramaPhase01024': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = _("Thugs in your Discard Pile: ");
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(1);
                }
            },

            'highDramaPhase01025': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },

            'highDramaPhase01026': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },

            'highDramaPhase01028': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },
            'highDramaPhase01028_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = this.MAX_CARDS_SELECTABLE;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                }
            },

            'highDramaPhase01029': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                }
            },

            'highDramaPhase01030': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01034': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },
            'highDramaPhase01034_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.highlightCharacterChosen(args.args.args.targetId);
                    this.clientStateArgs.targetId = args.args.args.targetId;
                }            
            },
    
            'highDramaPhase01035' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
    
                card = this.cardProperties[args.args.args.kasparId];
                let image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.kasparId = args.args.args.kasparId;
    
                var translated = _("Kaspar's Revealed Cards: ");
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },
    
    
            'highDramaPhase01035_3' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                this.addCardToDeck(this.chooseList, args.args.args.character);
    
                var translated = _("Kaspar's Revealed Mercenary: ");
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },
    
            'highDramaPhase01035_4' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                this.addCardToDeck(this.chooseList, args.args.args.character);
    
                var translated = _("Kaspar's Revealed Mercenary: ");
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },

            'highDramaPhase01038' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                let count = 0;
                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                    if (card.type === 'Attachment') 
                        count++;
                });
                var translated = dojo.string.substitute(
                    _("Otto Streit's Revealed Cards: ( ${count} Attachment(s) Found )"),
                    {
                        count: count
                    }
                );
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },
            'highDramaPhase01038_3' : () => {
                if (this.isCurrentPlayerActive()) 
                    {
                        dojo.removeClass('choose_container', 'hidden');
                        dojo.removeClass('chooseList', 'hidden');
                        
                        let count = 0;
                        args.args.args.cards.forEach((card) => {
                            this.addCardToDeck(this.chooseList, card);
                            if (card.type === 'Attachment') 
                                count++;
                            else
                            {
                                let div = this.chooseList.getItemDivId(card.id);
                                dojo.addClass(div, '_7sfs-unselectable');        
                            }
        
                            this.cardProperties[card.id] = card;                
                        });
                        var translated = dojo.string.substitute(
                            _("Otto Streit's Revealed Cards: ( ${count} Attachment(s) Found )"),
                            {
                                count: count
                            }
                        );
                        $('choose_container_name').innerHTML = translated;
                        this.chooseList.setSelectionMode(0);
                        if (count > 0)
                            this.chooseList.setSelectionMode(1);
                    }
                },
    
            'highDramaPhase01044' : () => {
                if (this.isCurrentPlayerActive()) {
                    let card = this.cardProperties[args.args.args.performerId ];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
    
                    card = this.cardProperties[args.args.args.actionCardId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.actionCardId = args.args.args.actionCardId;
                }
            },
    
            'highDramaPhase01044_2' : () => {
                if (this.isCurrentPlayerActive()) {
                    let card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
    
                    card = this.cardProperties[args.args.args.actionCardId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.actionCardId = args.args.args.actionCardId;
    
                    this.numberOfCardsSelectable = 1;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.ids = args.args.args.ids;
                }
            },
            'highDramaPhase01044_3' : () => {
                if (this.isCurrentPlayerActive()) {
                    let card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
    
                    card = this.cardProperties[args.args.args.actionCardId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.actionCardId = args.args.args.actionCardId;
    
                    card = this.cardProperties[args.args.args.opposingCharacterId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.opposingCharacterId = args.args.args.opposingCharacterId;
                }
            },

            'highDramaPhase01046a': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01049': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'highDramaPhase01055': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }

                card = this.cardProperties[args.args.args.performerId];
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.performerId = args.args.args.performerId;
            },
            'highDramaPhase01055_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    card = this.cardProperties[args.args.args.targetId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.targetId = args.args.args.targetId;
                }
            },

            'highDramaPhase01056': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01058': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01059': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01060': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },
            'highDramaPhase01060_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 2;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },
            'highDramaPhase01060_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'highDramaPhase01068': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'highDramaPhase01068_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

            'highDramaPhase01069': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    var translated = dojo.string.substitute(
                        _("(${amount} card(s) to discard)"),
                        {
                            amount: 1
                        }
                    );
                    $('faction_hand_info').innerHTML = translated;
                    this.factionHand.setSelectionMode(1);
    
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01069_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Your Discard Pile');
    
                    // For each card in the players discard pile, create a stock item
                    const player = this.gamedatas.players[this.getActivePlayerId()];      
                    player.discard.forEach((card) => {
                        if (card.type === 'Attachment' && !card.traits.includes('Unique'))
                            this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },

            'highDramaPhase01171': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01172': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },
    
            'highDramaPhase01072' : () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
    
                    args.args.args.targetCardIds.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.targetCardIds = args.args.args.targetCardIds;
    
                    card = this.cardProperties[args.args.args.schemeId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    this.clientStateArgs.schemeId = args.args.args.schemeId;
                }
            },
    
            'highDramaPhase01072_2' : () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.chosenCardId];
                    if (card)
                    {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                        this.clientStateArgs.chosenCardId = args.args.args.chosenCardId;
                    }
    
                    card = this.cardProperties[args.args.args.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.addClass(schemeImage, '_7sfs-chosen');
                    this.clientStateArgs.schemeId = args.args.args.schemeId;
    
                    this.showApproachDeckAtTop();
                    this.approachDeck.setSelectionMode(1);
    
                    items = this.approachDeck.getAllItems();
                    items.forEach((item) => {
                        card = this.cardProperties[item.id];
                        if (card.type === 'Scheme') {
                            let div = this.approachDeck.getItemDivId(item.id);
                            dojo.addClass(div, '_7sfs-unselectable');
                        }
                    });
                }
            },

            'highDramaPhase01076': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01076_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }

                card = this.cardProperties[args.args.args.performerId];
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.performerId = args.args.args.performerId;
            },

            'highDramaPhase01081': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'highDramaPhase01085': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    args.args.args.charactersIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.charactersIds = args.args.args.charactersIds;
                }
            },

            'highDramaPhase01086': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

            'highDramaPhase01091': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 2;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },
            'highDramaPhase01091_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsChosen(args.args.args.ids);

                    this.factionHand.setSelectionMode(1);
                }            
            },

            'highDramaPhase01092': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01093': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase01097': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01102': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(2);
                }
            },

            'highDramaPhase01104': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01105': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01106_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Risks in ${opponentName}'s Discard Pile"),
                        {
                            opponentName: args.args.args.opponentName
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(0);
                }
            },
            
            'highDramaPhase01111': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    $('choose_container_name').innerHTML = _("Cards in Your Discard Pile: ");
                    this.chooseList.setSelectionMode(2);
                }
            },
            'highDramaPhase01111_3': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    $('choose_container_name').innerHTML = _("Chosen Cards to Research");
                    this.chooseList.setSelectionMode(1);
                }
            },

            'highDramaPhase01112': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01113_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    args.args.args.attachments.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                    
                    var translated = dojo.string.substitute(
                        _("Attachments in ${opponentName}'s Discard Pile: "),
                        {
                            opponentName: args.args.args.opponentName
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(1);
                }
            },
            'highDramaPhase01113_3': () => {
                if (this.isCurrentPlayerActive()) 
                    {
                        const cardId = args.args.args.cardId;
                        const div = this.factionHand.getItemDivId(cardId);
                        dojo.addClass(div, '_7sfs-unselectable');
            
                        dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                            id: div,
                            cost: args.args.args.cost,
                        }), div, "first" );    
        
                        const costDiv = $(`${div}_wealth_cost`);
                        const cost = parseInt(costDiv.innerHTML);
                        let discountedCost = cost - args.args.args.discount;
                        discountedCost = discountedCost < 0 ? 0 : discountedCost;
                        if (discountedCost !== cost)
                        {
                            costDiv.innerHTML = parseInt(discountedCost);
                            dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                        }
        
                        $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                        this.factionHand.setSelectionMode(2);
                    }
            },

            'highDramaPhase01115': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase01147': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.numberOfCardsSelectable = 1;
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
    
                    args.args.args.attachmentsInPlay.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.attachmentsInPlay = args.args.args.attachmentsInPlay;
                }
            },

            'highDramaPhase01148': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.addClass(schemeImage, '_7sfs-chosen');
                    this.clientStateArgs.schemeId = args.args.args.schemeId;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    args.args.args.charactersIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.charactersIds = args.args.args.charactersIds;
                }
            },
            'highDramaPhase01148_3': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    card = this.cardProperties[args.args.args.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.addClass(schemeImage, '_7sfs-chosen');
                    this.clientStateArgs.schemeId = args.args.args.schemeId;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    card = this.cardProperties[args.args.args.targetId];
                    if (card)
                    {
                        const targetImage = $(`${card.divId}_image`);
                        dojo.addClass(targetImage, '_7sfs-chosen');
                        this.clientStateArgs.targetId = args.args.args.targetId;
                    }
    
                    this.factionHand.setSelectionMode(1);
                }
            },
            'highDramaPhase01148_4': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    card = this.cardProperties[args.args.args.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.addClass(schemeImage, '_7sfs-chosen');
                    this.clientStateArgs.schemeId = args.args.args.schemeId;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    card = this.cardProperties[args.args.args.targetId];
                    if (card)
                    {
                        const targetImage = $(`${card.divId}_image`);
                        dojo.addClass(targetImage, '_7sfs-chosen');
                        this.clientStateArgs.targetId = args.args.args.targetId;
                    }
    
                    this.factionHand.setSelectionMode(1);
                }
            },                
    
            'highDramaPhase01149': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    let images = this.getListofAvailableCityLocationImages();
                    images.forEach((location) => {
                        if (location != 'dock-image')
                        {
                            this.makeCityLocationSelectable(location);
                        }
                    });
                    const performerId = args.args.args.performerId;
                    this.clientStateArgs.performerId = performerId;
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    card = this.cardProperties[args.args.args.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.addClass(schemeImage, '_7sfs-chosen');
                    this.clientStateArgs.schemeId = args.args.args.schemeId;
                }
            },

            'highDramaPhase01152a': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'highDramaPhase01152b': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'highDramaPhase01156': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    var translated = dojo.string.substitute(
                        _("(${amount} card(s) to discard)"),
                        {
                            amount: 1
                        }
                    );
                    $('faction_hand_info').innerHTML = translated;
                    this.factionHand.setSelectionMode(1);
    
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },
            'highDramaPhase01156_2': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.numberOfCardsSelectable = 1;
                    args.args.args.charactersIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.charactersIds = args.args.args.charactersIds;
                }
            },
            'highDramaPhase01156_3': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    card = this.cardProperties[args.args.args.targetId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.targetId = args.args.args.targetId;
                }
            },

            'highDramaPhase01158': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(1);
                }
            },

            'highDramaPhase01160': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase01161': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase01164': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const selectedLocationElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(selectedLocationElement.id);
                    });
                }
            },

            'highDramaPhase01167_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Non-Unique Attachments in ${opponentName}'s Discard Pile: "),
                        {
                            opponentName: args.args.args.opponentName
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(1);
                }
            },

            'highDramaPhase01167_3': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    setTimeout(() => {
                        this.addCardToDeck(this.chooseList, args.args.args.chosenAttachment);
                        const card = args.args.args.chosenAttachment;
                        const chosenAttachmentId = card.id;
                        let div = this.chooseList.getItemDivId(chosenAttachmentId);
            
                        dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                            id: div,
                            cost: card.wealthCost,
                        }), div, "first" );    
            
                        const costDiv = $(`${div}_wealth_cost`);
                        const cost = parseInt(costDiv.innerHTML);
                        let discountedCost = cost - args.args.args.discount;
                        discountedCost = discountedCost < 0 ? 0 : discountedCost;
                        if (discountedCost !== cost)
                        {
                            costDiv.innerHTML = parseInt(discountedCost);
                            dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                        }
    
                    }, 500);

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
    
                    $('choose_container_name').innerHTML = _(`Chosen Attachment to Equip`);
                    this.chooseList.setSelectionMode(0);
        
                    $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                    this.factionHand.setSelectionMode(2);
                }            
            },
    
            'highDramaPhase01180' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                let count = 0;
                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                    if (card.traits.includes('Artifact')) 
                        count++;
    
                    this.cardProperties[card.id] = card;                
                });
                var translated = dojo.string.substitute(
                    _("Kaj Kousei Artifacts ( ${count} Found )"),
                    {
                        count: count
                    }
                );
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },
    
            'highDramaPhase01180_3' : () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    let count = 0;
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                        if (card.traits.includes('Artifact')) 
                            count++;
                        else
                        {
                            let div = this.chooseList.getItemDivId(card.id);
                            dojo.addClass(div, '_7sfs-unselectable');        
                        }
    
                        this.cardProperties[card.id] = card;                
                    });
                    var translated = dojo.string.substitute(
                        _("Kaj Kousei Artifacts ( ${count} Found )"),
                        {
                            count: count
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(0);
                    if (count > 0)
                            this.chooseList.setSelectionMode(1);
                }
            },
    
            'highDramaPhase01180_4' : () => {
                if (this.isCurrentPlayerActive()) {
                    //Wait a second for stock object to catch up?
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    setTimeout(() => {
                        this.addCardToDeck(this.chooseList, args.args.args.chosenCard);
                    }, 500);
                    $('choose_container_name').innerHTML = _(`Chosen Artifact to Equip`);
                    this.chooseList.setSelectionMode(0);
        
                    this.numberOfCardsSelectable = 1;
    
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                }
            },
    
            'highDramaPhase01180_5': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    setTimeout(() => {
                        this.addCardToDeck(this.chooseList, args.args.args.chosenAttachment);
                        const card = args.args.args.chosenAttachment;
                        const chosenAttachmentId = card.id;
                        let div = this.chooseList.getItemDivId(chosenAttachmentId);
            
                        dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                            id: div,
                            cost: card.wealthCost,
                        }), div, "first" );    
            
                        const costDiv = $(`${div}_wealth_cost`);
                        const cost = parseInt(costDiv.innerHTML);
                        let discountedCost = cost - args.args.args.discount;
                        discountedCost = discountedCost < 0 ? 0 : discountedCost;
                        if (discountedCost !== cost)
                        {
                            costDiv.innerHTML = parseInt(discountedCost);
                            dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                        }
    
                    }, 500);
    
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    $('choose_container_name').innerHTML = _(`Chosen Artifact to Equip`);
                    this.chooseList.setSelectionMode(0);
        
                    $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                    this.factionHand.setSelectionMode(2);
                }
            },
    
            'highDramaPhase01185': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    var translated = dojo.string.substitute(
                        _("(${amount} card(s) to discard)"),
                        {
                            amount: 2
                        }
                    );
                    $('faction_hand_info').innerHTML = translated;
                    this.factionHand.setSelectionMode(2);
    
                    card = this.cardProperties[args.args.args.id];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.id = args.args.args.id;
                }
            },
    
            'highDramaPhase01189a': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locations.forEach((location) => {
                            const selectedLocationElement = this.getCityLocationElement(location);
                            this.makeCityLocationSelectable(selectedLocationElement.id);
                    });
                    const performerId = args.args.args.performerId;
                    this.clientStateArgs.performerId = performerId;
                    card = this.cardProperties[performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                }
            },
    
            'highDramaPhase01189b': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locations.forEach((location) => {
                            const selectedLocationElement = this.getCityLocationElement(location);
                            this.makeCityLocationSelectable(selectedLocationElement.id);
                    });
                    const performerId = args.args.args.performerId;
                    this.clientStateArgs.performerId = performerId;
                    card = this.cardProperties[performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                }
            },
    
            'highDramaPhase01192' : () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                
                let count = 0;
                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                    if (card.type === 'Risk') 
                        count++;
                });
                var translated = dojo.string.substitute(
                    _("Gustavo's Revealed Cards: ( ${count} Risk(s) Found )"),
                    {
                        count: count
                    }
                );
                $('choose_container_name').innerHTML = translated;
                this.chooseList.setSelectionMode(0);
            },
    
            'highDramaPhase01192_3' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    let count = 0;
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                        if (card.type === 'Risk') 
                            count++;
                        else
                        {
                            let div = this.chooseList.getItemDivId(card.id);
                            dojo.addClass(div, '_7sfs-unselectable');        
                        }
    
                        this.cardProperties[card.id] = card;                
                    });
                    var translated = dojo.string.substitute(
                        _("Gustavo's Revealed Cards: ( ${count} Risk(s) Found )"),
                        {
                            count: count
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(0);
                    if (count > 0)
                        this.chooseList.setSelectionMode(1);
                }
            },
    
            'highDramaPhase01194': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    card = this.cardProperties[args.args.args.characterId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },
    
            'highDramaPhase01194_2': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.characterId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.characterId = args.args.args.characterId;
    
                    this.numberOfCardsSelectable = 1;
                    args.args.args.targetCharacterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.targetCharacterIds = args.args.args.targetCharacterIds;
                }
            },
    
            'highDramaPhase01197' : () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    args.args.args.targetCharacterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.clientStateArgs.targetCharacterIds = args.args.args.targetCharacterIds;
                }
            },
    
            'highDramaPhase01197_2' : () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    card = this.cardProperties[args.args.args.chosenCharacterId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.clientStateArgs.chosenCharacterId = args.args.args.chosenCharacterId;
                }
            },
    
            'highDramaPhase01197_3' : () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
    
                    card = this.cardProperties[args.args.args.chosenCharacterId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    args.args.args.targetCharacterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.chosenCharacterId = args.args.args.chosenCharacterId;
                    this.clientStateArgs.targetCharacterIds = args.args.args.targetCharacterIds;
                }
            },
    
            'highDramaPhase01200_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    const translated = dojo.string.substitute(
                        _("${playerName}'s Approach Deck"),
                        {
                            playerName: args.args._private.args.playerName
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
    
                    // For card in the approach deck, create a stock item
                    args.args._private.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },
    
            'highDramaPhase01205': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.characterId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.characterId = args.args.args.characterId;
    
                    this.numberOfCardsSelectable = 1;
                    args.args.args.targetCharacterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.targetCharacterIds = args.args.args.targetCharacterIds;
                }
            },
    
            'highDramaPhase01205_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locations.forEach((location) => {
                            const selectedLocationElement = this.getCityLocationElement(location);
                            this.makeCityLocationSelectable(selectedLocationElement.id);
                    });
                    const characterId = args.args.args.characterId;
                    this.clientStateArgs.characterId = characterId;
                    card = this.cardProperties[characterId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
    
                    const victimId = args.args.args.victimId;
                    this.clientStateArgs.victimId = victimId;
                    card = this.cardProperties[victimId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                }
            },

            'highDramaChallengeActionResolveTechnique_01063': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'duelChooseTechnique_01036': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const selectedLocationElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(selectedLocationElement.id);
                    });
                }
            },

            'duelChooseTechnique_01093': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(1);
                }
            },

            'duelResolveManeuver_01051': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args._private.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args._private.args.characterIds;
                }
            },

            'duelChooseTechnique_01063': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'duelResolveManeuver_01059': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args._private.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    card = this.cardProperties[args.args._private.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args._private.args.performerId;
                }
            },

            'duelResolveManeuver_01077': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Revealed Faction Deck Cards');
    
                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(0);
            },
            'duelResolveManeuver_01077_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Revealed Cards in Your Faction Deck');
    
                    // For each card in the players deck, create a stock item
                    setTimeout(() => {
                        args.args._private.args.cards.forEach((card) => {
                            this.addCardToDeck(this.chooseList, card);
                        });
                        this.chooseList.setSelectionMode(1);
                    }, 500);
                }
            },

            'duelResolveManeuver_01108': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(1);
                }
            },

            'duelResolveManeuver_01113_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    const cardId = args.args.args.attachmentId;
                    const div = this.factionHand.getItemDivId(cardId);
                    dojo.addClass(div, '_7sfs-unselectable');
        
                    dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                        id: div,
                        cost: args.args.args.cost,
                    }), div, "first" );    
    
                    const costDiv = $(`${div}_wealth_cost`);
                    const cost = parseInt(costDiv.innerHTML);
                    let discountedCost = cost - args.args.args.discount;
                    discountedCost = discountedCost < 0 ? 0 : discountedCost;
                    if (discountedCost !== cost)
                    {
                        costDiv.innerHTML = parseInt(discountedCost);
                        dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                    }
    
                    $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                    this.factionHand.setSelectionMode(2);
                }
            },

            'duelResolveManeuver_01115': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(1);
                }
            },

            'duelResolveManeuver_01164': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const selectedLocationElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(selectedLocationElement.id);
                    });
                }
            },
        
            'duelApplyCombatCardStats_01085': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                }
            },

            'duelEndOfRound_01031': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },
    
            'duskPhaseBegin01177' : () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId()) {
                            const image = $(`${card.divId}_image`);
                            this.makeCardSelectable(image);
                        }
                    });
                }
            },
    
            'duskPhaseBegin01177_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Penya Shows the Way');
    
                    args.args._private.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(2);
                }
            },

        };

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});