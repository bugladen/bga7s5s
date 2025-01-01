define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onleavingstate', null, {

onLeavingState: function( stateName )
{
    debug( 'Leaving state: '+ stateName );

    const methods = {

        'planningPhase': () => {
            this.approachDeck.setSelectionMode(0);
        },

        'planningPhaseResolveSchemes_PickOneLocationForReknown': () => {
            this.resetCityLocations();
        },
        
        'planningPhaseResolveSchemes_01044': () => {
            dojo.addClass('choose-container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },
        
        'planningPhaseResolveSchemes_01045': () => {
            dojo.addClass('choose-container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'planningPhaseResolveSchemes_01072': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01098': () => {
            this.resetCityLocations();
        },
        
        'planningPhaseResolveSchemes_01125_1': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01125_2': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01125_3': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01125_4': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                    const imageElement = dojo.query('.card', card.divId)[0];
                    dojo.removeClass(imageElement, 'selectable');
                    dojo.removeClass(imageElement, 'selected');
                    dojo.style(imageElement, 'cursor', 'default');
                }
            }
        },

        'planningPhaseResolveSchemes_01126': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01126_2_client': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01144_1': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01144_2': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01145': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01145_2_client': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01150': () => {
            dojo.removeClass("forum-image", 'darkened');
            this.resetCityLocations();
        },

        'planningPhaseEnd_01098': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.traits.includes('Leader') && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                    //Get the element that is a child of card.divId with the class 'card'
                    const imageElement = $(`${card.divId}_image`);
                    dojo.removeClass(imageElement, 'selectable');
                    dojo.removeClass(imageElement, 'selected');
                    dojo.style(imageElement, 'cursor', 'default');
                }
            }
        },

        'planningPhaseEnd_01098_2': () => {
            dojo.addClass('choose-container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'highDramaBeginning_01144': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && this.isCardInCity(card.id)) {
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'selectable');
                    dojo.removeClass(image, 'selected');
                    dojo.style(image, 'cursor', 'default');

                    const cost = $(`${card.divId}_wealth_cost`);
                    cost.innerHTML = card.wealthCost;
                    dojo.removeClass(cost, 'discounted-wealth-cost');
                }
            }
        },

        'highDramaBeginning_01144_1_client': () => {
            this.factionHand.setSelectionMode(0);
            this.clientStateArgs = {};
        },

    };

    if (methods[stateName]) {
        methods[stateName]();
    }

    this.selectedCityLocations = [];
    this.selectedCharacters = [];

    //Disconnect any connect handlers that were created
    this.connects.forEach((handle) => {
        dojo.disconnect(handle);
    });
},

resetCityLocations: function() {
    const locations = this.getListofAvailableCityLocationImages();
    locations.forEach((location) => {
        dojo.removeClass(location, 'selectable');
        dojo.removeClass(location, 'selected');
        dojo.style(location, 'cursor', 'default');
    });
},

})
});
