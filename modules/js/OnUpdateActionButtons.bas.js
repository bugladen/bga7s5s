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
    return declare('seventhseacityoffivesails.onupdateactionbuttons_bas', null, {

    onUpdateActionButtons_bas: function( stateName, args )
    {
        const methods = {

            'highDramaPhase04cd01': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04cd04': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04cd09': () => {
                if (args.args.canEngage) {
                    this.addActionButton(`actEngageCost`, _('Engage a Performer'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                }
                if (args.args.canDiscard) {
                    this.addActionButton(`actDiscardCost`, _('Discard a Card'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
                }
            },

            'highDramaPhase04cd09_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                if (args.args.costMode === 2) {
                    this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                } else {
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Engage'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase04cd09_3': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04cd01b': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },

            'highDramaPhase04cd01b_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                args.args.actions.forEach((action) => {
                    this.addActionButton(`actChooseAction-${action.id}`, action.name, () => this.bgaPerformAction('actFromCardWithActionId', {actionSourceId: action.sourceId, actionId: action.id}));
                });
            },

            'duskPhaseBegin04cd11': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Character'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04cd14': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Character'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04cd29': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Character'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04cd15': () => {
                this.addActionButton(`actChooseCardSelected`, _('Sink Selected'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
            },

            'highDramaPhase04cd15_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onCardsSorted());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04cd15_3': () => {
                this.addActionButton(`actChooseDiscardCards`, _('Discard to Draw'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCards', 'disabled');
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
            },

            'planningPhaseResolveSchemes_04004': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_04024': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_04014': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_04025': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseEnd_04025': () => {
                this.addActionButton(`actChooseCardSelected`, _('Draw Selected'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_04015': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_04004_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04004': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04015': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04015_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04005': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04008': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04018': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04018_2': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase04019': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'highDramaPhase04027': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04027_2': () => {
                this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actDecline`, _('Decline and Claim'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase04028': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04028_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04029': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'highDramaPhase04029_2': () => {
                this.addActionButton(`actPayForCards`, _('Confirm'), () => this.onPaymentConfirmedFromCard());
            },

            'highDramaPhase04030': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04032': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04032_2': () => {
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                if (args.args && args.args.canRevealHand) {
                    this.addActionButton('actRevealHand', _('Reveal Hand'), () => this.bgaPerformAction('actFromCardRevealHand', {}));
                }
            },

            'highDramaPhase04032_3': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase04032_4': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.bgaPerformAction('actPass', {}));
            },

            'highDramaPhase04032_5': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onChooseHandCardConfirmed());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'duelResolveManeuver_04030': () => {
                this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actFromCardWithId', { id: 1}));
                this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 2}));
            },

            'highDramaPhase04009': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },

            'highDramaPhase04009_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Character'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04011': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04012': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04010': () => {
                args.args.piles.forEach((pile) => {
                    this.addActionButton(`actChoosePile-${pile.id}`, pile.name, () => this.bgaPerformAction('actFromCardWithId', {id: pile.id}));
                });
            },

            'highDramaPhase04010_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
            },

            'duelGambleRevealed_04010': () => {
                if (!this.isCurrentPlayerActive()) {
                    return;
                }
                this.addActionButton(`actUse`, _('Use'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
            },

            'highDramaPhase04005_2': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase04002': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase04002_3': () => {
                this.addActionButton(`actWound`, _('Wound Intervener'), () => this.bgaPerformAction('actFromCardWithId', { id: 0 }));
                this.addActionButton(`actDraw`, _('Draw a Card'), () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
            },

            'duelChooseTechnique_04001': () => {
                this.addActionButton(`actChooseCardSelected`, _('Sink Selected'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
            },

            'duelChooseTechnique_04001_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onCardsSorted());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_04013': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'duelChooseTechnique_04021': () => {
                args.args.techniques.forEach((technique) => {
                    this.addActionButton(`actChooseTechnique-${technique.id}`, technique.name, () => this.bgaPerformAction('actFromCardWithIds', {ids: JSON.stringify([technique.id])}));
                });
            },

            'duelChooseTechnique_04017': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'duelChooseTechnique_04033': () => {
                this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actFromCardWithId', { id: 0 }));
                this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
            },

            'duelNewRound_04033': () => {
                this.addActionButton(`btnAddThreat`, _('Add Threat'), () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardWithId', { id: 0 }), { id: 'actPass', color: 'alert' });
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
