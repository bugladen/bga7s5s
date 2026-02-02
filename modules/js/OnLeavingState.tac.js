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
            'duelChooseTechnique_02006': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
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
                }
            },
        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});
 