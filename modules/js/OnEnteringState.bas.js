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
    return declare('seventhseacityoffivesails.onenteringstate_bas', null, {

    onEnteringState_bas: function( stateName, args )
    {
        const methods = {

            'highDramaPhase04cd01': () => {
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

            'highDramaPhase04cd04': () => {
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

            'highDramaPhase04cd09': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.cardId = args.args.args.cardId;
                    const card = this.cardProperties[args.args.args.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }
                }
            },

            'highDramaPhase04cd09_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.cardId = args.args.args.cardId;
                    const card = this.cardProperties[args.args.args.cardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }

                    if (args.args.args.costMode === 2) {
                        var translated = dojo.string.substitute(
                            _("(${amount} card(s) to discard)"),
                            { amount: 1 }
                        );
                        $('faction_hand_info').innerHTML = translated;
                        this.factionHand.setSelectionMode('single');
                    } else {
                        this.numberOfCardsSelectable = 1;
                        this.clientStateArgs.ids = args.args.args.ids;
                        this.highlightCardsAsSelectable(args.args.args.ids);
                    }
                }
            },

            'highDramaPhase04cd09_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    this.clientStateArgs.cardId = args.args.args.cardId;
                    const eventCard = this.cardProperties[args.args.args.cardId];
                    if (eventCard) {
                        const image = $(`${eventCard.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }

                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }
                }
            },

            'duskPhaseBegin04cd11': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        if (card && card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId()) {
                            const image = $(`${card.divId}_image`);
                            this.makeCardSelectable(image);
                        }
                    });
                }
            },

            'highDramaPhase04cd14': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);

                    const card = this.cardProperties[args.args.args.sourceId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }
                    this.clientStateArgs.sourceId = args.args.args.sourceId;
                }
            },

            'highDramaPhase04cd29': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);

                    const card = this.cardProperties[args.args.args.performerId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase04cd01b_2': () => {
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

            'highDramaPhase04cd15': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }

                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });

                    $('choose_container_name').innerHTML = _("Top Cards of Your Faction Deck");
                    this.chooseList.setSelectionMode(2);
                }
            },

            'highDramaPhase04cd15_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }

                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });

                    $('choose_container_name').innerHTML = _("Remaining Faction Deck Cards");
                    this.chooseList.setSelectionMode(2);
                }
            },

            'highDramaPhase04cd15_3': () => {
                if (this.isCurrentPlayerActive()) {
                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }

                    var translated = dojo.string.substitute(
                        _("(${amount} card(s) to discard)"),
                        { amount: 1 }
                    );
                    $('faction_hand_info').innerHTML = translated;
                    this.factionHand.setSelectionMode('single');
                }
            },

            'planningPhaseResolveSchemes_04004': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'planningPhaseResolveSchemes_04024': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'planningPhaseResolveSchemes_04014': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    (args.args.args.locationIds || []).forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

            'planningPhaseResolveSchemes_04025': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    (args.args.args.locationIds || []).forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

            'planningPhaseEnd_04025': () => {
                if (! this.isCurrentPlayerActive()) {
                    return;
                }
                if (! args.args._private || ! args.args._private.args || ! args.args._private.args.cards) {
                    return;
                }

                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Top Cards of Your Deck');

                args.args._private.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(2);
                this.clientStateArgs.cardsToDraw = args.args._private.args.cardsToDraw || 2;
            },

            'planningPhaseResolveSchemes_04015': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_04004_2': () => {
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

            'highDramaPhase04004': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    (args.args.args.locationIds || []).forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });

                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }
                }
            },

            'highDramaPhase04015': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    (args.args.args.locationIds || []).forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

            'highDramaPhase04015_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04005': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04008': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04018': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04018_2': () => {
                this.factionHand.setSelectionMode('single');
            },

            'highDramaPhase04019': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase04009': () => {
                if (this.isCurrentPlayerActive() && args.args.args.performerId) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase04009_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }
                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04011': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04012': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04010': () => {
                if (this.isCurrentPlayerActive() && args.args.args.performerId) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase04010_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');

                    if (args.args.args.performerId) {
                        this.highlightCharacterChosen(args.args.args.performerId);
                        this.clientStateArgs.performerId = args.args.args.performerId;
                    }

                    args.args.args.cards.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });

                    $('choose_container_name').innerHTML = args.args.args.discardPileLabel || _("Discard Pile");
                    this.chooseList.setSelectionMode(2);
                }
            },

            // WHY: Show revealed gamble cards BEFORE Use/Pass (public args, like 03047).
            // Selection mode 0 — display only; decision is Use/Pass buttons.
            'duelGambleRevealed_04010': () => {
                if (!args.args.args || !args.args.args.cards) {
                    return;
                }

                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Gamble Cards');

                args.args.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(0);
            },

            'highDramaPhase04005_2': () => {
                this.factionHand.setSelectionMode('single');
            },

            'highDramaPhase04002': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase04002_3': () => {
                if (this.isCurrentPlayerActive() && args.args.args.intervenerId) {
                    this.highlightCharacterChosen(args.args.args.intervenerId);
                    this.clientStateArgs.intervenerId = args.args.args.intervenerId;
                }
            },

            'duelChooseTechnique_04001': () => {
                // WHY: Look-at-deck is private (argsForStatePrivate) — only active player sees cards.
                if (! this.isCurrentPlayerActive())
                {
                    return;
                }
                if (! args.args._private || ! args.args._private.args || ! args.args._private.args.cards)
                {
                    return;
                }

                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _("Top Cards of Your Faction Deck");

                args.args._private.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(2);
            },

            'duelChooseTechnique_04001_2': () => {
                if (! this.isCurrentPlayerActive())
                {
                    return;
                }
                if (! args.args._private || ! args.args._private.args || ! args.args._private.args.cards)
                {
                    return;
                }

                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _("Remaining Faction Deck Cards");

                args.args._private.args.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(2);
            },

            'duelChooseTechnique_04017': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('single');
                }
            },

        };

        if (methods[stateName])
            methods[stateName](args);
    },

})
});
