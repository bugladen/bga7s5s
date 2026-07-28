/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * modules\js\EventHandlers.js
 *
 * Event handlers for SeventhSeaCityOfFiveSails
 * 
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.eventhandlers', null, {

    onApproachCardClicked: function( control_name, item_id )
    {
        const methods = {
            'planningPhase': () => {
                var items = this.approachDeck.getSelectedItems();
                // Grab the type of card from the properties cache and make sure we are only selecting 1 of each type
                const selectedType = this.cardProperties[item_id].type;        
                items.forEach((item) => {
                    const type = this.cardProperties[item.id].type;            
                    if (selectedType === type && item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                });

                //Get a count of available schemes and characters
                let schemeCount = 0;
                let characterCount = 0;
                items = this.approachDeck.getAllItems();
                items.forEach((item) => {
                    card = this.cardProperties[item.id];
                    if (card.type === 'Scheme') {
                        schemeCount++;
                    } else if (card.type === 'Character') {
                        characterCount++;
                    }
                });

                let schemeSelected = false;
                let characterSelected = false;
                items = this.approachDeck.getSelectedItems();
                items.forEach((item) => {
                    const type = this.cardProperties[item.id].type;            
                    if (selectedType === type && item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                    
                    if (type === 'Scheme') {
                        schemeSelected = true;
                    } else if (type === 'Character') {
                        characterSelected = true;
                    }
                });
        
                // Enable the confirm button if we have 2 cards selected or there are no schemes or characters left
                if (schemeSelected && characterSelected || 
                    (schemeCount === 0 && characterSelected) || 
                    (schemeSelected && characterCount === 0)) {
                    dojo.removeClass('actEndPlanningPhase', 'disabled');
                } else {
                    dojo.addClass('actEndPlanningPhase', 'disabled');
                }
            },

            'highDramaPhase01072_2': () => {
                var items = this.approachDeck.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                });
                if (item_id)
                {
                    const card = this.cardProperties[item_id];
                    if (card.type === 'Scheme') {
                        this.approachDeck.unselectItem(item_id);
                    }
                }

                if (this.approachDeck.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase03cd03_2': () => {
                var items = this.approachDeck.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                });
                if (item_id)
                {
                    const div = this.approachDeck.getItemDivId(item_id);
                    if (dojo.hasClass(div, '_7sfs-unselectable')) {
                        this.approachDeck.unselectItem(item_id);
                    }
                }

                if (this.approachDeck.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            }
        };

        if (methods[this.gamedatas.gamestate.name])
            methods[this.gamedatas.gamestate.name]();
    },

    onChooseCardClicked: function(control_name, item_id) 
    {
        const methods = {
            'planningPhaseResolveSchemes_02005_4': () => {
                if (this.chooseList.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'planningPhaseResolveSchemes_02005_5': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'highDramaPhase01038_3': () => {
                if (item_id === undefined) return;
                var items = this.chooseList.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.chooseList.unselectItem(item.id);
                    }
                });
                const card = this.cardProperties[item_id];
                if (card.type !== 'Attachment') {
                    this.chooseList.unselectItem(item_id);
                }

                if (this.chooseList.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01111': () => {
                if (item_id === undefined) return;

                if (this.chooseList.getSelectedItems().length == 3) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01134_2': () => {
                const performerId = this.clientStateArgs.performerId;                
                const performer = this.cardProperties[performerId];
                const maxSelected = performer.modifiedInfluence;

                if (this.chooseList.getSelectedItems().length > maxSelected) {
                    this.chooseList.unselectItem(item_id);
                }
                
                if (this.chooseList.getSelectedItems().length <= maxSelected) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01134_3': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'highDramaPhase01180_3': () => {
                if (item_id === undefined) return;
                var items = this.chooseList.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.chooseList.unselectItem(item.id);
                    }
                });
                const card = this.cardProperties[item_id];
                if ( ! card.traits.includes('Artifact')) {
                    this.chooseList.unselectItem(item_id);
                }

                if (this.chooseList.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01192_3': () => {
                if (item_id === undefined) return;
                var items = this.chooseList.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.chooseList.unselectItem(item.id);
                    }
                });
                const card = this.cardProperties[item_id];
                if (card.type !== 'Risk') {
                    this.chooseList.unselectItem(item_id);
                }

                if (this.chooseList.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase02002_2': () => {
                if (this.chooseList.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'highDramaPhase02002_3': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'highDramaPhase02014': () => {
                if (this.chooseList.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'highDramaPhase02014_2': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'duskPhaseBegin01177_2': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'duskPhaseBegin03052_2': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'duelResolveManeuver_03059_3': () => {
                if (this.chooseList.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'duelResolveManeuver_03059_4': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase04cd15': () => {
                if (this.chooseList.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase04cd15_2': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            // WHY: Multi-select sink — default else branch only enables Confirm when exactly 1 selected.
            'duelChooseTechnique_04001': () => {
                if (this.chooseList.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            // WHY: Reorder numbers come from addSortTagToCard — must be wired like 04cd15_2 / 03052_2.
            'duelChooseTechnique_04001_2': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            }
        };

        if (methods[this.gamedatas.gamestate.name])
            methods[this.gamedatas.gamestate.name]();
        else
        {
            if (this.chooseList.getSelectedItems().length === 1) {
                dojo.removeClass('actChooseCardSelected', 'disabled');
            } else {
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        }
    },

    onFactionCardClicked: function( control_name, item_id )
    {
        // Prevent re-entry during selection changes
        if (this._processingFactionCardClick) return;
        this._processingFactionCardClick = true;
        
        try {
            // Global check: prevent selecting cards with _7sfs-unselectable class
            if (item_id !== undefined) {
                const card = this.factionHand.getCards().find(c => c.id === item_id);
                if (card) {
                    const cardElement = this.factionHand.getCardElement(card);
                    if (cardElement && dojo.hasClass(cardElement, '_7sfs-unselectable')) {
                        this.factionHand.unselectCard(card);
                        return;
                    }
                }
            }

            const methods = {

            'highDramaBeginning_01144_2': () => {
                var items = this.factionHand.getSelection();
                let wealth = 0;
                items.forEach((item) => {
                    // With bga-cards, getSelection() returns card objects directly
                    wealth += item.traits.includes('Wealth') ? 2 : 1;
                });

                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },

            'highDramaPhase01180_5': () => {
                var items = this.factionHand.getSelection();
                let wealth = 0;
                items.forEach((item) => {
                    // With bga-cards, getSelection() returns card objects directly
                    wealth += item.traits.includes('Wealth') ? 2 : 1;
                });

                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },

            'highDramaRecruitActionPayForMercenary': () => {
                var items = this.factionHand.getSelection();
                let wealth = 0;
                items.forEach((item) => {
                    // With bga-cards, getSelection() returns card objects directly
                    wealth += item.traits.includes('Wealth') ? 2 : 1;
                });
                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },

            'highDramaEquipActionChooseAttachmentFromHand': () => {
                var items = this.factionHand.getSelection();
                const cardProps = this.cardProperties[item_id];
                const clickedCard = this.factionHand.getCards().find(c => c.id === item_id);
                
                // bga-cards already toggled the selection. Check if clicked card is now selected.
                const isNowSelected = items.some(item => item.id === item_id);
                
                // Unselect all OTHER cards (single selection mode)
                items.forEach((item) => {
                    if (item.id !== item_id) {
                        this.factionHand.unselectCard(item);
                    }
                });
                
                // If the card is now selected but it's not an Attachment, unselect it
                if (isNowSelected && clickedCard) {
                    if (cardProps?.type !== 'Attachment') {
                        this.factionHand.unselectCard(clickedCard);
                    }
                }

                // Enable the confirm button if we have a card selected
                items = this.factionHand.getSelection();
                if (items.length === 1) {
                    dojo.removeClass('actFactionCardsSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardsSelected', 'disabled');
                }
            },

            'highDramaEquipActionPayForAttachmentFromHand': () => {
                this.payForCard(item_id);
            },

            'highDramaEquipActionPayForAttachmentFromPlay': () => {
                var items = this.factionHand.getSelection();
                let wealth = 0;
                items.forEach((item) => {
                    // With bga-cards, getSelection() returns card objects directly
                    wealth += item.traits.includes('Wealth') ? 2 : 1;
                });
                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },
            
            'highDramaInHandActionPay': () => {
                this.payForCard(item_id);
            },

            'highDramaBruteActionChooseBrute': () => {
                var items = this.factionHand.getSelection();
                const cardProps = this.cardProperties[item_id];
                const clickedCard = this.factionHand.getCards().find(c => c.id === item_id);
                
                // bga-cards already toggled the selection. Check if clicked card is now selected.
                const isNowSelected = items.some(item => item.id === item_id);
                
                // Unselect all OTHER cards (single selection mode)
                items.forEach((item) => {
                    if (item.id !== item_id) {
                        this.factionHand.unselectCard(item);
                    }
                });
                
                // If the card is now selected but it's not a Brute Character, unselect it
                if (isNowSelected && clickedCard) {
                    if (cardProps?.type !== 'Character' || !cardProps.traits.includes('Brute')) {
                        this.factionHand.unselectCard(clickedCard);
                    }
                }

                // Enable the confirm button if we have a card selected
                items = this.factionHand.getSelection();
                if (items.length === 1) {
                    dojo.removeClass('actFactionCardsSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardsSelected', 'disabled');
                }
            },

            'highDramaBruteActionPayForBrute': () => {
                this.payForCard(item_id);
            },


            'highDramaPhase01064': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01069': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase03026': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase03038a': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase03042': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase04cd09_2': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase04cd15_3': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'planningPhaseEnd_03041': () => {
                const needed = this.clientStateArgs.cardsToDiscard || 0;
                if (this.factionHand.getSelection().length === needed) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },
            
            'highDramaPhase01091_2': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actFactionCardSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardSelected', 'disabled');
                }
            },

            'highDramaPhase01095': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01102': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01113_3': () => {
                this.payForCard(item_id);
            },

            'highDramaPhase01148_3': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actFactionCardSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardSelected', 'disabled');
                }
            },

            'highDramaPhase01156': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase01158': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01175': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01185': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase02001': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase02013': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase02036_2': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelChooseAction': () => {
                if (!$('btnCombatCard'))
                    return;
                
                const items = this.factionHand.getSelection();
                if (items.length === 1) {
                    dojo.removeClass('btnCombatCard', 'disabled');
                } else {
                    dojo.addClass('btnCombatCard', 'disabled');
                }
            },

            'duelPayForManeuverFromCombatCard': () => {
                this.payForCard(item_id);
            },

            'duelResolveManeuver_01108': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelResolveManeuver_01113_2': () => {
                this.payForCard(item_id);
            },

            'duelResolveManeuver_01115': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelResolveManeuver_03036': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelNewRound_01090': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelChooseTechnique_01093': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelChooseTechnique_03039': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelChooseTechnique_03043_3': () => {
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duskPhaseDiscard': () => {
                const player = this.gamedatas.players[this.player_id];
                const leader = player.leader;
                const panache = leader.panache;
                const count = this.factionHand.getCards().length;
                const expectedDiscardCount = count - panache;
                const statusBarTitle = _('${you} have discarded ${discarded}/${count} card(s) down to your unmodified Leader Panache value of ${panache}:');
                this.bga.statusBar.setTitle(statusBarTitle, {
                    discarded: this.factionHand.getSelection().length,
                    count: expectedDiscardCount,
                    panache: panache,
                });
        
                if (this.factionHand.getSelection().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'playerPayForReaction': () => {
                this.payForCard(item_id);
            },


            };

            if (methods[this.gamedatas.gamestate.name])
                methods[this.gamedatas.gamestate.name]();

        } finally {
            this._processingFactionCardClick = false;
        }
    },

    onCityLocationClicked: function( event )
    {
        const location = event.target.id;
        //Check to see if we are selecting or deselecting
        if (dojo.hasClass(location, '_7sfs-selected')) 
        {
            dojo.removeClass(location, '_7sfs-selected');
            this.selectedCityLocations = this.selectedCityLocations.filter((loc) => loc !== location);
        } 
        else if (this.selectedCityLocations.length == this.numberOfCityLocationsSelectable == 1)
        {
            this.selectedCityLocations.forEach((loc) => {
                dojo.removeClass(loc, '_7sfs-selected');
            });
            this.selectedCityLocations = [];
            dojo.addClass(location, '_7sfs-selected');
            this.selectedCityLocations.push(location);
        }
        else if (this.selectedCityLocations.length < this.numberOfCityLocationsSelectable) {
            dojo.addClass(location, '_7sfs-selected');
            this.selectedCityLocations.push(location);
        }

        //Enable the confirm button if we have the right number of locations selected
        if (this.selectedCityLocations.length == this.numberOfCityLocationsSelectable) {
            dojo.removeClass('actCityLocationsSelected', 'disabled');
        } else {
            dojo.addClass('actCityLocationsSelected', 'disabled');
        }
    },

    onCardInPlayClicked: function( event )
    {
        let id = event.target.id;

        //If id does not contain '_image' then ignore, we clicked on the some element inside the image that is not the image
        if (!id.includes('_image')) { return }

        //Remove '_image' from the id to get the card divId
        const divId = id.substring(0, id.length - 6);

        const card = this.getCardPropertiesByDivId(divId);
        let image = $(id);
        if (dojo.hasClass(image, '_7sfs-selected')) 
        {
            dojo.removeClass(image, '_7sfs-selected');
            this.selectedCards = this.selectedCards.filter((char) => char !== card.id);
        } 
        else if (this.selectedCards.length == this.numberOfCardsSelectable == 1)
        {
            this.selectedCards.forEach((unsetId) => {
                unsetCard = this.cardProperties[unsetId];
                unsetImageElement = dojo.query('._7sfs-card', unsetCard.divId)[0];
                dojo.removeClass(unsetImageElement, '_7sfs-selected');
            });
            this.selectedCards = [];

            dojo.addClass(image, '_7sfs-selected');
            this.selectedCards.push(card.id);
        }
        else if (this.selectedCards.length < this.numberOfCardsSelectable) 
        {
            dojo.addClass(image, '_7sfs-selected');
            this.selectedCards.push(card.id);
        }

        //Enable the confirm button if we have at least once card selected
        if (this.selectedCards.length > 0) {
            dojo.removeClass('actChooseCardSelected', 'disabled');
        } else {
            dojo.addClass('actChooseCardSelected', 'disabled');
        }
    },

    onCityDiscardClicked: function( event )
    {
        const overlay = document.createElement('div');
        overlay.className = '_7sfs-confirm-overlay';

        const dialog = document.createElement('div');
        dialog.className = '_7sfs-confirm-dialog _7sfs-discard-dialog';

        let cardsHtml = '';
        this.gamedatas.cityDiscard.forEach(card => {
            cardsHtml += this.format_block('jstpl_discard_card', {
                image : this.getCardImageUrlRoot(card.image) + card.image,
            });
        });

        dialog.innerHTML =
            '<div class="_7sfs-confirm-text">' + _("City Discard Pile") + '</div>' +
            '<div class="_7sfs-discard-cards">' + cardsHtml + '</div>' +
            '<div class="_7sfs-confirm-buttons">' +
                '<button class="_7sfs-confirm-btn _7sfs-confirm-close">' + _("Close") + '</button>' +
            '</div>';

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        const close = () => overlay.remove();
        dialog.querySelector('._7sfs-confirm-close').addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    },

    onCityLockerClicked: function( event )
    {
        const overlay = document.createElement('div');
        overlay.className = '_7sfs-confirm-overlay';

        const dialog = document.createElement('div');
        dialog.className = '_7sfs-confirm-dialog _7sfs-discard-dialog';

        let cardsHtml = '';
        this.gamedatas.cityLocker.forEach(card => {
            cardsHtml += this.format_block('jstpl_discard_card', {
                image : this.getCardImageUrlRoot(card.image) + card.image,
            });
        });

        dialog.innerHTML =
            '<div class="_7sfs-confirm-text">' + _("City Locker Pile") + '</div>' +
            '<div class="_7sfs-discard-cards">' + cardsHtml + '</div>' +
            '<div class="_7sfs-confirm-buttons">' +
                '<button class="_7sfs-confirm-btn _7sfs-confirm-close">' + _("Close") + '</button>' +
            '</div>';

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        const close = () => overlay.remove();
        dialog.querySelector('._7sfs-confirm-close').addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    },

    onPlayerDiscardClicked: function (event)
    {
        let playerId = $(event.target.id).getAttribute('data-player-id');
        let playerName = this.getFormattedPlayerName(playerId);

        const overlay = document.createElement('div');
        overlay.className = '_7sfs-confirm-overlay';

        const dialog = document.createElement('div');
        dialog.className = '_7sfs-confirm-dialog _7sfs-discard-dialog';

        let cardsHtml = '';
        this.gamedatas.players[playerId].discard.forEach(card => {
            cardsHtml += this.format_block('jstpl_discard_card', {
                image : this.getCardImageUrlRoot(card.image) + card.image,
            });
        });

        var translated = dojo.string.substitute(
            _("${playerName} Discard Pile"),
            { playerName: playerName }
        );

        dialog.innerHTML =
            '<div class="_7sfs-confirm-text">' + translated + '</div>' +
            '<div class="_7sfs-discard-cards">' + cardsHtml + '</div>' +
            '<div class="_7sfs-confirm-buttons">' +
                '<button class="_7sfs-confirm-btn _7sfs-confirm-close">' + _("Close") + '</button>' +
            '</div>';

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        const close = () => overlay.remove();
        dialog.querySelector('._7sfs-confirm-close').addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    },

    onPlayerLockerClicked: function (event)
    {
        let playerId = $(event.target.id).getAttribute('data-player-id');
        let playerName = this.getFormattedPlayerName(playerId);

        const overlay = document.createElement('div');
        overlay.className = '_7sfs-confirm-overlay';

        const dialog = document.createElement('div');
        dialog.className = '_7sfs-confirm-dialog _7sfs-discard-dialog';

        let cardsHtml = '';
        this.gamedatas.players[playerId].locker.forEach(card => {
            cardsHtml += this.format_block('jstpl_discard_card', {
                image : this.getCardImageUrlRoot(card.image) + card.image,
            });
        });

        var translated = dojo.string.substitute(
            _("${playerName} Locker"),
            { playerName: playerName }
        );

        dialog.innerHTML =
            '<div class="_7sfs-confirm-text">' + translated + '</div>' +
            '<div class="_7sfs-discard-cards">' + cardsHtml + '</div>' +
            '<div class="_7sfs-confirm-buttons">' +
                '<button class="_7sfs-confirm-btn _7sfs-confirm-close">' + _("Close") + '</button>' +
            '</div>';

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        const close = () => overlay.remove();
        dialog.querySelector('._7sfs-confirm-close').addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    },

    payForCard: function(item_id)
    {
        var items = this.factionHand.getSelection();
        let wealth = 0;
        items.forEach((item) => {
            // With bga-cards, getSelection() returns card objects directly
            wealth += item.traits.includes('Wealth') ? 2 : 1;
        });
        var translated = dojo.string.substitute(
            _("(${wealth} Wealth worth of cards selected)"),
            {
                wealth: wealth
            }
        );
        $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
    },

    addSortTagToCard: function(item_id)
    {
        // Declare static var named order
        if (typeof this.addSortTagToCard.order === 'undefined') {
            this.addSortTagToCard.order = 0;
        }

        const length = this.chooseList.getAllItems().length;
        if (this.chooseList.isSelected(item_id) && this.addSortTagToCard.order < length) 
        {                    
            const div = this.chooseList.getItemDivId(item_id);

            //Set the data element to the order of the card
            $(div).setAttribute('order', ++this.addSortTagToCard.order);

            // Create a small red square html element to show that the card is selected
            dojo.place(this.format_block('jstpl_number_order_chip', {
                id: this.addSortTagToCard.order,
            }), div, 'last');
        } 
        else 
        {
            this.addSortTagToCard.order = 0;
            this.chooseList.unselectAll();

            // Remove all the red square html elements
            dojo.query('._7sfs-number-order-chip').forEach((element) => {
                //Delete the data element named order on the parent div
                element.parentNode.removeAttribute('order');                        
                
                dojo.destroy(element);
            });
        }
    },

})
});