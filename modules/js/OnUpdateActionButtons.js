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
return declare('seventhseacityoffivesails.onupdateactionbuttons', null, {

// Main game methods only
onUpdateActionButtons: function( stateName, args )
{
    debug( 'onUpdateActionButtons: '+ stateName, args );

    // This lives outside of the methods object because it is dependent on the player being active or not.
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
                dojo.addClass('factionHand-placeholder', 'hidden');
                dojo.place( this.format_block( 'jstpl_deck_picker', {
                    banner_description: _('Select a Starter Deck to play with using the buttons above.  Or explore the available Factions using the buttons below, and click <strong>Select</strong> to choose that Faction.'),
                    castille_description: _('<strong>Castille</strong>: Soline el Gato grew up on the streets and canals of the Castillian District of Five Sails and knows intimately what it takes to survive in a city such as Five Sails. The leader of a den of thieves and scoundrels, Soline uses her cunning and adaptability to always keep her opponents on their toes, not knowing what to expect. Soline’s style is one of disruption, making it increasingly difficult for an opponent to gain ground.'),
                    eisen_description: _('<strong>Eisen</strong>: An accomplished General in the War of the Cross, Kaspar Dietrich returned home to Eisen, only to find it in ruins, overrun by monsters. As such, he has a passionate distrust for all things sorcery and supernatural. Kaspar fled south to the port city of Five Sails where he hopes to use his formidable reputation as a master commander and strategist to build an army to reclaim his homeland. He utilizes strategies that involve making use of the city and the mercenaries and equipment available to him.'),
                    montaigne_description: _('<strong>Montaigne</strong>: Odette Dubois d’Arrent is the most recent arrival to the city. She is a courtier from Montaigne, a country that does not have a district or established foothold in Five Sails. She is tasked to help her patron expand his influence within the free city.  As such, she is eager to find allies. But she did not arrive in Five Sails alone. A small, but elite, group of skilled Musketeers accompanies her and protects her from the rougher elements of the City. Her strengths include movement and creative positioning to make the most of her political abilities and her Musketeer’s steel.'),
                    ussura_description: _('<strong>Ussura</strong>: Yevgeni the Boar is a man so large and foreboding that even the elements seem to bow down before him. He has become something of a folk hero in the eyes of the Ussuran district and his reputation as such extends well beyond the cast of his shadow. He is not native to Five Sails, but has no memory of his life prior to his arrival. Because of this, he searches for the answers of his past. Yevgeni’s style is much like the man himself; bold, direct, and powerful. He prefers to get the job done himself rather than send anyone else to do the work.'),
                    vodacce_description: _('<strong>Vodacce</strong>: “Don” Constanzo Scarpa loves his city, for Five Sails is indeed his city, and he is willing to do whatever it takes to protect it, even if it is from itself. Reputation, family and loyalty are the things that are of paramount importance to Constanzo as he tries to advance politically through the ranks of the city’s elite. Constanzo’s style is cutthroat and brutal where the ends always justify the means. He cares not for what or even who gets sacrificed along the way as long as it advances his goals.'),
                    select_description: _('Select This Deck'),
                }),  'city', 'after');
            }
        }
        else if (! this.isSpectator)
        {
            dojo.destroy('deck-picker');
            dojo.removeClass('city', 'hidden');
            dojo.removeClass('approachDeck-container', 'hidden');
            dojo.removeClass('factionHand-placeholder', 'hidden');
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

        'highDramaPlayerTurn': () => {
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
                this.addTooltipHtml( 'btnInPlayAction', `<div class='_7sfs-basic-tooltip'>${_("Use an In-Play Action")}</div>` );
            }
            if (args._private.hasInHandActions)
            {
                this.addActionButton(`btnInHandAction`, _('In-Hand Action'), () => this.bgaPerformAction('actHighDramaChooseInHandActionStart', {})) 
                this.addTooltipHtml( 'btnInHandAction', `<div class='_7sfs-basic-tooltip'>${_("Use an In-Hand Action")}</div>` );
            }
            if (args._private.hasBrutes)
                this.addActionButton(`btnBrute`, _('Play Brute'), () => this.bgaPerformAction('actHighDramaChooseBruteStart', {})) 
                        
            this.addActionButton(`actPass`, _('Pass'), () => this.onConfirmPass());
        },

        'highDramaMoveActionChoosePerformer': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaMoveActionChooseLocation': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'highDramaRecruitActionChoosePerformer': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaRecruitActionParley': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseYes`, _('Yes'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyYes', {}));
            this.addActionButton(`actChooseNo`, _('No'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyNo', {}));
        },

        'highDramaRecruitActionChooseMercenary': () => {
            if (args.recruitType == this.NORMAL_RECRUIT_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaRecruitActionPayForMercenary': () => {
            if (args.recruitType == this.NORMAL_RECRUIT_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            else if (args.recruitType == this.KASPAR_RECRUIT_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backKaspar'}));
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
            if (! args._private.abnormalFlow)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaInHandActionPay': () => {
            if (! args._private.abnormalFlow)
            {
                if (args._private.requiresPerformerSelected)
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backPerformer'}));
                else
                    this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backChooseAction'}));
            }
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onActionCardFromHandPaymentConfirmed());
        },

        'highDramaBruteActionChooseBrute'  : () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandCardConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaBruteActionPayForBrute': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onPaymentConfirmed());
        },

        'highDramaEquipActionChoosePerformer': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
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
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandCardConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaEquipActionPayForAttachmentFromHand': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onPaymentConfirmed());
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            if (args._private.equipType === this.LETS_HAGGLE_EQUIP_TYPE) 
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backLetsHaggle'}));
            else
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onPaymentConfirmed());
        },

        'highDramaClaimActionChoosePerformer': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaChallengeActionChoosePerformer': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaChallengeActionChooseTarget': () => {
            if (args.challengeType == this.NORMAL_CHALLENGE_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            else if (args.challengeType == this.TRISKELION_CHALLENGE_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backTriskelion'}));
            else if (args.challengeType == this.EPEE_SANGLANTE_CHALLENGE_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backEpeeSanglante'}));
            else if (args.challengeType == this.CAVALIER_HAT_CHALLENGE_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backCavalierHat'}));

            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaChallengeActionActivateTechnique': () => {
            if (args.challengeType != this.SERVO_SCARPA_CHALLENGE_TYPE)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            args.techniques.forEach((technique) => { 
                this.addActionButton(
                    `actChooseTechnique${technique.id}-btn`, technique.name, () => this.bgaPerformAction('actHighDramaChallengeActionTechniqueActivated', { techniqueId: technique.id})) 
            });
            this.addActionButton(`actPass`, _('Pass'), () => this.onPass());
        },
        
        'highDramaChallengeActionAcceptChallenge': () => {
            this.addActionButton(`btnAccept`, _('Accept'), () => this.bgaPerformAction('actHighDramaChallengeActionAccept', {})) 
            this.addActionButton(`btnRefuse`, _('Refuse'), () => this.bgaPerformAction('actHighDramaChallengeActionReject', {})) 
            this.addActionButton(`actChooseCardSelected`, _('Intervene'), () => this.onChooseInPlayCardConfirmed());
            if (args.challengeType == this.EPEE_SANGLANTE_CHALLENGE_TYPE)
                dojo.addClass('btnRefuse', 'disabled');
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'playerReaction': () => {
            args._private.args.buttons.forEach((button, index) => {
                this.addActionButton(`actReaction-${index}`, button.text, () => this.bgaPerformAction('actReactionForState', {reactionId: button.reaction}));
            });
        },

        'playerPayForReaction': () => {
            this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onReactionPaymentConfirmed());
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
                this.addTooltipHtml( 'btnTechnique', `<div class='_7sfs-basic-tooltip'>${_("Add Technique from Character or Attachment")}</div>` );
            }
            if (args._private.combatCardAvailable)
            {
                this.addActionButton(`btnCombatCard`, _('Combat Card'), () => this.onDuelChooseCombatCardConfirmed());
                dojo.addClass('btnCombatCard', 'disabled');
                this.addTooltipHtml( 'btnCombatCard', `<div class='_7sfs-basic-tooltip'>${_("Play Combat card. Choose Maneuvers on card.")}</div>` );
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

        'duelUseManeuverFromCombatCard': () => {
            if (! args._private.gambled && ! args._private.abnormalFlow)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            args._private.maneuvers.forEach((maneuver) => { 
                this.addActionButton(
                    `btnChooseManeuver_${maneuver.id}`, maneuver.name, () => this.bgaPerformAction('actDuelUseManeuverFromCombatCard', { maneuverId: maneuver.id})) 
            });
            this.addActionButton(`btnDecline`, _('Decline'), () => this.bgaPerformAction('actDuelUseManeuverFromCombatCardDeclined', {}));
        },

        'duelPayForManeuverFromCombatCard': () => {
            if (args._private.gambled || args._private.abnormalFlow)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backAbnormalFlow'}));
            else
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actPayForCards`, _('Confirm'), () => this.onCombatCardPaymentConfirmed());
        },

        'duelChooseGambleCard': () => {
            if (args._private.cards.length == 0)
                this.addActionButton(`actBack`, '<', () => this.bgaPerformAction('actBack', {}));
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'duskPhaseDiscard': () => {
            this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardsChosenForDiscard());
            dojo.addClass('actChooseDiscardCards', 'disabled');

            //Code here instead of onEnteringState because multiactive client states are not ready at that point
            const player = this.gamedatas.players[this.player_id];
            const leader = player.leader;
            const panache = leader.panache;
            const count = this.factionHand.getCards().length;

            const amount = count - panache;
            var translated = dojo.string.substitute(
                _("(${amount} card(s) to discard)"),
                {
                    amount: amount
                }
            );
            $('faction_hand_info').innerHTML = translated;
            this.factionHand.setSelectionMode('multiple');

        }
    };

    if( methods[stateName] )
        methods[stateName]()
    
    this.onUpdateActionButtons_7s5s( stateName, args );        
}

})
});
