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
    return declare('seventhseacityoffivesails.onenteringstate_tac', null, {

    onEnteringState_tac: function( stateName, args )
    {
        const methods = {
            'highDramaPhase02001': () => {
                if (this.isCurrentPlayerActive())
                {
                    // Filter cards to only those with the Sorcery trait
                    const sorceryCards = this.factionHand.getCards().filter((card) => card.traits.includes('Sorcery'));
                    
                    // Set selection mode and restrict selectable cards to Sorcery cards only
                    this.factionHand.setSelectionMode('single');
                    this.factionHand.setSelectableCards(sorceryCards);
                }
            },

            'highDramaPhase02001_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.numberOfCardsSelectable = 1;
                    this.highlightCharacterChosen(args.args.args.performerId);
                    this.clientStateArgs.performerId = args.args.args.performerId;

                    this.clientStateArgs.ids = args.args.args.ids;
                    this.highlightCardsAsSelectable(args.args.args.ids);

                    // Set the discarded card as selected in factionHand
                    const discardedCard = this.factionHand.getCards().find((card) => card.id === args.args.args.discardedCardId);
                    if (discardedCard) {
                        const cardElement = this.factionHand.getCardElement(discardedCard);
                        if (cardElement) dojo.addClass(cardElement, '_7sfs-chosen');
                        this.clientStateArgs.discardedCardId = args.args.args.discardedCardId;
                    }
                }
            },

        }

        if ( methods[stateName] )
            methods[stateName]();
    }
});
});