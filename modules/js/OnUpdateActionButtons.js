define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onupdateactionbuttons', null, {

onUpdateActionButtons: function( stateName, args )
{
    debug( 'onUpdateActionButtons: '+ stateName, args );
                
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

        'highDramaPlayerTurn': () => {
            if (this.isCurrentPlayerActive()) {
                if (args.canChallenge)
                    this.addActionButton(`actChallengeAction`, _('Challenge'), () => this.bgaPerformAction('actHighDramaChallengeActionStart', {}));
                if (args.canClaim)
                    this.addActionButton(`actClaimAction`, _('Claim'), () => this.bgaPerformAction('actHighDramaClaimActionStart', {}));
                if (args.canEquip)
                    this.addActionButton(`actEquipAction`, _('Equip'), () => this.bgaPerformAction('actHighDramaEquipActionStart', {}));
                if (args.canMove)
                    this.addActionButton(`actMoveAction`, _('Move'), () => this.bgaPerformAction('actHighDramaMoveActionStart', {}));
                if (args.canRecruit)
                    this.addActionButton(`actRecruitAction`, _('Recruit'), () => this.bgaPerformAction('actHighDramaRecruitActionStart', {}));
                
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
            }
        },

        'highDramaMoveActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaMoveActionChooseLocation': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            }
        },

        'highDramaRecruitActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaRecruitActionParley': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseYes`, _('Yes'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyYes', {}));
                this.addActionButton(`actChooseNo`, _('No'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyNo', {}));
            }
        },

        'highDramaRecruitActionChooseMercenary': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaRecruitActionChooseMercenary_client': () => {
            this.addActionButton(`actBack`, _('<'), () => 
                this.setClientState('highDramaRecruitActionChooseMercenary',
                    {
                        'descriptionmyturn' : _("${you} are performing a Recruit Action.  Choose a Mercenary to recruit:"),
                    }));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onRecruitCharacterConfirmed());
        },

        'highDramaEquipActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaEquipActionChooseAttachmentLocation': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            if (args.attachmentsInHand.length > 0) {
                this.addActionButton(`actChooseFromHand`, _('Equip from Hand'), () => {
                    this.setClientState('highDramaEquipActionChooseAttachmentFromHand_client', {
                        'descriptionmyturn' : _("${you} are performing an Equip Action.  Choose an Attachment to equip from Your Hand:"),
                    })
                });
            }
            if (args.attachmentsInPlay.length > 0) {
                this.addActionButton(`actChooseFromPlay`, _('Equip from Play'), () => {
                    this.setClientState('highDramaEquipActionChooseAttachmentFromPlay_client', {
                        'descriptionmyturn' : _("${you} are performing an Equip Action.  Choose an Attachment to equip from play:"),
                    })
            });
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand_client': () => {
            this.addActionButton(`actBack`, _('<'), () => {
                this.setClientState('highDramaEquipActionChooseAttachmentLocation', {
                    'descriptionmyturn' : _("${you} are performing an Equip Action.  Choose an Attachment Location to equip from:"),
                })
            });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandAttachmentConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaEquipActionChooseAttachmentFromPlay_client': () => {
            this.addActionButton(`actBack`, _('<'), () => {
                this.setClientState('highDramaEquipActionChooseAttachmentLocation', {
                    'descriptionmyturn' : _("${you} are performing an Equip Action.  Choose an Attachment Location to equip from:"),
                })
            });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaEquipActionPayForAttachmentFromHand_client': () => {
            this.addActionButton(`actBack`, _('<'), () => {
                this.setClientState('highDramaEquipActionChooseAttachmentFromHand_client', {
                    'descriptionmyturn' : _("${you} are performing an Equip Action.  Choose an Attachment to equip from Your Hand:"),
                })
            });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmed());
        },

        'highDramaEquipActionPayForAttachmentFromPlay_client': () => {
            this.addActionButton(`actBack`, _('<'), () => {
                this.setClientState('highDramaEquipActionChooseAttachmentFromPlay_client', {
                    'descriptionmyturn' : _("${you} are performing an Equip Action.  Choose an Attachment to equip from play:"),
                })
            });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmed());
        },

        'highDramaClaimActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaChallengeActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaChallengeActionChooseTarget': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaChallengeActionActivateTechnique': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
                args.techniques.forEach((technique) => { 
                    this.addActionButton(
                        `actChooseTechnique${technique.id}-btn`, _(technique.name), () => this.bgaPerformAction('actHighDramaChallengeActionTechniqueActivated', { techniqueId: technique.id})) 
                });
            }
        },
        
        'highDramaChallengeActionAcceptChallenge': () => {
            this.addActionButton(`btnAccept`, _('Accept'), () => this.bgaPerformAction('actHighDramaChallengeActionAccept', {})) 
            this.addActionButton(`btnReject`, _('Reject'), () => this.bgaPerformAction('actHighDramaChallengeActionReject', {})) 
            this.addActionButton(`actChooseCardSelected`, _('Intervene'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'duelChooseAction': () => {
            if (args.combatCardAvailable)
                this.addActionButton(`btnGamble`, _(`Gamble (${args.gamblesLeft} Left)`), () => this.bgaPerformAction('actDuelActionGamble', {})) 
            if (args.maneuversAvailable)
                this.addActionButton(`btnManueuver`, _('Character Maneuver'), () => this.bgaPerformAction('actDuelActionChooseManeuver', {})) 
            if (args.techniqueAvailable)
            {
                this.addActionButton(`btnTechnique`, _('Technique'), () => this.bgaPerformAction('actDuelActionChooseTechnique', {})) 
                this.addTooltipHtml( 'btnTechnique', `<div class='basic-tooltip'>${_("Add Technique from Character or Attachment")}</div>` );
            }
            if (args.combatCardAvailable)
            {
                this.addActionButton(`btnCombatCard`, _('Combat Card'), () => this.onDuelChooseCombatCardConfirmed());
                dojo.addClass('btnCombatCard', 'disabled');
                this.addTooltipHtml( 'btnCombatCard', `<div class='basic-tooltip'>${_("Play Combat card. Choose Maneuvers on card.")}</div>` );
            }

            this.addActionButton(`btnDone`, _('End Round'), () => this.bgaPerformAction('actDuelChooseActionEndRound', {})) 
            
        },

        'duelChooseTechnique': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            args.techniques.forEach((technique) => { 
                this.addActionButton(
                    `btnChooseTechnique${technique.id}-btn`, _(technique.name), () => this.bgaPerformAction('actDuelTechniqueChosen', { techniqueId: technique.id})) 
            });
        },

        'duelActionResolveTechnique_01013': () => {
            this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: false}));
            this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: true}));
        },

        'duelChooseGambleCard': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },
    };

    if( methods[stateName] )
        methods[stateName]();
}

})
});
