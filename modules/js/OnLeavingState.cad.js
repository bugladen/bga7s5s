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
    return declare('seventhseacityoffivesails.onleavingstate_cad', null, {

    onLeavingState_cad: function( stateName )
    {
        const methods = {

            'highDramaPhase05DabneyUS01': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'duelChooseTechnique_05Cooper': () => {
                if (this.isCurrentPlayerActive())
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'setupTable_05Gary': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },
            'setupTable_05Gary_2': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
