define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onenteringstate', null, {

onEnteringState: function( stateName, args )
{
    console.log( 'Entering state: '+stateName, args );
    
    switch( stateName )
    {
        case 'dawnBeginning':
            $('city-day-phase').innerHTML = _('Dawn');
            dojo.style('city-day-phase', 'display', 'block');
            break;

        case 'planningPhase':
            $('city-day-phase').innerHTML = _('Planning');
            //Enable the approach deck
            this.approachDeck.setSelectionMode(2);
            break;

        case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
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
            break;

        case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
        case 'planningPhaseResolveSchemes_01125_1': 
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
            break;

        case 'planningPhaseResolveSchemes_01125_2': 
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
            break;

        case 'planningPhaseResolveSchemes_01125_3':
            if (this.isCurrentPlayerActive()) {
                // Get all the elements that have the class 'city-location'
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
            break;

        case 'planningPhaseResolveSchemes_01125_4':
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

            break;

        case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
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
            break;
        
        case 'planningPhaseResolveSchemes_01044':
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose-container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose-container-name').innerHTML = _('Your Discard Pile');

                // For each card in the players discard pile, create a stock item
                const player = this.gamedatas.players[this.getActivePlayerId()];      
                player.discard.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(1);

                if (this.chooseList.count() > 0) 
                    dojo.addClass('actPass', 'disabled');
            }
            break;

        case 'planningPhaseResolveSchemes_01045':
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
            break;

        case 'planningPhaseResolveSchemes_01150':
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;


                locations.forEach((location) => {
                    if (location == 'forum-image') return;

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
            break;

        case 'highDramaPhase':
            $('city-day-phase').innerHTML = _('High Drama');
            break;

    }
}

})
});
