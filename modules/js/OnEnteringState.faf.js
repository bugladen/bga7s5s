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
    return declare('seventhseacityoffivesails.onenteringstate_faf', null, {

    onEnteringState_faf: function( stateName, args )
    {
        const methods = {

            'highDramaPhase03cd01': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03cd01_2': () => {
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

            'highDramaPhase03cd03': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }
                }
            },

            'highDramaPhase03cd13': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                        this.clientStateArgs.crabsCardId = args.args.args.cardId;
                    }

                    const performerId = args.args.args.performerId;
                    if (performerId) {
                        this.highlightCharacterChosen(performerId);
                        this.clientStateArgs.performerId = performerId;
                    }
                }
            },

            'highDramaPhase03001': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03002': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03003': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.donId);
                    this.clientStateArgs.donId = args.args.args.donId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03003_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03001_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    this.clientStateArgs.sourceId = args.args.args.sourceId;

                    card = this.cardProperties[args.args.args.sourceId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03cd03_2': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[args.args.args.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }

                    this.showApproachDeckAtTop();
                    this.approachDeck.setSelectionMode(1);

                    const musterIds = args.args.args.musterIds || [];
                    items = this.approachDeck.getAllItems();
                    items.forEach((item) => {
                        if (!musterIds.includes(parseInt(item.id))) {
                            const div = this.approachDeck.getItemDivId(item.id);
                            dojo.addClass(div, '_7sfs-unselectable');
                        }
                    });
                }
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});