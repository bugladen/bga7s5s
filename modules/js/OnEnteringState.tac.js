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
    return declare('seventhseacityoffivesails.onenteringstate_tac', null, {

    onEnteringState_tac: function( stateName, args )
    {
        const methods = {

            'planningPhaseResolveSchemes_02005': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
               }
            },

            'planningPhaseResolveSchemes_02005_2': () => {
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

            'planningPhaseResolveSchemes_02005_4': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Top ${count} Cards in ${opponentName}'s Faction Deck"),
                        {
                            opponentName: args.args.args.opponentName,
                            count: args.args.args.cards.length
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(2);
                }
            },

            'planningPhaseResolveSchemes_02005_5': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Top ${count} Cards in ${opponentName}'s Faction Deck"),
                        {
                            opponentName: args.args.args.opponentName,
                            count: args.args.args.cards.length
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(2);
                }
            },

            'planningPhaseResolveSchemes_02014': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    const locations = this.getListofAvailableCityLocationImages();
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (location == 'forum-image' || reknown == 0) return;

                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02015': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 2;
                    const locations = this.getListofAvailableCityLocationImages();
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (reknown > 0) return;
    
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02025': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02025_3': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02035': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = args.args.args.requiredLocationCount;
                    args.args.args.locationIds.forEach((locationId) => {
                        if (locationId == this.LOCATION_PLAYER_HOME) {
                            this.makeHomeEndcapMarkerSelectable();
                        } else {
                            const imageElement = this.getCityLocationElement(locationId);
                            this.makeCityLocationSelectable(imageElement);
                        }
                    });
                }
            },

            'planningPhaseResolveSchemes_02052': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
                        const reknown = parseInt(reknownElement.innerHTML);
                        if (location == 'bazaar-image' || reknown === 0)
                            return;

                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02045': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofOutermostCityLocations();
                    this.numberOfCityLocationsSelectable = 1;

                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02045_2': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = dojo.query(`[data-location="${args.args.args.chosenLocation}"]`)[0];
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id)
                        {
                            this.markCityLocationAsChosen(location);
                            return;
                        }

                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02046': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_02046_2': () => {
                if (this.isCurrentPlayerActive()) {
                    const selectedLocationElement = dojo.query(`[data-location="${args.args.args.chosenLocation}"]`)[0];
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 1;
                    locations.forEach((location) => {
                        const imageElement = $(location);
                        if (imageElement.id == selectedLocationElement.id)
                        {
                            this.markCityLocationAsChosen(location);
                            return;
                        }

                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'highDramaPhase02001': () => {
                if (this.isCurrentPlayerActive())
                {
                    // Filter cards to only those with the Sorcery trait
                    const sorceryCards = this.factionHand.getCards().filter((card) => card.traits.includes('Sorcery'));
                    
                    // Set selection mode and restrict selectable cards to Sorcery cards only
                    this.factionHand.setSelectionMode('single');
                    this.factionHand.setSelectableCards(sorceryCards);
                }
            },

            'highDramaPhase02001_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);

                    // Set the discarded card as selected in factionHand
                    const discardedCard = this.factionHand.getCards().find((card) => card.id === args.args.args.discardedCardId);
                    if (discardedCard) {
                        const cardElement = this.factionHand.getCardElement(discardedCard);
                        if (cardElement) dojo.addClass(cardElement, '_7sfs-chosen');
                        this.clientStateArgs.discardedCardId = args.args.args.discardedCardId;
                    }
                }
            },

            'highDramaPhase02002_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Top 3 Cards in ${opponentName}'s Faction Deck"),
                        {
                            opponentName: args.args.args.opponentName
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(2);
                }
            },

            'highDramaPhase02002_3': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Cards not discarded from the Top 3 in ${opponentName}'s Faction Deck"),
                        {
                            opponentName: args.args.args.opponentName
                        }
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(2);
                }
            },

            'highDramaPhase02007': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase02008': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Risks in your Discard Pile"),
                        {}
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(1);
                }
            },

            'highDramaPhase02008_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase02010': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase02010_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.highlightCharacterChosen(args.args.args.fromId);
                    this.clientStateArgs.fromId = args.args.args.fromId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase02010_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.highlightCharacterChosen(args.args.args.fromId);
                    this.clientStateArgs.fromId = args.args.args.fromId;
                    this.highlightCharacterChosen(args.args.args.targetId);
                    this.clientStateArgs.targetId = args.args.args.targetId;
                }            
            },

            'highDramaPhase02013': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    const availableCards = this.factionHand.getCards().filter((card) => card.traits.includes('Relic') || card.traits.includes('Faith'));
                    
                    this.factionHand.setSelectionMode('single');
                    this.factionHand.setSelectableCards(availableCards);
                }
            },

            'highDramaPhase02013_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'highDramaPhase02014': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Top 4 Cards of the City Deck"),
                        {}
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(2);
                }
            },

            'highDramaPhase02014_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    
                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
        
                    var translated = dojo.string.substitute(
                        _("Remaining City Deck Cards"),
                        {}
                    );
                    $('choose_container_name').innerHTML = translated;
                    this.chooseList.setSelectionMode(2);
                }
            },

            'highDramaPhase02023': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase02023_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        if (locationId == this.LOCATION_PLAYER_HOME)
                        {
                            this.makeHomeEndcapMarkerSelectable();
                        }
                        else
                        {
                            const imageElement = this.getCityLocationElement(locationId);
                            this.makeCityLocationSelectable(imageElement);
                        }
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    card = this.cardProperties[args.args.args.characterId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaPhase02033': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase02034': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase02034_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.highlightCharacterChosen(args.args.args.characterId);
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaPhase02025': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase02025_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        if (locationId == this.LOCATION_PLAYER_HOME)
                        {
                            this.makeHomeEndcapMarkerSelectable();
                        }
                        else
                        {
                            const imageElement = this.getCityLocationElement(locationId);
                            this.makeCityLocationSelectable(imageElement);
                        }
                    });

                    card = this.cardProperties[args.args.args.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    card = this.cardProperties[args.args.args.characterId];
                    image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaPhase02036': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase02045': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Dar Matushki / Poluchatel Cards in Your Faction Deck');

                    args.args._private.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },

            'highDramaPhase02047': () => {
                if (this.isCurrentPlayerActive()) {
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

            'highDramaPhase02036_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.factionHand.setSelectionMode('single');
                    this.factionHand.setSelectableCards(this.factionHand.getCards());

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.highlightCharacterChosen(args.args.args.characterId);
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaPhase02020': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase02020_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.highlightCharacterChosen(args.args.args.characterId);
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaPhase02020_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.highlightCharacterChosen(args.args.args.characterId);
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaPhase02051': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'duelChooseTechnique_02006': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

            'duskPhaseBegin02024': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

     }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});