define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
    return declare('seventhseacityoffivesails.onupdateactionbuttons_7s5s', null, {
   
    // 7s5s Core Set methods only        
    onUpdateActionButtons_7s5s: function( stateName, args )
    {
        if( ! this.isCurrentPlayerActive() )
            return;

        const methods = {            

            'planningPhaseResolveSchemes_01016': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01016_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01016_3': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },    

            'planningPhaseResolveSchemes_01044': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            
            'planningPhaseResolveSchemes_01045': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            
            'planningPhaseResolveSchemes_01071': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01072': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01098': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01125': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
    
                let numberofLocationsWithReknown = 0;
                const locations = this.getListofAvailableCityLocationImages();
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown > 0) numberofLocationsWithReknown++
                });
                if (numberofLocationsWithReknown === 0) dojo.addClass('actPass', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01125_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01125_3': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01125_4': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
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
    
            'planningPhaseResolveSchemes_01143': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01144': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01144_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01145': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01145_2_client': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01147': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
    
            'planningPhaseResolveSchemes_01150': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01152': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01152_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01152_3': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseEnd_01098': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'planningPhaseEnd_01098_2': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
    
            'highDramaBeginning_01144': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaBeginning_01144_client': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onRecruitCharacterConfirmed());
            },
    
            'highDramaChallengeActionActivateTechnique_01067b': () => {
                this.addActionButton(`actThrust`, _('Choose +1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actRiposte`, _('Choose +1 Riposte'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },
    
            'highDramaPhase01029': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
    
            'highDramaPhase01035': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
    
            'highDramaPhase01035_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actRecruit`, _('Recruit'), () => this.bgaPerformAction('actFromCardWithId', {id: 1})) 
                    this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                }
            },
    
            'highDramaPhase01035_4': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actParley`, _('Parley'), () => this.bgaPerformAction('actFromCardWithId', {id: 1})) 
                    this.addActionButton(`actNoParley`, _('No Parley'), () => this.bgaPerformAction('actFromCardWithId', {id: 0})) 
                }
            },
    
            'highDramaPhase01044': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    args.args.attachments.forEach((attachment) => {
                        this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                    });
                }
            },
            'highDramaPhase01044_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
            'highDramaPhase01044_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    if ( ! args.args.engaged)
                        this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                    this.addActionButton(`actSendHome`, _('Send Home'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
                }
            },
    
            'highDramaPhase01072': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                    this.addActionButton(`actNone`, _('None'), () => this.bgaPerformAction('actFromCardWithId', {id: 0})) 
                    dojo.addClass('actChooseCardSelected', 'disabled');
                    if (args.args.targetCardIds.length > 0)
                        dojo.addClass('actNone', 'disabled');
                }
            },
    
            'highDramaPhase01072_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Muster Card'), () => this.onMusterCardSelected());
                    dojo.addClass('actChooseCardSelected', 'disabled');
    
                    let count = 0;
                    items = this.approachDeck.getAllItems();
                    items.forEach((item) => {
                        card = this.cardProperties[item.id];
                        if (card.type !== 'Scheme') {
                            count++;
                        }
                    });
                    if (count === 0)
                        this.addActionButton(`actNone`, _('None'), () => this.bgaPerformAction('actFromCardWithId', {id: 0}));
                }
            },
    
            'highDramaPhase01147': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01149': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                    dojo.addClass('actCityLocationsSelected', 'disabled');
                }
            },
    
            'highDramaPhase01180': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
            'highDramaPhase01180_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                    this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
            'highDramaPhase01180_4': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
            'highDramaPhase01180_5': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmedFromCard());
                }
            },
    
            'highDramaPhase01185': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardsDiscarded_01185());
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },
    
            'highDramaPhase01189a': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                    dojo.addClass('actCityLocationsSelected', 'disabled');
                }
            },
    
            'highDramaPhase01189b': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                    dojo.addClass('actCityLocationsSelected', 'disabled');
                }
            },
    
            'highDramaPhase01192': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
    
            'highDramaPhase01192_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                    this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
    
            'highDramaPhase01194': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    args.args.attachments.forEach((attachment) => {
                        this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                    });
                }
            },
    
            'highDramaPhase01194_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
    
            'highDramaPhase01197': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01197_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },
    
            'highDramaPhase01197_3': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01200': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },
    
            'highDramaPhase01200_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01205': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
    
            'highDramaPhase01205_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                    this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                    dojo.addClass('actCityLocationsSelected', 'disabled');
                }
            },

            'highDramaChallengeActionActivateTechnique_01063': () => {
                if (this.isCurrentPlayerActive()) {
                    this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },
        
            'duelActionResolveTechnique_01013': () => {
                this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: false}));
                this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: true}));
            },

            'duelChooseTechnique_01063': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'duskPhaseBegin01177': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Character'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'duskPhaseBegin01177_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onCardsChosen_01177_2());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
        };

        if ( methods[stateName] )
            methods[stateName]();
    }
})
});
