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

            'planningPhaseResolveSchemes_03005': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = _('Your Discard Pile');

                    const player = this.gamedatas.players[this.getActivePlayerId()];
                    player.discard.forEach((card) => {
                        if (card.traits && (card.traits.includes('Gang') || card.traits.includes('Crime') || card.traits.includes('Villainous'))) {
                            this.addCardToDeck(this.chooseList, card);
                        }
                    });
                    this.chooseList.setSelectionMode(1);

                    if (this.chooseList.count() > 0)
                        dojo.addClass('actPass', 'disabled');
                }
            },

            'planningPhaseResolveSchemes_03006': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_03017': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

            'planningPhaseResolveSchemes_03030': () => {
                if (this.isCurrentPlayerActive()) {
                    const locations = this.getListofAvailableCityLocationImages();
                    this.numberOfCityLocationsSelectable = 2;
                    locations.forEach((location) => {
                        this.makeCityLocationSelectable(location);
                    });
                }
            },

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

            'highDramaPhase03030': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.diplomatId);
                    this.clientStateArgs.diplomatId = args.args.args.diplomatId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03030_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.duelistId);
                    this.clientStateArgs.duelistId = args.args.args.duelistId;

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

            'highDramaPhase03009': () => {
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

            'highDramaPhase03032': () => {
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

            'highDramaPhase03011': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03034': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03034_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.highlightCharacterChosen(args.args.args.targetId);
                    this.clientStateArgs.targetId = args.args.args.targetId;
                }
            },

            'duelResolveManeuver_03035': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'duelResolveManeuver_03035_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.highlightCharacterChosen(args.args.args.targetId);
                    this.clientStateArgs.targetId = args.args.args.targetId;
                }
            },

            'duelResolveManeuver_03036': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('single');
                }
            },

            'highDramaPhase03020': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03026': () => {
                if (this.isCurrentPlayerActive()) {
                    var translated = dojo.string.substitute(
                        _("(${amount} card(s) to discard)"),
                        {
                            amount: 1
                        }
                    );
                    $('faction_hand_info').innerHTML = translated;
                    this.factionHand.setSelectionMode('single');
                }
            },

            'highDramaPhase03026_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                }
            },

            'highDramaPhase03026_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'highDramaPhase03029': () => {
                if (this.isCurrentPlayerActive()) {
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'highDramaPhase03029_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.characterIds = args.args.args.characterIds;
                    this.highlightCardsAsSelectable(args.args.args.characterIds);
                }
            },

            'highDramaPhase03029_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        if (locationId == this.LOCATION_PLAYER_HOME) {
                            this.makeHomeEndcapMarkerSelectable();
                        } else {
                            const imageElement = this.getCityLocationElement(locationId);
                            this.makeCityLocationSelectable(imageElement);
                        }
                    });

                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.highlightCharacterChosen(args.args.args.characterId);
                    this.clientStateArgs.characterId = args.args.args.characterId;
                }
            },

            'highDramaChallengeActionResolveTechnique_03013': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'duelChooseTechnique_03013': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args.args.characterIds;

                    card = this.cardProperties[args.args.args.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = args.args.args.performerId;
                }
            },

            'duelChooseTechnique_03025b': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCityLocationsSelectable = 1;
                    args.args.args.locationIds.forEach((locationId) => {
                        const imageElement = this.getCityLocationElement(locationId);
                        this.makeCityLocationSelectable(imageElement);
                    });
                    this.clientStateArgs.locationIds = args.args.args.locationIds;
                }
            },

            'duelEndOfRound_03022': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    args.args._private.args.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);
                    });
                    this.clientStateArgs.characterIds = args.args._private.args.characterIds;
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