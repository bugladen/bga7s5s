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
    return declare('seventhseacityoffivesails.onleavingstate_bas', null, {

    onLeavingState_bas: function( stateName )
    {
        const methods = {

            'highDramaPhase04cd01': () => {
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

            'highDramaPhase04cd04': () => {
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

            'highDramaPhase04cd01b_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
