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
    return declare('seventhseacityoffivesails.onleavingstate_bas', null, {

    onLeavingState_bas: function( stateName )
    {
        const methods = {

            'highDramaPhase04cd01': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    const card = this.cardProperties[this.clientStateArgs.performerId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, '_7sfs-chosen');
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd04': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    const card = this.cardProperties[this.clientStateArgs.performerId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, '_7sfs-chosen');
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd09': () => {
                if (this.isCurrentPlayerActive()) {
                    const card = this.cardProperties[this.clientStateArgs.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, '_7sfs-chosen');
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd09_2': () => {
                if (this.isCurrentPlayerActive()) {
                    const card = this.cardProperties[this.clientStateArgs.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, '_7sfs-chosen');
                    }
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    if (this.clientStateArgs.ids) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd09_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    const card = this.cardProperties[this.clientStateArgs.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, '_7sfs-chosen');
                    }
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd01b_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'duskPhaseBegin04cd11': () => {
                if (this.isCurrentPlayerActive()) {
                    for (const cardId in this.cardProperties) {
                        card = this.cardProperties[cardId];
                        if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                            const image = $(`${card.divId}_image`);
                            this.clearCardAsSelectable(image);
                        }
                    }
                }
            },

            'highDramaPhase04cd14': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.ids) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    if (this.clientStateArgs.sourceId) {
                        const card = this.cardProperties[this.clientStateArgs.sourceId];
                        if (card) {
                            const image = $(`${card.divId}_image`);
                            dojo.removeClass(image, '_7sfs-chosen');
                        }
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd29': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.ids) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    if (this.clientStateArgs.performerId) {
                        const card = this.cardProperties[this.clientStateArgs.performerId];
                        if (card) {
                            const image = $(`${card.divId}_image`);
                            dojo.removeClass(image, '_7sfs-chosen');
                        }
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd15': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd15_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase04cd15_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    this.clientStateArgs = {};
                }
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
