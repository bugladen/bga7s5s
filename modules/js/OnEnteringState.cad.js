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
    return declare('seventhseacityoffivesails.onenteringstate_cad', null, {

    onEnteringState_cad: function( stateName, args )
    {
        const methods = {

            'highDramaPhase05DabneyUS01': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'duelChooseTechnique_05Cooper': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);
                }
            },

            'setupTable_05Gary': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.removeClass('choose_container', 'hidden');
                    dojo.removeClass('chooseList', 'hidden');
                    $('choose_container_name').innerHTML = 'Attachments in Your Faction Deck';

                    args.args._private.args.attachments.forEach((card) => {
                        this.addCardToDeck(this.chooseList, card);
                    });
                    this.chooseList.setSelectionMode(1);
                }
            },
            'setupTable_05Gary_2': () => {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = 'Chosen Attachment';

                setTimeout(() => {
                    this.addCardToDeck(this.chooseList, args.args.args.card);
                    this.chooseList.setSelectionMode(0);
                }, 500);
            },

        };

        if (methods[stateName])
            methods[stateName]();
    },

})
});
