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
    return declare('seventhseacityoffivesails.onupdateactionbuttons_tac', null, {

    onUpdateActionButtons_tac: function( stateName, args )
    {
        const methods = {            
            'planningPhaseResolveSchemes_02005': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02005_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02005_3': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },

            'planningPhaseResolveSchemes_02005_4': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'planningPhaseResolveSchemes_02005_5': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onCardsSorted());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'planningPhaseResolveSchemes_02014': () => {
                this.addActionButton(`actAddRenown`, _('Add Renown to The City Forum'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02015': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02025': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02025_2': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },

            'planningPhaseResolveSchemes_02025_3': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02035': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02045': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02045_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02052': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02046': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'planningPhaseResolveSchemes_02046_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase02001': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onChooseHandCardConfirmed());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase02001_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02002': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                args.args.players.forEach((player) => {
                    this.addActionButton(`actChoosePlayer-${player.id}`, player.name, () => this.bgaPerformAction('actFromCardWithId', {id: player.id}));
                });
            },

            'highDramaPhase02002_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'highDramaPhase02002_3': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onCardsSorted());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'highDramaPhase02007': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02008': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'highDramaPhase02008_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02010': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02010_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02010_3': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChoose1Wounds`, _('Move 1 Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                if (args.args.wounds > 1) {
                    this.addActionButton(`actChoose2Wounds`, _('Move 2 Wounds'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
                }
                const statusBarTitle = _('Twist of the Arcana') + _(': ${you} must choose how many wounds to move from ${from_name} to ${target_name}: ');
                this.bga.statusBar.setTitle(statusBarTitle, {
                    from_name: _(args.args.fromName),
                    target_name: _(args.args.targetName),
                });
            },

            'highDramaPhase02013': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase02013_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02014': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
                this.addActionButton(`actKeepAll`, _('Keep All'), () => this.bgaPerformAction('actFromCardWithIds', {'ids': JSON.stringify([])}));
            },

            'highDramaPhase02014_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onCardsSorted());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'highDramaPhase02020': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02020_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'highDramaPhase02020_3': () => {
                this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actDecline`, _('Decline and Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase02023': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02023_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase02033': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02034': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02034_2': () => {
                this.addActionButton(`actIssueChallenge`, _('Issue challenge to Torvo'), () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
                this.addActionButton(`actDeclineChallenge`, _('Decline (opponent draws)'), () => this.bgaPerformAction('actFromCardWithId', { id: 2 }));
            },

            'highDramaPhase02025': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02025_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase02036': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02045': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02047': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02036_2': () => {
                this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onChooseHandCardConfirmed());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase02051': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelResolveManeuver_02038': () => {
                this.addActionButton('actSufferWound', _('Suffer a Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton('actLetDraw', _('Let Opponent Draw'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'duelResolveManeuver_02057': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_02054': () => {
                this.addActionButton('actSufferWound', _('Suffer a Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton('actDecline', _('Decline'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'duelChooseTechnique_02055': () => {
                args.args.techniques.forEach((technique) => {
                    this.addActionButton(`actChooseTechnique-${technique.id}`, technique.name, () => this.bgaPerformAction('actFromCardWithIds', {ids: JSON.stringify([technique.id])}));
                });
            },

            'duelChooseTechnique_02006': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_02011': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'duelChooseTechnique_02026a': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'duelChooseTechnique_02026b': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'duelChooseTechnique_02043b': () => {
                args.args.cards.forEach((card) => {
                    this.addActionButton(`actChooseCard-${card.id}`, card.name, () => this.bgaPerformAction('actFromCardWithId', {id: card.id}));
                });
            },

            'duskPhaseBegin02024': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'duskPhaseBegin02053': () => {
                this.addActionButton(`actChooseCardSelected`, _('Send to Locker'), () => this.onChooseListCardConfirmed());
                this.statusBar.addActionButton(_('Pass'), () => this.onPass(), { id: 'actPass', color: 'alert' });
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }

});
});