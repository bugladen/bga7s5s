define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onupdateactionbuttons', null, {

onUpdateActionButtons: function( stateName, args )
{
    debug( 'onUpdateActionButtons: '+ stateName, args );

    // This lives outside of the methods object because it is dependent on the playing being active or not.
    // It contains logic to display or hide a special modal to choose a deck.
    // It must no longer be shown after the player has selected a deck.
    // Once a player has chosen an action in a multi-active client state and waiting on other players, 
    // only onUpdateActionButtons is called, so that's why the code lives here.
    if (stateName === 'pickDecks') {
        if(this.isCurrentPlayerActive())
        {
            args.availableDecks.forEach(
                (deck) => { this.addActionButton(`actPickDeck${deck.id}-btn`, _(deck.name), () => this.onStarterDeckSelected(deck.id)) }
            );
    
            if ( ! document.getElementById('deck-picker'))
            {
                dojo.addClass('city', 'hidden');
                dojo.addClass('approachDeck-container', 'hidden');
                dojo.addClass('factionHand-container', 'hidden');
                dojo.place( this.format_block( 'jstpl_deck_picker', {
                    banner_description: _('Select a Starter Deck to play with using the buttons above.  Or explore the available Factions using the buttons below, and click <strong>Select</strong> to choose that Faction.'),
                    eisen_description: _('<strong>Eisen</strong>: An accomplished General in the War of the Cross, Kaspar Dietrich returned home to Eisen, only to find it in ruins, overrun by monsters. As such, he has a passionate distrust for all things sorcery and supernatural. Kaspar fled south to the port city of Five Sails where he hopes to use his formidable reputation as a master commander and strategist to build an army to reclaim his homeland. He utilizes strategies that involve making use of the city and the mercenaries and equipment available to him.'),
                    montaigne_description: _('<strong>Montaigne</strong>: Odette Dubois d’Arrent is the most recent arrival to the city. She is a courtier from Montaigne, a country that does not have a district or established foothold in Five Sails. She is tasked to help her patron expand his influence within the free city.  As such, she is eager to find allies. But she did not arrive in Five Sails alone. A small, but elite, group of skilled Musketeers accompanies her and protects her from the rougher elements of the City. Her strengths include movement and creative positioning to make the most of her political abilities and her Musketeer’s steel.'),
                    select_description: _('Select This Deck'),
                }),  'city', 'after');
            }
        }
        else
        {
            dojo.destroy('deck-picker');
            dojo.removeClass('city', 'hidden');
            dojo.removeClass('approachDeck-container', 'hidden');
            dojo.removeClass('factionHand-container', 'hidden');
        }
    }
                
    if( ! this.isCurrentPlayerActive() )
        return;

    const methods = {
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
                if (args._private.hasInHandActions)
                    {
                        this.addActionButton(`btnInHandAction`, _('In-Hand Action'), () => this.bgaPerformAction('actHighDramaChooseInHandActionStart', {})) 
                        this.addTooltipHtml( 'btnInHandAction', `<div class='basic-tooltip'>${_("Use an In-Hand Action")}</div>` );
                    }
                            
                this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
            }
        },

        'highDramaMoveActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaMoveActionChooseLocation': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
                dojo.addClass('actCityLocationsSelected', 'disabled');
            }
        },

        'highDramaRecruitActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaRecruitActionParley': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseYes`, _('Yes'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyYes', {}));
                this.addActionButton(`actChooseNo`, _('No'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyNo', {}));
            }
        },

        'highDramaRecruitActionChooseMercenary': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaRecruitActionPayForMercenary': () => {
            if (args.recruitType == 0)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onRecruitCharacterConfirmed());
        },

        'highDramaInPlayActionChooseAction'  : () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            args._private.actions.forEach((action, index) => { 
                this.addActionButton(
                    `btnChooseAction_${action.id}`, action.name, () => this.bgaPerformAction('actHighDramaInPlayActionChosen', { actionId: action.id})) 
            });
    },

        'highDramaInPlayActionChoosePerformer'  : () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaInHandActionChooseAction'  : () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            args._private.actions.forEach((action) => { 
                this.addActionButton(
                    `btnChooseAction_${action.id}`, action.name, () => this.bgaPerformAction('actHighDramaInHandActionChosen', { actionId: action.id})) 
            });
        },

        'highDramaInHandActionChoosePerformer'  : () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaInHandActionPay': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onActionCardFromHandPaymentConfirmed());
        },

        'highDramaEquipActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaEquipActionChooseAttachmentLocation': () => {
            if (args._private.equipType === this.SMUGGLED_ITEM_EQUIP_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backSmuggledItem'}));
            else if (args._private.equipType === this.NORMAL_EQUIP_TYPE) 
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            if (args._private.attachmentsInHand.length > 0) {
                this.addActionButton(`actChooseFromHand`, _('Equip from Hand'), () => this.bgaPerformAction('actSimpleTransition', {transition: 'equipFromHand'}));
            }
            if (args._private.attachmentsInPlay.length > 0) {
                this.addActionButton(`actChooseFromPlay`, _('Equip from Play'), () => this.bgaPerformAction('actSimpleTransition', {transition: 'equipFromPlay'}));
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandAttachmentConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaEquipActionPayForAttachmentFromHand': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmed());
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            if (args._private.equipType === this.LETS_HAGGLE_EQUIP_TYPE) 
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backLetsHaggle'}));
            else
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onAttachmentPaymentConfirmed());
        },

        'highDramaClaimActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaChallengeActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaChallengeActionChooseTarget': () => {
            if (this.isCurrentPlayerActive()) {
                if (args.challengeType == 1)
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backTriskelion'}));
                else if (args.challengeType == 0)
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));

                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        },

        'highDramaChallengeActionActivateTechnique': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                args.techniques.forEach((technique) => { 
                    this.addActionButton(
                        `actChooseTechnique${technique.id}-btn`, technique.name, () => this.bgaPerformAction('actHighDramaChallengeActionTechniqueActivated', { techniqueId: technique.id})) 
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

        'highDramaPhase01147': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
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


        'playerReaction': () => {
            if (this.isCurrentPlayerActive()) {
                args._private.args.buttons.forEach((button, index) => {
                    this.addActionButton(`actReaction-${index}`, button.text, () => this.bgaPerformAction('actReactionForState', {reactionId: button.reaction}));
                });
            }
        },

        'playerPayForReaction': () => {
            if (this.isCurrentPlayerActive()) {
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
                this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onReactionPaymentConfirmed());
            }
        },

        'duelChooseAction': () => {
            if (args._private.gambleAvailable)
            {
                var translated = dojo.string.substitute(
                    _("Gamble (${gamblesLeft} Left)"),
                    {
                        gamblesLeft: args._private.gamblesLeft
                    }
                );
                this.addActionButton(`btnGamble`, translated, () => this.bgaPerformAction('actDuelActionGamble', {})) 
            }
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
            if ( ! args._private.endDuelAvailable)
                this.addActionButton(`btnDone`, _('End Round'), () => this.bgaPerformAction('actDuelDoneRound', {})) 
            if (args._private.endDuelAvailable)
                this.addActionButton(`btnEndDuel`, _('End Duel'), () => this.bgaPerformAction('actDuelEndDuel', {})) 
        },

        'duelChooseTechnique': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            args.techniques.forEach((technique) => { 
                this.addActionButton(
                    `btnChooseTechnique_${technique.id}`, technique.name, () => this.bgaPerformAction('actDuelTechniqueChosen', { techniqueId: technique.id})) 
            });
        },

        'duelActionResolveTechnique_01013': () => {
            this.addActionButton(`btnParry`, _('+1 Parry'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: false}));
            this.addActionButton(`btnThrust`, _('+1 Thrust'), () => this.bgaPerformAction('actDuelActionResolveTechnique_01013', { useThrust: true}));
        },

        'duelUseManeuverFromCombatCard': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            args._private.maneuvers.forEach((maneuver) => { 
                this.addActionButton(
                    `btnChooseManeuver_${maneuver.id}`, _(maneuver.name), () => this.bgaPerformAction('actDuelUseManeuverFromCombatCard', { maneuverId: maneuver.id})) 
            });
            this.addActionButton(`btnDecline`, _('Decline'), () => this.bgaPerformAction('actDuelUseManeuverFromCombatCardDeclined', {}));
        },

        'duelPayForManeuverFromCombatCard': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
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
            this.showHandAtTop();
    
            const player = this.gamedatas.players[this.player_id];
            const leader = player.leader;
            const panache = leader.panache;
            const count = this.factionHand.count();

            const amount = count - panache;
            var translated = dojo.string.substitute(
                _("(${amount} cards to discard)"),
                {
                    amount: amount
                }
            );
            $('faction_hand_info').innerHTML = translated;
            this.factionHand.setSelectionMode(2);

        }
    };

    if( methods[stateName] )
        methods[stateName]();
}

})
});
