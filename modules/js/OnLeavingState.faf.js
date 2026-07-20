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

            'planningPhaseResolveSchemes_03005': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

            'planningPhaseResolveSchemes_03006': () => {
                this.resetCityLocations();
            },

            'planningPhaseResolveSchemes_03017': () => {
                this.resetCityLocations();
            },

            'planningPhaseResolveSchemes_03030': () => {
                this.resetCityLocations();
            },

            'planningPhaseResolveSchemes_03053': () => {
                this.resetCityLocations();
            },

            'planningPhaseEnd_03041': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03042': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    this.clientStateArgs = {};
                }
            },

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

            'highDramaPhase03030': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.diplomatId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03030_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.duelistId);
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

            'highDramaPhase03009': () => {
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

            'highDramaPhase03032': () => {
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

            'highDramaPhase03045': () => {
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

            'highDramaPhase03051': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03054': () => {
                if (this.isCurrentPlayerActive())
                {
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    if (this.clientStateArgs.ids && this.clientStateArgs.ids.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03061': () => {
                if (this.isCurrentPlayerActive())
                {
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    if (this.clientStateArgs.ids && this.clientStateArgs.ids.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03062': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                if (this.isCurrentPlayerActive() && this.clientStateArgs.performerId) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                }
                this.clientStateArgs = {};
            },

            'highDramaPhase03063': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    if (this.clientStateArgs.attachmentsInPlay && this.clientStateArgs.attachmentsInPlay.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.attachmentsInPlay);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03063_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03067': () => {
                if (this.isCurrentPlayerActive()) {
                    if (this.clientStateArgs.performerId) {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaEnd_03061': () => {
                if (this.isCurrentPlayerActive())
                {
                    if (this.clientStateArgs.ids && this.clientStateArgs.ids.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03055': () => {
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

            'highDramaPhase03056': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03060': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03060_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03056_2': () => {
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

            'highDramaPhase03011': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03034': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03037': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03038a': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03038b': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03038b_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03040': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    if (this.clientStateArgs.ids && this.clientStateArgs.ids.length > 0) {
                        this.unhighlightCards(this.clientStateArgs.ids);
                    }
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03034_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.targetId);
                    this.clientStateArgs = {};
                }
            },

            'duelResolveManeuver_03035': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'duelResolveManeuver_03069': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'duelResolveManeuver_03035_2': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.targetId);
                    this.clientStateArgs = {};
                }
            },

            'duelResolveManeuver_03036': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                }
            },

            'duelResolveManeuver_03059': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },

            'duelResolveManeuver_03059_3': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },

            'duelResolveManeuver_03059_4': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
                delete this.addSortTagToCard.order;
            },

            'duelChooseGambleCard_03047': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },

            'highDramaPhase03020': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03026': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                    $('faction_hand_info').innerHTML = '';
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03026_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03026_3': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03029': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03029_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.characterIds);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase03029_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
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

            'highDramaChallengeActionResolveTechnique_03013': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
                }
            },

            'duelChooseTechnique_03013': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
                }
            },

            'duelChooseTechnique_03025b': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    this.selectedCityLocations = [];
                    this.numberOfCityLocationsSelectable = 0;
                }
            },

            'duelChooseTechnique_03039': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                }
            },

            'duelChooseTechnique_03043': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

            'duelChooseTechnique_03043_3': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.factionHand.setSelectionMode('none');
                }
            },

            'duelChooseTechnique_03052': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

            'duskPhaseBegin03052': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

            'duskPhaseBegin03052_2': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

            'duelEndOfRound_03022': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
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