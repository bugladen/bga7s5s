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
    return declare('seventhseacityoffivesails.onupdateactionbuttons_cad', null, {

    onUpdateActionButtons_cad: function( stateName, args )
    {
        const methods = {

            // WHY: Prototype CAD — plain button labels, no _() (hide from translators).
            'highDramaPhase05DabneyUS01': () => {
                this.addActionButton(`actChooseCardSelected`, 'Confirm', () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase05Overcoat': () => {
                // WHY: Mandatory claim-or-move-renown (Soline "or" — no Pass).
                // Args nest: OnUpdateActionButtons uses args.args.* (one less than OnEnteringState).
                if (args.args.canClaim) {
                    this.addActionButton(`actClaim`, 'Claim Location', () => this.bgaPerformAction('actFromCardWithId', {id: 0}));
                }
                if (args.args.locationIds && args.args.locationIds.length > 0) {
                    this.addActionButton(`actCityLocationsSelected`, 'Confirm Move From Location', () => this.onCityLocationsSelected());
                    dojo.addClass('actCityLocationsSelected', 'disabled');
                }
            },

            'duelChooseTechnique_05DabneyUS01': () => {
                // WHY: EventHub zeroes Technique Parry/Riposte when combat card(s) have
                // dashed that axis — hide the option so it is not offered as a no-op.
                if (args.parryAvailable)
                {
                    this.addActionButton(`btnParry`, '+1 Parry', () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
                }
                if (args.riposteAvailable)
                {
                    this.addActionButton(`btnRiposte`, '+1 Riposte', () => this.bgaPerformAction('actFromCardWithId', { id: 0 }));
                }
                this.addActionButton(`btnLethal`, 'Lethal', () => this.bgaPerformAction('actFromCardWithId', { id: 2 }));
            },

            'highDramaChallengeActionResolveTechnique_05DabneyUS01': () => {
                // WHY: Technique is duel-only (Parry replaced Thrust — no challenge-useful
                // choice). Pass out if a stale transition lands here.
                this.statusBar.addActionButton('Pass', () => this.bgaPerformAction('actPass', {}), { id: 'actPass', color: 'alert' });
            },

            'duelChooseTechnique_05Cooper': () => {
                this.addActionButton(`actChooseCardSelected`, 'Confirm', () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'setupTable_05Gary': () => {
                this.addActionButton(`actChooseCardSelected`, 'Confirm Selection', () => this.onChooseListCardConfirmed());
                this.statusBar.addActionButton('Pass', () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'setupTable_05Gary_2': () => {
                this.addActionButton(`actOk`, 'Ok', () => this.onMultipleOk());
            },

            'duelChooseTechnique_05GaryFlipped': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
