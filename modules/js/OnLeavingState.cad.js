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
    return declare('seventhseacityoffivesails.onleavingstate_cad', null, {

    onLeavingState_cad: function( stateName )
    {
        const methods = {

            'highDramaPhase05DabneyUS01': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase05Overcoat': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    if (this.clientStateArgs.locationIds && this.clientStateArgs.locationIds.length > 0) {
                        this.resetCityLocations();
                        this.selectedCityLocations = [];
                        this.numberOfCityLocationsSelectable = 0;
                    }
                    this.clientStateArgs = {};
                }
            },

            'planningPhaseResolveSchemes_05William': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.ids && this.clientStateArgs.ids.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    this.clientStateArgs = {};
                }
            },

            'planningPhaseResolveSchemes_05William_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    if (this.clientStateArgs.characterId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'planningPhaseResolveSchemes_05William_3': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.ids && this.clientStateArgs.ids.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    if (this.clientStateArgs.characterId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'planningPhaseResolveSchemes_05William_4': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    if (this.clientStateArgs.chosenAttachmentId) {
                        const card = this.cardProperties[this.clientStateArgs.chosenAttachmentId];
                        if (card) {
                            const image = $(`${card.divId}_image`);
                            dojo.removeClass(image, '_7sfs-chosen');
                            const costDiv = $(`${card.divId}_wealth_cost`);
                            if (costDiv) {
                                costDiv.innerHTML = card.wealthCost;
                                dojo.removeClass(costDiv, '_7sfs-discounted-wealth-cost');
                            }
                        }
                    }
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase05William': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase05William_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    if (this.clientStateArgs.attachmentId) {
                        this.unhighlightCards([this.clientStateArgs.attachmentId]);
                    }
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'duelChooseTechnique_05Cooper': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'setupTable_05Gary': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },
            'setupTable_05Gary_2': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
