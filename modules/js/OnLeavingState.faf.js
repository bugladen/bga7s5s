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
    return declare('seventhseacityoffivesails.onleavingstate_faf', null, {

    // 7s5s Core Set methods only
    onLeavingState_faf: function( stateName )
    {

        const methods = {

            'highDramaPhase03cd01': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03cd01_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.targetId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase03001': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03002': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03003': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.donId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03003_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03001_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);

                    const sourceId = this.clientStateArgs.sourceId;
                    if (sourceId) {
                        const card = this.cardProperties[sourceId];
                        if (card) {
                            const image = $(`${card.divId}_image`);
                            dojo.removeClass(image, '_7sfs-chosen');
                        }
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03cd13': () => {
                if (this.isCurrentPlayerActive()) {
                    const cardId = this.clientStateArgs && this.clientStateArgs.crabsCardId;
                    const card = cardId ? this.cardProperties[cardId] : null;
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        if (image) dojo.removeClass(image, '_7sfs-chosen');
                    }

                    const performerId = this.clientStateArgs && this.clientStateArgs.performerId;
                    if (performerId) {
                        this.unhighlightCharacterChosen(performerId);
                    }
                }
            },

            'highDramaPhase03cd03_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.showApproachDeckAtBottom();
                    this.approachDeck.setSelectionMode(0);

                    const items = this.approachDeck.getAllItems();
                    items.forEach((item) => {
                        const div = this.approachDeck.getItemDivId(item.id);
                        dojo.removeClass(div, '_7sfs-unselectable');
                    });
                }
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});