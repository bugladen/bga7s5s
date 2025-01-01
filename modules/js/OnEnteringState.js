define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onenteringstate', null, {

onEnteringState: function( stateName, args )
{
    debug( 'Entering state: '+ stateName, args );

    const methods = {
        'dawnBeginning': (args) => {
            $('city-day-phase').innerHTML = _('Dawn');
            dojo.style('city-day-phase', 'display', 'block');            
        },

        'planningPhase': () => {
            $('city-day-phase').innerHTML = _('Planning');
            //Enable the approach deck
            this.approachDeck.setSelectionMode(2);
        },

        'planningPhaseResolveSchemes_PickOneLocationForReknown': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01044': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose-container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose-container-name').innerHTML = _('Your Discard Pile');

                // For each card in the players discard pile, create a stock item
                const player = this.gamedatas.players[this.getActivePlayerId()];      
                player.discard.forEach((card) => {
                    if (card.type === 'Attachment')
                        this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(1);

                if (this.chooseList.count() > 0) 
                    dojo.addClass('actPass', 'disabled');
            }
        },

        'planningPhaseResolveSchemes_01045': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose-container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose-container-name').innerHTML = _('Mercenaries in the City Deck Discard Pile');

                // For each card in the city discard pile, create a stock item
                this.gamedatas.cityDiscard.forEach((card) => {
                    if (card.traits.includes('Mercenary')) {
                        this.addCardToDeck(this.chooseList, card);
                    }                            

                });
                this.chooseList.setSelectionMode(1);

                if (this.chooseList.count() > 0) 
                    dojo.addClass('actPass', 'disabled');
            }
        },

        'planningPhaseResolveSchemes_01072': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown > 0) return;
        
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01098': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 2;
                locations.forEach((location) => {
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01125_1': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01125_2': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                let count = 0;
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown == 0) return;

                    count++;

                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });

                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01125_3': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];

                const locations = this.getListOfLocationsAdjacentToLocation(selectedLocationElement.id);
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01125_4': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCharactersSelectable = 1;
                let count = 0;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                        //Get the element that is a child of card.divId with the class 'card'
                        const imageElement = dojo.query('.card', card.divId)[0];
                        dojo.addClass(imageElement, 'selectable');
                        dojo.style(imageElement, 'cursor', 'pointer');

                        const handle = dojo.connect(imageElement, 'onclick', this, 'onCharacterClicked');
                        this.connects.push(handle);

                        count++;
                    }
                }
                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01126': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofOutermostCityLocations();
                this.numberOfCityLocationsSelectable = 1;

                locations.forEach((location) => {
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },
                

        'planningPhaseResolveSchemes_01126_2_client': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = $(this.clientStateArgs.selectedCityLocations[0]);
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 2;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;
        
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01144_1': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01144_2': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];

                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01145': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                let count = 0;
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    console.log(location)
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown == 0) return;

                    count++;

                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });

                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01145_2_client': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = $(this.clientStateArgs.selectedCityLocations[0]);
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;
        
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01150': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    if (location == 'forum-image')
                    {
                        dojo.addClass(location, 'darkened');
                        return;
                    } 

                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown === 0) return;
        
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseEnd_01098': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCharactersSelectable = 1;
                let count = 0;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.traits.includes('Leader') && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                        //Get the element that is a child of card.divId with the class 'card'
                        const imageElement = $(`${card.divId}_image`);
                        dojo.addClass(imageElement, 'selectable');
                        dojo.style(imageElement, 'cursor', 'pointer');

                        const handle = dojo.connect(imageElement, 'onclick', this, 'onCharacterClicked');
                        this.connects.push(handle);

                        count++;
                    }
                }
                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseEnd_01098_2': () => {
            dojo.removeClass('choose-container', 'hidden');
            dojo.removeClass('chooseList', 'hidden');
            $('choose-container-name').innerHTML = _('Revealed Card');

            // For each card in the players discard pile, create a stock item
            this.addCardToDeck(this.chooseList, args.args.card);
            this.chooseList.setSelectionMode(0);
    },

        'highDramaBeginning': () => {
            $('city-day-phase').innerHTML = _('High Drama');
        },

        'highDramaBeginning_01144': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCharactersSelectable = 1;
                this.clientStateArgs.discount = args.args.discount;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && !card.controllerId && this.isCardInCity(card.id) ) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, 'selectable');
                        dojo.style(image, 'cursor', 'pointer');

                        const cost = $(`${card.divId}_wealth_cost`);
                        let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
                        discountedCost = discountedCost < 0 ? 0 : discountedCost;
                        cost.innerHTML = parseInt(discountedCost);
                        dojo.addClass(cost, 'discounted-wealth-cost');

                        const handle = dojo.connect(image, 'onclick', this, 'onCharacterClicked');
                        this.connects.push(handle);                        
                    }
                }
            }
        },

        'highDramaBeginning_01144_1_client': () => {
            const card = this.cardProperties[this.clientStateArgs.selectedCharacters[0]];
            const image = $(`${card.divId}_image`);
            dojo.addClass(image, 'selectable');

            const cost = $(`${card.divId}_wealth_cost`);
            let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
            discountedCost = discountedCost < 0 ? 0 : discountedCost;
            this.clientStateArgs.discountedCost = discountedCost;
            cost.innerHTML = parseInt(discountedCost);
            dojo.addClass(cost, 'discounted-wealth-cost');

            this.factionHand.setSelectionMode(2);
        },
    };
    
    if (methods[stateName]) {
        methods[stateName](args);
    }
}

})
});
