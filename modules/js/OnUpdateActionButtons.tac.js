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
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
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
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onMultipleChooseListCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');            
            },

            'highDramaPhase02001': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onChooseHandCardConfirmed());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase02001_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase02002': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
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
            'duelChooseTechnique_02006': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }

});
});