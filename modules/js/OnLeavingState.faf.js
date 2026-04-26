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

        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});