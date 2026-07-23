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

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
