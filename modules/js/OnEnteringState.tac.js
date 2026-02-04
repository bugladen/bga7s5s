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

            'duelChooseTechnique_02006': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }            
            },

     }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});