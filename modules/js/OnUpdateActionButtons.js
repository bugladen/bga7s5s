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

            //Enable the approach deck.  Here because onEnteringState can't be used to multiactive client states
            this.approachDeck.setSelectionMode(2);
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
                if (args._private.canChallenge)
                    this.addActionButton(`actChallengeAction`, _('Challenge'), () => this.bgaPerformAction('actHighDramaChallengeActionStart', {}));
                if (args._private.canClaim)
                    this.addActionButton(`actClaimAction`, _('Claim'), () => this.bgaPerformAction('actHighDramaClaimActionStart', {}));
                if (args._private.canEquip)
                    this.addActionButton(`actEquipAction`, _('Equip'), () => this.bgaPerformAction('actHighDramaEquipActionStart', {}));
                if (args._private.canMove)
                    this.addActionButton(`actMoveAction`, _('Move'), () => this.bgaPerformAction('actHighDramaMoveActionStart', {}));
                if (args._private.canRecruit)
                    this.addActionButton(`actRecruitAction`, _('Recruit'), () => this.bgaPerformAction('actHighDramaRecruitActionStart', {}));
                if (args._private.hasInPlayActions)
                {
                    this.addActionButton(`btnInPlayAction`, _('In-Play Action'), () => this.bgaPerformAction('actHighDramaChooseInPlayActionStart', {})) 
                    this.addTooltipHtml( 'btnInPlayAction', `<div class='basic-tooltip'>${_("Use an In-Play Action")}</div>` );
                }
                        
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

        'highDramaRecruitActionPayForMercenary_client': () => {
            this.addActionButton(`actBack`, _('<'), () => 
                this.setClientState('highDramaRecruitActionChooseMercenary',
                    {
                        'descriptionmyturn' : _("${you} are performing a Recruit Action.  Choose a Mercenary to recruit:"),
                    }));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onRecruitCharacterConfirmed());
        },

        'highDramaInPlayActionChooseAction'  : () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            args._private.actions.forEach((action, index) => { 
                this.addActionButton(
                    `btnChooseTechnique_${action.id}`, _(action.name), () => this.bgaPerformAction('actHighDramaInPlayActionChosen', { actionId: action.id})) 
            });
        },

        'highDramaInPlayActionChoosePerformer'  : () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
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
            if (args._private.attachmentsInHand.length > 0) {
                this.addActionButton(`actChooseFromHand`, _('Equip from Hand'), () => this.bgaPerformAction('actSimpleTransition', {transition: 'equipFromHand'}));
            }
            if (args._private.attachmentsInPlay.length > 0) {
                this.addActionButton(`actChooseFromPlay`, _('Equip from Play'), () => this.bgaPerformAction('actSimpleTransition', {transition: 'equipFromPlay'}));
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandAttachmentConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaEquipActionPayForAttachmentFromHand': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmed());
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
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
                args.techniques.forEach((technique) => { 
                    this.addActionButton(
                        `actChooseTechnique${technique.id}-btn`, _(technique.name), () => this.bgaPerformAction('actHighDramaChallengeActionTechniqueActivated', { techniqueId: technique.id})) 
                    });
                this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
            }
        },
        
        'highDramaChallengeActionAcceptChallenge': () => {
            this.addActionButton(`btnAccept`, _('Accept'), () => this.bgaPerformAction('actHighDramaChallengeActionAccept', {})) 
            this.addActionButton(`btnReject`, _('Reject'), () => this.bgaPerformAction('actHighDramaChallengeActionReject', {})) 
            this.addActionButton(`actChooseCardSelected`, _('Intervene'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaPhase01180': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
                this.addActionButton(`actPass`, _('Pass'), () => this.bgaPerformAction('actFromCardPass', {})) 
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaPhase01180_2': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaPhase01180_3': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmedFromCard());
            }
        },

        'duelChooseAction': () => {
            if (args._private.combatCardAvailable)
                this.addActionButton(`btnGamble`, _(`Gamble (${args._private.gamblesLeft} Left)`), () => this.bgaPerformAction('actDuelActionGamble', {})) 
            if (args._private.maneuversAvailable)
                this.addActionButton(`btnManueuver`, _('Character Maneuver'), () => this.bgaPerformAction('actDuelActionChooseManeuver', {})) 
            if (args._private.techniquesAvailable)
            {
                this.addActionButton(`btnTechnique`, _('Technique'), () => this.bgaPerformAction('actDuelActionChooseTechnique', {})) 
                this.addTooltipHtml( 'btnTechnique', `<div class='basic-tooltip'>${_("Add Technique from Character or Attachment")}</div>` );
            }
            if (args._private.combatCardAvailable)
            {
                this.addActionButton(`btnCombatCard`, _('Combat Card'), () => this.onDuelChooseCombatCardConfirmed());
                dojo.addClass('btnCombatCard', 'disabled');
                this.addTooltipHtml( 'btnCombatCard', `<div class='basic-tooltip'>${_("Play Combat card. Choose Maneuvers on card.")}</div>` );
            }

            this.addActionButton(`btnDone`, _('End Round'), () => this.bgaPerformAction('actDuelDoneRound', {})) 
            
        },

        'duelChooseTechnique': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            args.techniques.forEach((technique) => { 
                this.addActionButton(
                    `btnChooseTechnique_${technique.id}`, _(technique.name), () => this.bgaPerformAction('actDuelTechniqueChosen', { techniqueId: technique.id})) 
            });
        },

        'duelActionResolveTechnique_01013': () => {
            this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: false}));
            this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: true}));
        },

        'duelUseManeuverFromCombatCard': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            args._private.maneuvers.forEach((maneuver) => { 
                this.addActionButton(
                    `btnChooseManeuver_${maneuver.id}`, _(maneuver.name), () => this.bgaPerformAction('actDuelUseManeuverFromCombatCard', { maneuverId: maneuver.id})) 
            });
            this.addActionButton(`btnDecline`, _('Decline'), () => this.bgaPerformAction('actDuelUseManeuverFromCombatCardDeclined', {}));
        },

        'duelPayForManeuverFromCombatCard': () => {
            this.addActionButton(`actBack`, _('<'), () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onCombatCardPaymentConfirmed());
        },

        'duelChooseGambleCard': () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
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

        'duskPhaseDiscard': () => {
            this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardsChosenForDiscard());
            dojo.addClass('actChooseDiscardCards', 'disabled');

            //Code here instead of onEnteringState because multiactive client states are not ready at that point
            dojo.place('factionHand-container', 'city', 'before');
    
            const player = this.gamedatas.players[this.player_id];
            const leader = player.leader;
            const panache = leader.panache;
            const count = this.factionHand.count();

            $('faction_hand_info').innerHTML = `(${count - panache} cards to discard)`;
            this.factionHand.setSelectionMode(2);

        }
    };

    if( methods[stateName] )
        methods[stateName]();
}

})
});
