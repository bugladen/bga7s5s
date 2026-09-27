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

            'highDramaPhase05DabneyUS01': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_05DabneyUS01': () => {
                // WHY: EventHub zeroes Technique Riposte when combat card(s) have dashed
                // Riposte — hide the option so it is not offered as a no-op.
                if (args.riposteAvailable)
                {
                    this.addActionButton(`btnRiposte`, _('+1 Riposte'), () => this.bgaPerformAction('actFromCardWithId', { id: 0 }));
                }
                this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
                this.addActionButton(`btnLethal`, _('Lethal'), () => this.bgaPerformAction('actFromCardWithId', { id: 2 }));
            },

            'highDramaChallengeActionResolveTechnique_05DabneyUS01': () => {
                // WHY: Challenge — Riposte needs Calculate; Lethal is pointless (threat already
                // capped at challenge stat, so Restricted Hostilities never cuts it). Thrust only.
                this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 1 }));
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
