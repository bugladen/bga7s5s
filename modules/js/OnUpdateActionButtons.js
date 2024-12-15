define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onupdateactionbuttons', null, {

onUpdateActionButtons: function( stateName, args )
{
    console.log( 'onUpdateActionButtons: '+ stateName, args );
                
    if( ! this.isCurrentPlayerActive() )
        return;

    const methods = {
        'pickDecks': () => {
            args.availableDecks.forEach(
                (deck) => { this.addActionButton(`actPickDeck${deck.id}-btn`, _(deck.name), () => this.onStarterDeckSelected(deck.id)) }
            ); 
        },

        'planningPhase': () => {
            this.addActionButton(`actEndPlanningPhase`, _('Confirm Approach Cards'), () => this.onPlanningCardsSelected());
            dojo.addClass('actEndPlanningPhase', 'disabled');
        },

        'planningPhaseResolveSchemes_PickOneLocationForReknown': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_PickTwoLocationsForReknown': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_PickOneLocationForReknownWithNone': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01044': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseStockCardConfirmed());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01045': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseStockCardConfirmed());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01125_1': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01125_2': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01125_3': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01125_4': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseCharacterConfirmed());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01126': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01126_2_client': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01144_1': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01144_2': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'planningPhaseResolveSchemes_01150': () => {
            this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'highDramaBeginning_01144': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseCharacterConfirmed());
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaBeginning_01144_1_client': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onRecruitCharacterConfirmed());
        },

    };

    if( methods[stateName] )
        methods[stateName]();
}

})
});
