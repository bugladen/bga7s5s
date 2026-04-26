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

            'highDramaPhase03cd01': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase03cd01_2': () => {
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});