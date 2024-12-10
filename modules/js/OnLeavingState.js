define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onleavingstate', null, {

onLeavingState: function( stateName )
{
    console.log( 'Leaving state: '+stateName );
    
    switch( stateName )
    {
        case 'planningPhase':
            this.approachDeck.setSelectionMode(0);
            break;

        case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
        case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
        case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
        case 'planningPhaseResolveSchemes_01125_1': 
        case 'planningPhaseResolveSchemes_01125_2': 
        case 'planningPhaseResolveSchemes_01125_3':
        case 'planningPhaseResolveSchemes_01126_1':
        case 'planningPhaseResolveSchemes_01126_2':
        case 'planningPhaseResolveSchemes_01144_1':
        case 'planningPhaseResolveSchemes_01144_2':
        case 'planningPhaseResolveSchemes_01150':
            const locations = this.getListofAvailableCityLocationImages();
            locations.forEach((location) => {
                dojo.removeClass(location, 'selectable');
                dojo.removeClass(location, 'selected');
                dojo.style(location, 'cursor', 'default');
            });
            break;

        case 'planningPhaseResolveSchemes_01125_4':
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                    const imageElement = dojo.query('.card', card.divId)[0];
                    dojo.removeClass(imageElement, 'selectable');
                    dojo.removeClass(imageElement, 'selected');
                    dojo.style(imageElement, 'cursor', 'default');
                }
            }
            break;

        case 'planningPhaseResolveSchemes_01044':
        case 'planningPhaseResolveSchemes_01045':
            dojo.addClass('choose-container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
            break;
    }

    this.selectedCityLocations = [];
    this.selectedCharacters = [];

    //Disconnect any connect handlers that were created
    this.connects.forEach((handle) => {
        dojo.disconnect(handle);
    });
}

})
});
