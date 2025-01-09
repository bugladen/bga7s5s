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
            case 'highDramaBeginning_01144_1_client':
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

        //Generic method when no specific logic is needed
        const actionMap = {
            'planningPhaseResolveSchemes_01016': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01071': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01072': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01098': 'actCityLocationsForReknownSelected',
            'planningPhaseResolveSchemes_01125': 'actPlanningPhase_01125',
            'planningPhaseResolveSchemes_01125_2': 'actPlanningPhase_01125_2',
            'planningPhaseResolveSchemes_01125_3': 'actPlanningPhase_01125_3',
            'planningPhaseResolveSchemes_01126': 'planningPhaseResolveSchemes_01126_2_client',
            'planningPhaseResolveSchemes_01143': 'actPlanningPhase_01143',
            'planningPhaseResolveSchemes_01144_1': 'actPlanningPhase_01144_1',
            'planningPhaseResolveSchemes_01144_2': 'actPlanningPhase_01144_2',
            'planningPhaseResolveSchemes_01145': 'planningPhaseResolveSchemes_01145_2_client',
            'planningPhaseResolveSchemes_01145_2_client': 'actPlanningPhase_01145',
            'planningPhaseResolveSchemes_01150': 'actPlanningPhase_01150',
            'planningPhaseResolveSchemes_01152': 'actPlanningPhase_01152',
            'planningPhaseResolveSchemes_01152_2': 'actPlanningPhase_01152_2',
            'planningPhaseResolveSchemes_01152_3': 'actPlanningPhase_01152_3',
        };

        const clientMessageArray = {
            'planningPhaseResolveSchemes_01126_2_client': "Leshiye of the Woods: ${you} must choose two other locations to place Reknown onto:",
            'planningPhaseResolveSchemes_01145_2_client': "Inspire Generosity: ${you} must choose a location to move the Reknown to:",
        };

        const action = actionMap[this.gamedatas.gamestate.name];
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

    onChooseCharacterConfirmed: function()
    {
        const actionArray = {
            'highDramaBeginning_01144': 'highDramaBeginning_01144_1_client',
            'planningPhaseEnd_01098': 'actPlanningPhaseEnd_01098',
            'planningPhaseResolveSchemes_01125_4': 'actPlanningPhase_01125_4',
        };

        const clientMessageArray = {
            'highDramaBeginning_01144_1_client': "${you} must choose cards from your Faction Hand to pay for selected Mercenary:",
        };

        const action = actionArray[this.gamedatas.gamestate.name];

        //If the action ends with _client, we need to call a client side function
        if (action.includes('_client')) {
            this.clientStateArgs.selectedCharacters = this.selectedCharacters;
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
        let action = '';
        switch (this.gamedatas.gamestate.name) {
            case 'planningPhaseResolveSchemes_01016_2':
                action = 'actPlanningPhase_01016_2';
                break;
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
            this.chooseList.removeFromStockById(card.id);
        });        
    },

    onRecruitCharacterConfirmed: function()
    {
        var items = this.factionHand.getSelectedItems();
        items = items.map((item) => item.id);

        const actionArray = {
            'highDramaBeginning_01144_1_client': 'actHighDramaBeginning_01144',
        };

        const action = actionArray[this.gamedatas.gamestate.name];
        this.bgaPerformAction(action, { 
            'recruitId': this.clientStateArgs.selectedCharacters[0],
            'payWithCards': JSON.stringify(items),
        }).catch(() =>  {                
            this.setClientState('highDramaBeginning_01144',
                {
                    'descriptionmyturn' : _("${you} may choose a Mercenary from a City Location to recruit to your home:"),
                })
        });        
    },

    onPass: function()
    {
        this.confirmationDialog(_("Are you sure you want to pass?"),
        () => {this.passConfirmed();}
        );
    },

    onMultipleOk: function()
    {
        this.bgaPerformAction("actMultipleOk", { 
        });        
    },

    passConfirmed: function()
    {
        const actionArray = {
            'planningPhaseResolveSchemes_01016_2': 'actPlanningPhase_01016_2_Pass',
            'planningPhaseResolveSchemes_01125': 'actPlanningPhase_01125_Pass',
            'planningPhaseResolveSchemes_01125_2': 'actPlanningPhase_01125_2_Pass',
            'planningPhaseResolveSchemes_01125_4': 'actPlanningPhase_01125_4_Pass',
            'planningPhaseResolveSchemes_01145': 'actPlanningPhase_01145_Pass',            
            'planningPhaseResolveSchemes_01145_2_client': 'actPlanningPhase_01145_Pass',            
            'planningPhaseResolveSchemes_01152': 'actPlanningPhase_01152_Pass',
            'planningPhaseResolveSchemes_01152_2': 'actPlanningPhase_01152_2_Pass',
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