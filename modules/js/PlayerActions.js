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
        this.bgaPerformAction("actPickDeck", { 
            'deck_type': 'starter',
            'deck_id': deckId,
            'deck_json': ''
        }).then(() =>  {                
            // What to do after the server call if it succeeded
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
            planningPhaseResolveSchemes_01126_2_client: () => {
                const leshiyeLocation = $(this.clientStateArgs.selectedCityLocations[0]).getAttribute('data-location');
                const locations = this.selectedCityLocations.map((loc) => $(loc).getAttribute('data-location'));

                this.bgaPerformAction('actPlanningPhase_01126_2', { 
                    'leshiyeLocation': leshiyeLocation,
                    'locations': JSON.stringify(locations),
                });
            },

            planningPhaseResolveSchemes_01145_2_client: () => {
                const fromLocation = $(this.clientStateArgs.selectedCityLocations[0]).getAttribute('data-location');
                const toLocation = $(this.selectedCityLocations[0]).getAttribute('data-location');

                this.bgaPerformAction('actPlanningPhase_01145', { 
                    'fromLocation': fromLocation,
                    'toLocation': toLocation,
                });
            }
        }

        if (methods[this.gamedatas.gamestate.name]) {
            methods[this.gamedatas.gamestate.name]();
            return;
        }

        const actionMap = {
            'planningPhaseResolveSchemes_01016': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01071': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01098': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01125': 'actPlanningPhase_01125',
            'planningPhaseResolveSchemes_01125_2': 'actPlanningPhase_01125_2',
            'planningPhaseResolveSchemes_01125_3': 'actPlanningPhase_01125_3',
            'planningPhaseResolveSchemes_01126': 'planningPhaseResolveSchemes_01126_2_client',
            'planningPhaseResolveSchemes_01144': 'actPlanningPhase_01144',
            'planningPhaseResolveSchemes_01144_2': 'actPlanningPhase_01144_2',
            'planningPhaseResolveSchemes_01145': 'planningPhaseResolveSchemes_01145_2_client',
            'planningPhaseResolveSchemes_01145_2_client': 'actPlanningPhase_01145',
            'highDramaMoveActionChooseLocation': 'actHighDramaMoveActionDestinationChosen',
        };

        const clientMessageArray = {
            'planningPhaseResolveSchemes_01126_2_client': _("Leshiye of the Wood: ${you} must choose two other locations to place Reknown onto: "),
            'planningPhaseResolveSchemes_01145_2_client': _("Inspire Generosity: ${you} must choose a location to move the Reknown to: "),
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
            'planningPhaseResolveSchemes_01125_4'                   : 'actPlanningPhase_01125_4',
            'highDramaBeginning_01144'                              : 'highDramaBeginning_01144_client',
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

        const clientMessages = {
            'highDramaBeginning_01144_client'                       : _("${you} must choose cards from your Faction Hand to pay for selected Mercenary: "),
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
            'highDramaPhase01072_3'   : 'actFromCardWithId',
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
        var items = this.factionHand.getSelectedItems();
        let id = Object.values(items)[0].id;

        const actionArray = {
            'highDramaEquipActionChooseAttachmentFromHand'  : 'actHighDramaEquipActionAttachmentFromHandSelected',
            'highDramaBruteActionChooseBrute'               : 'actHighDramaBruteActionBruteChosen',
            'highDramaPhase01148_3'                         : 'actFromCardWithId',
        };

        const action = actionArray[this.gamedatas.gamestate.name];

        let errors = false;
        this.bgaPerformAction(action, { 
            'id' : id, 
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {                
            //if (!errors) this.factionHand.removeFromStockById(id);
        });        

    },

    onDuelChooseCombatCardConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
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

    onRecruitCharacterConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        const actionArray = {
            'highDramaBeginning_01144_client'               : 'actHighDramaBeginning_01144',
            'highDramaRecruitActionPayForMercenary'         : 'actHighDramaRecruitActionPayForMercenary',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        switch (action) {
            case 'actHighDramaRecruitActionPayForMercenary':
                this.bgaPerformAction(action, { 
                    'payWithCards': JSON.stringify(items),
                });
                break;
                case 'actHighDramaBeginning_01144':
                    this.bgaPerformAction(action, { 
                    'recruitId': this.clientStateArgs.selectedCards[0],
                    'payWithCards': JSON.stringify(items),
                }).catch(() =>  {
                    if (this.gamedatas.gamestate.name == 'highDramaBeginning_01144_client')
                        this.setClientState('highDramaBeginning_01144',
                            {
                                'descriptionmyturn' : _("${you} may choose a Mercenary from a City Location to recruit to your home: "),
                            })
                });        
                break;
        }
    },

    onActionCardFromHandPaymentConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        this.bgaPerformAction('actPayForInHandAction', { 
            'payWithCards': JSON.stringify(items),
        }).catch(() =>  {
        });        
    },

    onPaymentConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        const actionArray = {
            'highDramaEquipActionPayForAttachmentFromHand' : 'actHighDramaEquipAttachment',
            'highDramaEquipActionPayForAttachmentFromPlay' : 'actHighDramaEquipAttachment',
            'highDramaBruteActionPayForBrute'              : 'actPayForBrute',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        let errors = false;
        this.bgaPerformAction(action, { 
            'payWithCards': JSON.stringify(items),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors && this.clientStateArgs.chosenCardId) 
                this.factionHand.removeFromStockById(this.clientStateArgs.chosenCardId);
        });            
    },

    onPaymentConfirmedFromCard: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        const actionArray = {
            'highDramaPhase01167_3'               : 'actFromCardWithIds',
            'highDramaPhase01180_5'               : 'actFromCardWithIds',
        };

        const action = actionArray[this.gamedatas.gamestate.name];

        this.bgaPerformAction(action, { 
            'ids': JSON.stringify(items),
        }).catch(() =>  {
        });        
    },

    onCombatCardPaymentConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        this.bgaPerformAction('actDuelPayForManeuverFromCombatCard', { 
            'payWithCards': JSON.stringify(items),
        }).catch(() =>  {
        });        
    },

    onReactionPaymentConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        this.bgaPerformAction('actPayForReaction', { 
            'payWithCards': JSON.stringify(items),
        }).catch(() =>  {
        });        
    },

    onCardsChosenForDiscard: function()
    {
        let items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);
    
        let errors = false;
        this.bgaPerformAction('actDuskPhaseCardsDiscarded', { 
            'ids': JSON.stringify(items),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) items.forEach((item) => this.factionHand.removeFromStockById(item));
        });        
    },

    onCardDiscarded: function()
    {
        let items = this.factionHand.getSelectedItems();
        let item = items[0].id;
        let errors = false;
    
        this.bgaPerformAction('actFromCardWithId', { 
            'id': item,
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) this.factionHand.removeFromStockById(item);
        });        
    },

    onCardsDiscarded_01185: function()
    {
        let items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);
        let errors = false;
    
        this.bgaPerformAction('actFromCardWithIds', { 
            'ids': JSON.stringify(items),
        }).catch(() =>  {
            errors = true;
        }).then(() =>  {
            if (!errors) items.forEach((item) => this.factionHand.removeFromStockById(item));
        });        
    },

    onCardsChosen_01177_2: function()
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
        this.confirmationDialog(_("Are you sure you want to pass?"),
        () => {this.onPass();}
        );
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
            'planningPhaseResolveSchemes_01125'         : 'actPlanningPhase_01125_Pass',
            'planningPhaseResolveSchemes_01125_2'       : 'actPlanningPhase_01125_2_Pass',
            'planningPhaseResolveSchemes_01125_4'       : 'actPlanningPhase_01125_4_Pass',
            'planningPhaseResolveSchemes_01145'         : 'actPlanningPhase_01145_Pass',            
            'planningPhaseResolveSchemes_01145_2_client': 'actPlanningPhase_01145_Pass',            
            'planningPhaseResolveSchemes_01152'         : 'actPassWithPass',
            'planningPhaseResolveSchemes_01152_2'       : 'actPassWithPass',
            'highDramaChallengeActionActivateTechnique' : 'actHighDramaChallengeActionActivateTechnique_Pass',
            'duskPhaseBegin01177'                       : 'actPassWithPass',
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