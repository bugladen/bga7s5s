define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onupdateactionbuttons', null, {

onUpdateActionButtons: function( stateName, args )
{
    console.log( 'onUpdateActionButtons: '+ stateName, args );
                
    if( this.isCurrentPlayerActive() )
    {            
        switch( stateName )
        {
            case 'pickDecks':    
                args.availableDecks.forEach(
                    (deck) => { this.addActionButton(`actPickDeck${deck.id}-btn`, _(deck.name), () => this.onStarterDeckSelected(deck.id)) }
                ); 
                break;

            case 'planningPhase':
                this.addActionButton(`actEndPlanningPhase`, _('Confirm Approach Cards'), () => this.onPlanningCardsSelected());
                dojo.addClass('actEndPlanningPhase', 'disabled');
                break;

            case 'planningPhaseResolveSchemes_PickOneLocationForReknown':
            case 'planningPhaseResolveSchemes_01126_1': 
            case 'planningPhaseResolveSchemes_01126_2': 
            case 'planningPhaseResolveSchemes_01144_1': 
            case 'planningPhaseResolveSchemes_01144_2':
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
                break;

            case 'planningPhaseResolveSchemes_PickTwoLocationsForReknown':
            case 'planningPhaseResolveSchemes_01125_3':
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
                break;

            case 'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone':
            case 'planningPhaseResolveSchemes_01125_1': 
            case 'planningPhaseResolveSchemes_01125_2': 
            case 'planningPhaseResolveSchemes_01150':
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
                break;

            case 'client_highDramaBeginning_01144_1':
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onRecruitCharacterConfirmed());
                break;

            case 'planningPhaseResolveSchemes_01044':
            case 'planningPhaseResolveSchemes_01045':
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseStockCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
                break;

            case 'highDramaBeginning_01144':
            case 'planningPhaseResolveSchemes_01125_4':
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseCharacterConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
                break;


            case 'playerTurn':
                break;
        }
    }
}

})
});