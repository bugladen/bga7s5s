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

    onCityLocationsSelected: function() {
        let action = '';
        switch (this.gamedatas.gamestate.name) {
            case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
            case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
            case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
                action = 'actCityLocationsForReknownSelected';
                break;

            case 'planningPhaseResolveSchemes_01125_1': 
                action = 'actPlanningPhase_01125_1';
                break;

            case 'planningPhaseResolveSchemes_01125_2': 
                action = 'actPlanningPhase_01125_2';
                break;

            case 'planningPhaseResolveSchemes_01125_3':
                action = 'actPlanningPhase_01125_3';
                break;

            case 'planningPhaseResolveSchemes_01150':
                action = 'actPlanningPhase_01150';
                break;
        }

        const locations = this.selectedCityLocations.map((loc) => $(loc).getAttribute('data-location'));
        this.bgaPerformAction(action, { 
            'locations': JSON.stringify(locations),
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });
    },

    onChooseCharacterConfirmed: function()
    {
        let action = '';
        switch (this.gamedatas.gamestate.name) {
            case 'planningPhaseResolveSchemes_01125_4':
                action = 'actPlanningPhase_01125_4';
                break;
        }

        console.log(this.selectedCharacters);
        this.bgaPerformAction(action, { 
            'ids' : JSON.stringify(this.selectedCharacters),
        }).then(() =>  {                
            // What to do after the server call if it succeeded
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

    onPass: function()
    {
        let action = '';
        switch (this.gamedatas.gamestate.name) {
            case 'planningPhaseResolveSchemes_01125_1':
                action = 'actPlanningPhase_01125_1_Pass';
                break;
            case 'planningPhaseResolveSchemes_01125_2':
                action = 'actPlanningPhase_01125_2_Pass';
                break;
            case 'planningPhaseResolveSchemes_01125_4':
                action = 'actPlanningPhase_01125_4_Pass';
                break;
            default:
                action = 'actPass';
                break;
        }
        this.bgaPerformAction(action, { 
        }).then(() =>  {                
            // What to do after the server call if it succeeded
        });
    },
});      
});