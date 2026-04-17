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
return declare('seventhseacityoffivesails.actions', null, {

    onStarterDeckSelected: function(deckId)
    {
        let deckJson = '';
        let deckType = 'starter';
        if (deckId === 'Custom') 
        {
            deckJson = document.getElementById('customJson').value;
            deckType = 'custom';
            deckId = 'Custom';
        }

        let errors = false;
        this.bgaPerformAction("actPickDeck", {
            'deck_type': deckType,
                'deck_id': deckId,
                'deck_json': deckJson
            }).catch(() =>  {
                errors = true;
            }).then(() =>  {
            });
    },    

    onCityLocationsSelected: function() 
    {
        if (this.selectedCityLocations.length < this.numberOfCityLocationsSelectable )
         this.confirmationDialog(_("You did not select as many locations as you are allowed. Are you sure you want to continue?"),
            () => {this.submitLocations();}
        );
        else
            this.submitLocations();
    },

    submitLocations: function()
    {
        //Special logic for specific states
        const methods = {
        }


        if (methods[this.gamedatas.gamestate.name]) {
            methods[this.gamedatas.gamestate.name]();
            return;
        }

        const actionMap = {
            'planningPhaseResolveSchemes_01016': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01071': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01098': 'actCityLocationsForReknownSelected',
            'highDramaMoveActionChooseLocation': 'actHighDramaMoveActionDestinationChosen',
        };

        const clientMessageArray = {
        };

        let action = actionMap[this.gamedatas.gamestate.name];
        if (action === undefined)
            action = 'actFromCardWithLocations';

        const locations = this.selectedCityLocations.map((loc) => $(loc).getAttribute('data-location'));
        
        //If the action ends with _client, we need to call a client side function
        if (action.includes('_client')) {
            this.clientStateArgs.selectedCityLocations = this.selectedCityLocations
            const clientMessage = clientMessageArray[action];
            this.setClientState(action, {
                'descriptionmyturn' : _(clientMessage),
            })
        } else {

            this.bgaPerformAction(action, { 
                'locations': JSON.stringify(locations),
            }).then(() =>  {                
                // What to do after the server call if it succeeded
            });
        }
    },

    onChooseInPlayCardConfirmed: function()
    {
        const actions = {
            'highDramaMoveActionChoosePerformer'                    : 'actHighDramaMoveActionPerformerChosen',
            'highDramaInPlayActionChoosePerformer'                  : 'actHighDramaInPlayActionPerformerChosen',  
            'highDramaInHandActionChoosePerformer'                  : 'actHighDramaInHandActionPerformerChosen',  
            'highDramaRecruitActionChoosePerformer'                 : 'actHighDramaRecruitActionPerformerChosen',
            'highDramaRecruitActionChooseMercenary'                 : 'actHighDramaRecruitActionMercenaryChosen',
            'highDramaEquipActionChoosePerformer'                   : 'actHighDramaEquipActionPerformerChosen',
            'highDramaEquipActionChooseAttachmentFromPlay'          : 'actHighDramaEquipActionAttachmentFromPlaySelected',
            'highDramaClaimActionChoosePerformer'                   : 'actHighDramaClaimActionPerformerChosen',
            'highDramaChallengeActionChoosePerformer'               : 'actHighDramaChallengeActionPerformerChosen',
            'highDramaChallengeActionChooseTarget'                  : 'actHighDramaChallengeActionTargetChosen',
            'highDramaChallengeActionAcceptChallenge'               : 'actHighDramaChallengeActionIntervene', 
            'highDramaPhase01060_2'                                 : 'actFromCardWithIds',
        };

        let action = actions[this.gamedatas.gamestate.name];
        if (action === undefined)
            action = 'actFromCardWithId';

        //If the action ends with _client, we need to call a client side function
        if (action.includes('_client')) {
            this.clientStateArgs.selectedCards = this.selectedCards;
            const clientMessage = clientMessages[action];
            this.setClientState(action, {
                'descriptionmyturn' : _(clientMessage),
            })
        } else {
            this.bgaPerformAction(action, { 
                'id' : this.selectedCards[0],
            });
        }
    },

    onChooseMultipleInPlayCardsConfirmed: function()
    {
        if (this.numberOfCardsSelectable < this.MAX_CARDS_SELECTABLE && this.selectedCards.length < this.numberOfCardsSelectable )
            this.confirmationDialog(_("You did not select as many cards as you are allowed. Are you sure you want to continue?"),
               () => {this.submitInPlayCards();}
           );
           else
               this.submitInPlayCards();
    },

    submitInPlayCards: function()
    {
        const actionMap = {
        };

        let action = actionMap[this.gamedatas.gamestate.name];
        if (action === undefined)
            action = 'actFromCardWithIds';

        this.bgaPerformAction(action, { 
            'ids': JSON.stringify(this.selectedCards),
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });
    },

    onMusterCardSelected: function()
    {
        var items = this.approachDeck.getSelectedItems();
        const card = Object.values(items)[0];

        const actions = {
            'highDramaPhase01072_2'   : 'actFromCardWithId',
        };

        const action = actions[this.gamedatas.gamestate.name];

        let errors = false;
        this.bgaPerformAction(action, { 
            'id' : card.id
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {                
            if (!errors) this.approachDeck.removeFromStockById(card.id);
        });
    },

    onPlanningCardsSelected: function()
    {
        var scheme = 0;
        var character = 0;

        var items = this.approachDeck.getSelectedItems();
        items.forEach((item) => {
            this.approachDeck.removeFromStockById(item.id);
            if (this.cardProperties[item.type].type === 'Scheme') {
                scheme = item.id;
            } else {
                character = item.id;
            }
        });

        this.bgaPerformAction("actDayPlanned", { 
                'scheme' : scheme, 
                'character' : character
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });        
    },

    onChooseHandCardConfirmed: function()
    {
        var items = this.factionHand.getSelection();
        let id = Object.values(items)[0].id;

        const actionArray = {
            'highDramaEquipActionChooseAttachmentFromHand'  : 'actHighDramaEquipActionAttachmentFromHandSelected',
            'highDramaBruteActionChooseBrute'               : 'actHighDramaBruteActionBruteChosen',
        };

        let action = actionArray[this.gamedatas.gamestate.name];
        if (action === undefined)
            action = 'actFromCardWithId';

        let errors = false;
        this.bgaPerformAction(action, { 
            'id' : id, 
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {                
        });        

    },

    onDuelChooseCombatCardConfirmed: function()
    {
        var items = this.factionHand.getSelection();
        const card = Object.values(items)[0];
        this.bgaPerformAction('actDuelActionChooseCombatCard', { 
            'cardId' : card.id
        }).then(() =>  {                
        });                
    },

    onChooseListCardConfirmed: function()
    {
        var items = this.chooseList.getSelectedItems();
        const card = Object.values(items)[0];

        const actions = {
            'setupTable_01006'                      : 'actFromCardWithId',
            'duelChooseGambleCard'                  : 'actGambleCardChosen',
        };

        let action = actions[this.gamedatas.gamestate.name];
        if (action === undefined)
            action = 'actFromCardWithId';

        let errors = false;
        this.bgaPerformAction(action, { 
            'id' : card.id
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {                
            if (!errors) this.chooseList.removeFromStockById(card.id);
        });
    },

    onMultipleChooseListCardsConfirmed: function()
    {
        let cards = [];
        this.chooseList.getSelectedItems().forEach((item) => {
            const div = this.chooseList.getItemDivId(item.id);
            const order = parseInt($(div).getAttribute('order'));
            cards.push({order: order, id: item.id});
        });

        const ids = cards.map((card) => card.id);

        this.bgaPerformAction('actFromCardWithIds', { 
            'ids': JSON.stringify(ids),
        }).catch(() =>  {
        }).then(() =>  {
        });        
    },

    onRecruitCharacterConfirmed: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const payWithCards = selectedCards.map((item) => item.id);

        const actionArray = {
            'highDramaRecruitActionPayForMercenary'         : 'actHighDramaRecruitActionPayForMercenary',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        let errors = false;
        switch (action) {
            case 'actHighDramaRecruitActionPayForMercenary':
                this.bgaPerformAction(action, { 
                    'payWithCards': JSON.stringify(payWithCards),
                }).catch(() =>  {
                    errors = true;
                }).then(() =>  {
                    if (!errors)
                    {
                        selectedCards.forEach((card) => this.factionHand.removeCard(card));
                    }
                });
                break;
        }
    },

    onActionCardFromHandPaymentConfirmed: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const payWithCards = selectedCards.map((item) => item.id);

        let errors = false;
        this.bgaPerformAction('actPayForInHandAction', { 
            'payWithCards': JSON.stringify(payWithCards),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors)
            {
                if (this.clientStateArgs.chosenActionCardId) {
                    const actionCard = this.cardProperties[this.clientStateArgs.chosenActionCardId];
                    if (actionCard) this.factionHand.removeCard(actionCard);
                }

                selectedCards.forEach((card) => this.factionHand.removeCard(card));
            }

        });
    },

    onPaymentConfirmed: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const payWithCards = selectedCards.map((item) => item.id);

        const actionArray = {
            'highDramaEquipActionPayForAttachmentFromHand' : 'actHighDramaEquipAttachment',
            'highDramaEquipActionPayForAttachmentFromPlay' : 'actHighDramaEquipAttachment',
            'highDramaBruteActionPayForBrute'              : 'actPayForBrute',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        let errors = false;
        this.bgaPerformAction(action, { 
            'payWithCards': JSON.stringify(payWithCards),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) 
            {
                if (this.clientStateArgs.chosenCardId) {
                    const chosenCard = this.cardProperties[this.clientStateArgs.chosenCardId];
                    if (chosenCard) this.factionHand.removeCard(chosenCard);
                }

                selectedCards.forEach((card) => this.factionHand.removeCard(card));
            }
        });            
    },

    onPaymentConfirmedFromCard: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const ids = selectedCards.map((item) => item.id);

        const actionArray = {
            'highDramaPhase01180_5'               : 'actFromCardWithIds',
        };

        let action = actionArray[this.gamedatas.gamestate.name];

        if (!action)
            action = 'actFromCardWithIds';

        let errors = false;
        this.bgaPerformAction(action, { 
            'ids': JSON.stringify(ids),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors)
            {
                selectedCards.forEach((card) => this.factionHand.removeCard(card));
            }
        });        
    },

    onCombatCardPaymentConfirmed: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const payWithCards = selectedCards.map((item) => item.id);

        let errors = false;
        this.bgaPerformAction('actDuelPayForManeuverFromCombatCard', { 
            'payWithCards': JSON.stringify(payWithCards),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors)
            {
                selectedCards.forEach((card) => this.factionHand.removeCard(card));
                const combatCard = this.cardProperties[this.clientStateArgs.combatCardId];
                if (combatCard) this.factionHand.removeCard(combatCard);
            }
        });        
    },

    onReactionPaymentConfirmed: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const payWithCards = selectedCards.map((item) => item.id);

        let errors = false;
        this.bgaPerformAction('actPayForReaction', { 
            'payWithCards': JSON.stringify(payWithCards),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors)
            {
                const reactionCard = this.cardProperties[this.clientStateArgs.reactionCardId];
                if (reactionCard) this.factionHand.removeCard(reactionCard);
                selectedCards.forEach((card) => this.factionHand.removeCard(card));
            }
        });        
    },

    onCardsChosenForDiscard: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const ids = selectedCards.map((item) => item.id);

        let errors = false;
        this.bgaPerformAction('actDuskPhaseCardsDiscarded', { 
            'ids': JSON.stringify(ids),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) selectedCards.forEach((card) => this.factionHand.removeCard(card));
        });        
    },

    onCardDiscarded: function()
    {
        let items = this.factionHand.getSelection();
        let card = items[0];
        let errors = false;
    
        this.bgaPerformAction('actFromCardWithId', { 
            'id': card.id,
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) this.factionHand.removeCard(card);
        });        
    },

    onCardsDiscarded: function()
    {
        const selectedCards = this.factionHand.getSelection();
        const ids = selectedCards.map((item) => item.id);
        let errors = false;

        this.bgaPerformAction('actFromCardWithIds', { 
            'ids': JSON.stringify(ids),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) selectedCards.forEach((card) => this.factionHand.removeCard(card));
        });        
    },

    onCardsSorted: function()
    {
        let cards = [];
        this.chooseList.getSelectedItems().forEach((item) => {
            const div = this.chooseList.getItemDivId(item.id);
            const order = parseInt($(div).getAttribute('order'));
            cards.push({order: order, id: item.id});
        });

        //Sort cards by descending order (they will be added to the top of the deck in this order)
        cards.sort((a, b) => b.order - a.order);        
        const ids = cards.map((card) => card.id);

        this.bgaPerformAction('actFromCardWithIds', { 
            'ids': JSON.stringify(ids),
        }).catch(() =>  {
        }).then(() =>  {
        });        
    },

    onConfirmPass: function()
    {
        const overlay = document.createElement('div');
        overlay.className = '_7sfs-confirm-overlay';

        const dialog = document.createElement('div');
        dialog.className = '_7sfs-confirm-dialog';
        dialog.innerHTML =
            '<div class="_7sfs-confirm-text">' + _("Are you sure you want to pass?") + '</div>' +
            '<div class="_7sfs-confirm-buttons">' +
                '<button class="_7sfs-confirm-btn _7sfs-confirm-yes bgabutton bgabutton_blue">' + _("Yes") + '</button>' +
                '<button class="_7sfs-confirm-btn _7sfs-confirm-no bgabutton bgabutton_red">' + _("No") + '</button>' +
            '</div>';

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        const close = () => overlay.remove();

        dialog.querySelector('._7sfs-confirm-yes').addEventListener('click', () => { close(); this.onPass(); });
        dialog.querySelector('._7sfs-confirm-no').addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    },

    onMultipleOk: function()
    {
        this.bgaPerformAction("actMultipleOk", { 
        });        
    },

    onPass: function()
    {
        const actionArray = {
            'setupTable_01006'                          : 'actFromCardPass',
            'highDramaPlayerTurn'                       : 'actHighDramaPass',
            'highDramaPhase01180_3'                     : 'actPassWithPass',
            'planningPhaseResolveSchemes_01016_2'       : 'actPassWithPass',
            'planningPhaseResolveSchemes_01125'         : 'actFromCardPass',
            'planningPhaseResolveSchemes_01125_2'       : 'actFromCardPass',
            'planningPhaseResolveSchemes_01152'         : 'actPassWithPass',
            'planningPhaseResolveSchemes_01152_2'       : 'actPassWithPass',
            'highDramaChallengeActionActivateTechnique' : 'actHighDramaChallengeActionActivateTechnique_Pass',
            'duskPhaseBegin01177'                       : 'actPassWithPass',
            'duskPhaseBegin02053'                       : 'actFromCardPass',
        };

        //If the current game state is in actionArray set the action to the value in the array
        //Otherwise set the action to actPass
        let action = actionArray[this.gamedatas.gamestate.name] || 'actPass';

        this.bgaPerformAction(action, { 
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });
    },

});      
});