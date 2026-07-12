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
    return declare('seventhseacityoffivesails.onupdateactionbuttons_faf', null, {

    onUpdateActionButtons_faf: function( stateName, args )
    {
        const methods = {

            'planningPhaseResolveSchemes_03005': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_03006': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_03017': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_03030': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase03cd01': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03cd01_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase03cd03': () => {
                args.args.players.forEach((player) => {
                    this.addActionButton(`actChoosePlayer-${player.id}`, player.name, () => this.bgaPerformAction('actFromCardWithId', {id: player.id}));
                });
            },

            'highDramaPhase03cd13': () => {
                args.args.players.forEach((player) => {
                    this.addActionButton(`actChoosePlayer-${player.id}`, player.name, () => this.bgaPerformAction('actFromCardWithId', {id: player.id}));
                });
            },

            'highDramaPhase03001': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03001_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03002': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03003': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03003_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03030': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03030_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03009': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase03032': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase03011': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03034': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03034_2': () => {
                this.addActionButton(`actHeal`, _('Heal a Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actDraw`, _('Draw a Card'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase03037': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03038a': () => {
                this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCards', 'disabled');
            },

            'highDramaPhase03038b': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03038b_2': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'highDramaPhase03020': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03026': () => {
                this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCards', 'disabled');
            },

            'highDramaPhase03026_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase03026_3': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03029': () => {
                if (args.args.optionToPerformerAvailable) {
                    this.addActionButton('actMoveToPerformer', _('Move character to performer\'s location'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                }
                if (args.args.optionFromPerformerAvailable) {
                    this.addActionButton('actMoveFromPerformer', _('Move character from performer\'s location'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
                }
            },

            'highDramaPhase03029_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03029_3': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase03cd03_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Muster'), () => this.onMusterCardSelected());
                dojo.addClass('actChooseCardSelected', 'disabled');
                this.addActionButton(`actNone`, _('Decline'), () => this.bgaPerformAction('actFromCardWithId', {id: 0}));
            },

            'highDramaChallengeActionResolveTechnique_03013': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_03013': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_03025b': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'duelChooseTechnique_03039': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'duelEndOfRound_03022': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
            },

            'duelGambleSetup_03cd05': () => {
                this.addActionButton(`btnTop`, _('Reveal from Top'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`btnBottom`, _('Reveal from Bottom'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'duelResolveManeuver_03024': () => {
                this.addActionButton(`btnParry`, _('+2 Parry'), () => this.bgaPerformAction('actFromCardWithId', { id: 1}));
                this.addActionButton(`btnThrust`, _('+2 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 2}));
            },

            'duelResolveManeuver_03035': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelResolveManeuver_03035_2': () => {
                this.addActionButton(`btnRiposte`, _('+1 Riposte'), () => this.bgaPerformAction('actFromCardWithId', { id: 1}));
                this.addActionButton(`btnThrust`, _('+2 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 2}));
            },

            'duelResolveManeuver_03036': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});