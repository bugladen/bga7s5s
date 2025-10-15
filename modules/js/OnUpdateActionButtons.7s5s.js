/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
    return declare('seventhseacityoffivesails.onupdateactionbuttons_7s5s', null, {
   
    // 7s5s Core Set methods only        
    onUpdateActionButtons_7s5s: function( stateName, args )
    {
        const methods = {            

            'setupTable_01006': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'setupTable_01006_2': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },

            'planningPhaseResolveSchemes_01016': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01016_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01016_3': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },    

            'planningPhaseResolveSchemes_01044': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            
            'planningPhaseResolveSchemes_01045': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            
            'planningPhaseResolveSchemes_01071': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01072': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
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
                    const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
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
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {}));
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01152': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseResolveSchemes_01152_2': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
            },
    
            'planningPhaseResolveSchemes_01152_3': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'planningPhaseEnd_01098': () => {
                args.args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
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

            'highDramaPhase01008': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
            'highDramaPhase01008_4': () => {
                this.addActionButton(`actSink`, _('Sink'), () => this.bgaPerformAction('actFromCardWithId', {id: 1})) 
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actPass', {})) 
            },

    
            'highDramaPhase01011': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01012': () => {
                if (! args.args.abnormalFlow)
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01015': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01017': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01019': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01020': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01024': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01025': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01026': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01028': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
            'highDramaPhase01028_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseMultipleInPlayCardsConfirmed());
            },

            'highDramaPhase01029': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01030': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01034': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01034_2': () => {
                this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1})) 
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
            },

            'highDramaPhase01035': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
    
            'highDramaPhase01035_3': () => {
                this.addActionButton(`actRecruit`, _('Recruit'), () => this.bgaPerformAction('actFromCardWithId', {id: 1})) 
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
            },
    
            'highDramaPhase01035_4': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actParley`, _('Parley'), () => this.bgaPerformAction('actFromCardWithId', {id: 1})) 
                this.addActionButton(`actNoParley`, _('No Parley'), () => this.bgaPerformAction('actFromCardWithId', {id: 0})) 
            },

            'highDramaPhase01038': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
            'highDramaPhase01038_3': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01044': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },
            'highDramaPhase01044_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01044_3': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                if ( ! args.args.engaged)
                    this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actSendHome`, _('Send Home'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase01046a': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01049': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01049_2': () => {
                this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actWound`, _('Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase01055': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01055_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01056': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01056_2': () => {
                this.addActionButton(`actMoveHome`, _('Move Home'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actContinue`, _('Continue with Challenge'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase01058': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01059': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01060': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01060_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseMultipleInPlayCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01060_3': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01068': () => {
                if (! args.args.abnormalFlow)
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01068_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01069': () => {
                if (! args.args.abnormalFlow)
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCards', 'disabled');
            },

            'highDramaPhase01069_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01072': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actNone`, _('None'), () => this.bgaPerformAction('actFromCardWithId', {id: 0})) 
                dojo.addClass('actChooseCardSelected', 'disabled');
                if (args.args.targetCardIds.length > 0)
                    dojo.addClass('actNone', 'disabled');
            },
    
            'highDramaPhase01072_2': () => {
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
            },

            'highDramaPhase01076': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01076_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`btnDecline`, _('Decline'), () => this.bgaPerformAction('actFromCardWithId', {id: 0}));
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01081': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01085': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actDone`, _('Done'), () =>  this.bgaPerformAction('actFromCardWithId', {id: 0}));
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01086': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Locations'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01091': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseMultipleInPlayCardsConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01091_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actFactionCardSelected`, _('Confirm'), () => this.onChooseHandCardConfirmed());
                dojo.addClass('actFactionCardSelected', 'disabled');
            },

            'highDramaPhase01092': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01093': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01097': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01102': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardsDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'highDramaPhase01104': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01105': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01106': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },
            'highDramaPhase01106_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                args.args.actions.forEach((action) => {
                    this.addActionButton(`actChooseAction-${action.id}`, action.name, () => this.bgaPerformAction('actFromCardWithActionId', {actionSourceId: action.sourceId, actionId: action.id}));
                });
            },

            'highDramaPhase01147': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01148': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01148_3': () => {
                this.addActionButton(`actFactionCardSelected`, _('Confirm'), () => this.onChooseHandCardConfirmed());
                this.addActionButton(`actFinished`, _('Finished'), () => this.bgaPerformAction('actFromCardWithId', {id: 0}));
                dojo.addClass('actFactionCardSelected', 'disabled');
            },

            'highDramaPhase01148_4': () => {
                if (!args.args.isEngaged)
                    this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actWound`, _('Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },
    
            'highDramaPhase01149': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaPhase01152a': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01152b': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01156': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCards', 'disabled');
            },
            'highDramaPhase01156_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01156_3': () => {
                this.addActionButton(`actEngage`, _('Engage'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actWound`, _('Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'highDramaPhase01160': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01161': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01167': () => {
                args.args.opponents.forEach((opponent) => {
                    this.addActionButton(`actChooseOpponent-${opponent.id}`, opponent.name, () => this.bgaPerformAction('actFromCardWithId', {id: opponent.id}));
                });
            },
            'highDramaPhase01167_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01167_3': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onPaymentConfirmedFromCard());
            },

            'highDramaPhase01171': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01172': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'highDramaPhase01174': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'highDramaPhase01180': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
            'highDramaPhase01180_3': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01180_4': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
            'highDramaPhase01180_5': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onPaymentConfirmedFromCard());
            },
    
            'highDramaPhase01185': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardsDiscarded());
                dojo.addClass('actChooseDiscardCards', 'disabled');
            },
    
            'highDramaPhase01189a': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'highDramaPhase01189b': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },
    
            'highDramaPhase01192': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },
    
            'highDramaPhase01192_3': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01194': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },
    
            'highDramaPhase01194_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
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
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
    
            'highDramaPhase01205_2': () => {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'highDramaChallengeActionResolveTechnique_01063': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },
        
            'duelChooseTechnique_01013': () => {
                this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actFromCardWithId', { id: 0}));
                this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', { id: 1}));
            },

            'duelChooseTechnique_01036': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'duelChooseTechnique_01063': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelChooseTechnique_01067': () => {
                this.addActionButton(`actThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actRiposte`, _('+1 Riposte'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'duelChooseTechnique_01093': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'duelResolveManeuver_01051': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelResolveManeuver_01059': () => {
                this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            },

            'duelResolveManeuver_01077': () => {
                this.addActionButton(`actOk`, _('Ok'), () => this.onMultipleOk());
            },    
            'duelResolveManeuver_01077_2': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelResolveManeuver_01079': () => {
                args.args.attachments.forEach((attachment) => {
                    this.addActionButton(`actChooseAttachment-${attachment.id}`, attachment.name, () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id}));
                });
            },

            'duelResolveManeuver_01103': () => {
                this.addActionButton(`actUseParry`, _('+2 Parry'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actUseThrust`, _('+2 Thrust'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'duelResolveManeuver_01079_2': () => {
                this.addActionButton(`actDestroyWeapon`, _('Destroy Weapon'), () => this.bgaPerformAction('actFromCardWithId', {id: 1}));
                this.addActionButton(`actTakeWound`, _('Take Wound'), () => this.bgaPerformAction('actFromCardWithId', {id: 2}));
            },

            'duelResolveManeuver_01108': () => {
                this.addActionButton(`actChooseDiscardCard`, _('Confirm Selection'), () => this.onCardDiscarded());
                dojo.addClass('actChooseDiscardCard', 'disabled');
            },

            'duelResolveManeuver_01165': () => {
                args._private.args.techniques.forEach((technique) => {
                    this.addActionButton(`actChooseTechnique-${technique.Id}`, technique.Name, () => this.bgaPerformAction('actFromCardWithIds', {ids: JSON.stringify([technique.Id])}));
                });
            },
    
            'duelApplyCombatCardStats_01085': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            },

            'duelEndOfRound_01031': () => {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseInPlayCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardWithId', {id: 0}));
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
