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
    return declare('seventhseacityoffivesails.onleavingstate_tac', null, {

    // 7s5s Core Set methods only
    onLeavingState_tac: function( stateName )
    {

        const methods = {

            'planningPhaseResolveSchemes_02005': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02005_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02005_4': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'planningPhaseResolveSchemes_02005_5': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);

                    delete this.addSortTagToCard.order;
                }
            },

            'planningPhaseResolveSchemes_02014': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02015': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'highDramaPhase02001': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode('none');
                }
            },

            'highDramaPhase02001_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    const discardedCard = this.factionHand.getCards().find((card) => card.id === this.clientStateArgs.discardedCardId);
                    if (discardedCard) {
                        const cardElement = this.factionHand.getCardElement(discardedCard);
                        if (cardElement) dojo.removeClass(cardElement, '_7sfs-chosen');
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02002_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase02002_3': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);

                    delete this.addSortTagToCard.order;
                }
            },

            'highDramaPhase02007': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02008': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();

                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.clientStateArgs = {};
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase02008_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02010': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02010_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.fromId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02010_3': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.fromId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.targetId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02013': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode('none');
                }
            },

            'highDramaPhase02013_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02014': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase02014_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);

                    delete this.addSortTagToCard.order;
                }
            },

            'planningPhaseResolveSchemes_02025': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02025_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02035': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02045': () => {
                this.resetCityLocations();
            },

            'planningPhaseResolveSchemes_02045_2': () => {
                this.resetCityLocations();
            },

            'planningPhaseResolveSchemes_02046': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'planningPhaseResolveSchemes_02046_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'highDramaPhase02023': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02023_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.characterId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase02033': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02034': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02034_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02025': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02025_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.characterId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase02036': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02045': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase02036_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.factionHand.setSelectionMode('none');
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02020': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02020_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase02020_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    this.clientStateArgs = {};
                }
            },

            'duelChooseTechnique_02006': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'duskPhaseBegin02024': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

       }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});
 