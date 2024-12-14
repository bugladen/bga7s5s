define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.actions', null, {

    onApproachCardSelected: function( control_name, item_id )
    {
        var items = this.approachDeck.getSelectedItems();
        // Grab the type of card from the properties cache and make sure we are only selecting 1 of each type
        const types = {};
        items.forEach((item) => {
            const type = this.cardProperties[item.type].type;
            if (types[type]) {
                this.approachDeck.unselectItem(item_id);
            } else {              
                types[type] = true;
            }
        });

        var items = this.approachDeck.getSelectedItems();

        // Enable the confirm button if we have 2 cards selected
        if (items.length === 2) {
            dojo.removeClass('actEndPlanningPhase', 'disabled');
        } else {
            dojo.addClass('actEndPlanningPhase', 'disabled');
        }
    },

    onFactionCardSelected: function( control_name, item_id )
    {
        var items = this.factionHand.getSelectedItems();

        switch (this.gamedatas.gamestate.name) {
            case 'client_highDramaBeginning_01144_1':
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = items.length > 0 ? `(${wealth} Wealth worth of cards selected)` : '';

                break;
            }
    },

    onChooseCardSelected: function()
    {
        if (this.chooseList.getSelectedItems().length === 1) {
            dojo.removeClass('actChooseCardSelected', 'disabled');
        } else {
            dojo.addClass('actChooseCardSelected', 'disabled');
        }
    },

    onStarterDeckSelected: function( deck_id )
    {
        this.bgaPerformAction("actPickDeck", { 
            'deck_type':'starter',
            'deck_id':deck_id,
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });        
    },    

    onCityLocationsSelected: function() 
    {
        const actionArray = {
            'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_PickOneLocationForReknown': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_PickTwoLocationsForReknown': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01125_1': 'actPlanningPhase_01125_1',
            'planningPhaseResolveSchemes_01125_2': 'actPlanningPhase_01125_2',
            'planningPhaseResolveSchemes_01125_3': 'actPlanningPhase_01125_3',
            'planningPhaseResolveSchemes_01126_1': 'actPlanningPhase_01126_1',
            'planningPhaseResolveSchemes_01126_2': 'actPlanningPhase_01126_2',
            'planningPhaseResolveSchemes_01144_1': 'actPlanningPhase_01144_1',
            'planningPhaseResolveSchemes_01144_2': 'actPlanningPhase_01144_2',
            'planningPhaseResolveSchemes_01150': 'actPlanningPhase_01150',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        const locations = this.selectedCityLocations.map((loc) => $(loc).getAttribute('data-location'));

        this.bgaPerformAction(action, { 
            'locations': JSON.stringify(locations),
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });
    },

    onChooseCharacterConfirmed: function()
    {
        const actionArray = {
            'highDramaBeginning_01144': 'client_highDramaBeginning_01144_1',
            'planningPhaseResolveSchemes_01125_4': 'actPlanningPhase_01125_4',
        };

        const clientMessageArray = {
            'client_highDramaBeginning_01144_1': "${you} must choose cards from your Faction Hand to pay for selected Mercenary:",
        };

        const action = actionArray[this.gamedatas.gamestate.name];

        //If the action has client_ in the name, we need to call a client side function
        if (action.includes('client_')) {
            this.clientArgs.selectedCharacters = this.selectedCharacters;
            const clientMessage = clientMessageArray[action];
            this.setClientState(action, {
                'descriptionmyturn' : _(clientMessage),
            })
        } else {
            const ids = JSON.stringify(this.selectedCharacters);
            this.bgaPerformAction(action, { 
                'ids' : ids,
            });
        }
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

    onChooseStockCardConfirmed: function()
    {
        var items = this.chooseList.getSelectedItems();
        const card = Object.values(items)[0];
        this.chooseList.removeFromStockById(card.id);
        let action = '';
        switch (this.gamedatas.gamestate.name) {
            case 'planningPhaseResolveSchemes_01044':
                action = 'actPlanningPhase_01044';
                break;
            case 'planningPhaseResolveSchemes_01045':
                action = 'actPlanningPhase_01045';
                break;
        }

        this.bgaPerformAction(action, { 
            'id' : card.id
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });        
    },

    onRecruitCharacterConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        const actionArray = {
            'client_highDramaBeginning_01144_1': 'actHighDramaBeginning_01144',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        this.bgaPerformAction(action, { 
            'recruitId': this.clientArgs.selectedCharacters[0],
            'payWithCards': JSON.stringify(items),
        });        
    },

    onPass: function()
    {
        const actionArray = {
            'planningPhaseResolveSchemes_01125_1': 'actPlanningPhase_01125_1_Pass',
            'planningPhaseResolveSchemes_01125_2': 'actPlanningPhase_01125_2_Pass',
            'planningPhaseResolveSchemes_01125_4': 'actPlanningPhase_01125_4_Pass',
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