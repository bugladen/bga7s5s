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
    return declare('seventhseacityoffivesails.onenteringstate_cad', null, {

    onEnteringState_cad: function( stateName, args )
    {
        const methods = {

            'highDramaPhase05DabneyUS01': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase05Overcoat': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.locationIds = args.args.args.locationIds || [];
                    if (this.clientStateArgs.locationIds.length > 0) {
                        this.numberOfCityLocationsSelectable = 1;
                        this.selectedCityLocations = [];
                        this.clientStateArgs.locationIds.forEach((locationId) => {
                            const imageElement = this.getCityLocationElement(locationId);
                            this.makeCityLocationSelectable(imageElement);
                        });
                    }
                }
            },

            'planningPhaseResolveSchemes_05William': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'planningPhaseResolveSchemes_05William_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    (args.args.args.locationIds || []).forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    if (args.args.args.characterId) {
                        this.highlightCharacterChosen(args.args.args.characterId);
                        this.clientStateArgs.characterId = args.args.args.characterId;
                    }
                }
            },

            'planningPhaseResolveSchemes_05William_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);

                    if (args.args.args.characterId) {
                        this.highlightCharacterChosen(args.args.args.characterId);
                        this.clientStateArgs.characterId = args.args.args.characterId;
                    }
                }
            },

            'planningPhaseResolveSchemes_05William_4': () => {
                if (this.isCurrentPlayerActive()) {
                    // WHY: Prototype CAD — plain strings, no _() (hide from translators).
                    // Equip-from-play idiom: highlight city Artifact + performer; hand pays.
                    const statusBarTitle = (args.descriptionmyturn || '').replace('#{cost}', '${cost}');
                    this.bga.statusBar.setTitle(statusBarTitle, {
                        cost: this.format_block('jstpl_status_bar_wealth_cost_chip', {
                            cost: Math.max(0, args.args.args.cost - args.args.args.discount),
                        }),
                    });

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    const attachmentId = args.args.args.chosenAttachmentId;
                    const attachment = this.cardProperties[attachmentId];
                    if (attachment) {
                        const image = $(`${attachment.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                        this.clientStateArgs.chosenAttachmentId = attachmentId;

                        const costDiv = $(`${attachment.divId}_wealth_cost`);
                        if (costDiv) {
                            const cost = parseInt(costDiv.innerHTML);
                            let discountedCost = cost - args.args.args.discount;
                            discountedCost = discountedCost < 0 ? 0 : discountedCost;
                            if (discountedCost !== cost) {
                                costDiv.innerHTML = parseInt(discountedCost);
                                dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                            }
                        }
                    }

                    $('faction_hand_info').innerHTML = '(0 Wealth worth of cards selected)';
                    this.factionHand.setSelectionMode('multiple');
                }
            },

            'highDramaPhase05William': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase05William_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                    if (args.args.args.attachmentId) {
                        this.highlightCardsAsChosen([args.args.args.attachmentId]);
                        this.clientStateArgs.attachmentId = args.args.args.attachmentId;
                    }
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'duelChooseTechnique_05Cooper': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'setupTable_05Gary': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = 'Attachments in Your Faction Deck';

                    args.args._private.args.attachments.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },
            'setupTable_05Gary_2': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = 'Chosen Attachment';

                setTimeout(() => {
                    this.addCardToDeck(this.chooseList, args.args.args.card);
                    this.chooseList.setSelectionMode(0);
                }, 500);
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
