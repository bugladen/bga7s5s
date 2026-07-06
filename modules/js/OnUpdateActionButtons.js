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
            this.addActionButton(`actConfirmDeck`, _('Confirm Deck'), () => this.deckPickerDeckSelected());
            dojo.addClass('actConfirmDeck', 'disabled');

            if ( ! document.getElementById('deck-picker'))
            {
                dojo.addClass('city', 'hidden');
                dojo.addClass('approachDeck-container', 'hidden');
                dojo.addClass('factionHand-placeholder', 'hidden');
                dojo.place( this.format_block( 'jstpl_deck_picker', {
                    banner_description: _('Explore Starter Decks using the buttons below, or...<br>Select the <strong>Custom Deck Code</strong> button to paste a custom deck code.<p>When ready, click <strong>Confirm Deck</strong> to confirm selected Deck.</p>'),
                    castille_description_core: _('<strong>Castille</strong>: Soline el Gato grew up on the streets and canals of the Castillian District of Five Sails and knows intimately what it takes to survive in a city such as Five Sails. The leader of a den of thieves and scoundrels, Soline uses her cunning and adaptability to always keep her opponents on their toes, not knowing what to expect. Soline’s style is one of disruption, making it increasingly difficult for an opponent to gain ground.<p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/b3e77c40-12d3-47e7-a559-3e72f87762f4">Core Castille Starter</a></p>'),
                    eisen_description_core: _('<strong>Eisen</strong>: An accomplished General in the War of the Cross, Kaspar Dietrich returned home to Eisen, only to find it in ruins, overrun by monsters. As such, he has a passionate distrust for all things sorcery and supernatural. Kaspar fled south to the port city of Five Sails where he hopes to use his formidable reputation as a master commander and strategist to build an army to reclaim his homeland. He utilizes strategies that involve making use of the city and the mercenaries and equipment available to him.<p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/7c4bcda2-b788-48f9-bae1-5c55ceacd514">Core Eisen Starter</a></p>'),
                    montaigne_description_core: _('<strong>Montaigne</strong>: Odette Dubois d’Arrent is the most recent arrival to the city. She is a courtier from Montaigne, a country that does not have a district or established foothold in Five Sails. She is tasked to help her patron expand his influence within the free city.  As such, she is eager to find allies. But she did not arrive in Five Sails alone. A small, but elite, group of skilled Musketeers accompanies her and protects her from the rougher elements of the City. Her strengths include movement and creative positioning to make the most of her political abilities and her Musketeer’s steel. <p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/cee927a0-4ec3-4868-a7cf-cf38be98ab1e">Core Montaigne Starter</a></p>'),
                    ussura_description_core: _('<strong>Ussura</strong>: Yevgeni the Boar is a man so large and foreboding that even the elements seem to bow down before him. He has become something of a folk hero in the eyes of the Ussuran district and his reputation as such extends well beyond the cast of his shadow. He is not native to Five Sails, but has no memory of his life prior to his arrival. Because of this, he searches for the answers of his past. Yevgeni’s style is much like the man himself; bold, direct, and powerful. He prefers to get the job done himself rather than send anyone else to do the work.<p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/79d1ded0-9ae7-4aa4-bf23-bd375ddbe4a5">Core Ussura Starter</a></p>'),
                    vodacce_description_core: _('<strong>Vodacce</strong>: “Don” Constanzo Scarpa loves his city, for Five Sails is indeed his city, and he is willing to do whatever it takes to protect it, even if it is from itself. Reputation, family and loyalty are the things that are of paramount importance to Constanzo as he tries to advance politically through the ranks of the city’s elite. Constanzo’s style is cutthroat and brutal where the ends always justify the means. He cares not for what or even who gets sacrificed along the way as long as it advances his goals. <p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/eb30bae2-8c70-4f9a-917b-7ce3309a146f">Core Vodacce Starter</a></p>'),
                    castille_description_tac: _('<strong>Castille</strong>: Soline el Gato grew up on the streets and canals of the Castillian District of Five Sails and knows intimately what it takes to survive in a city such as Five Sails. The leader of a den of thieves and scoundrels, Soline uses her cunning and adaptability to always keep her opponents on their toes, not knowing what to expect. Soline’s style is one of disruption, making it increasingly difficult for an opponent to gain ground.<p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/7b3c8773-353e-464d-8f3c-073a56a3e745">Tooth & Claw Castille Starter</a></p>'),
                    eisen_description_tac: _('<strong>Eisen</strong>: An accomplished General in the War of the Cross, Kaspar Dietrich returned home to Eisen, only to find it in ruins, overrun by monsters. As such, he has a passionate distrust for all things sorcery and supernatural. Kaspar fled south to the port city of Five Sails where he hopes to use his formidable reputation as a master commander and strategist to build an army to reclaim his homeland. He utilizes strategies that involve making use of the city and the mercenaries and equipment available to him.<p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/9212d0d4-6496-477b-89a2-92e968127eb7">Tooth & Claw Eisen Starter</a></p>'),
                    montaigne_description_tac: _('<strong>Montaigne</strong>: Odette Dubois d’Arrent is the most recent arrival to the city. She is a courtier from Montaigne, a country that does not have a district or established foothold in Five Sails. She is tasked to help her patron expand his influence within the free city.  As such, she is eager to find allies. But she did not arrive in Five Sails alone. A small, but elite, group of skilled Musketeers accompanies her and protects her from the rougher elements of the City. Her strengths include movement and creative positioning to make the most of her political abilities and her Musketeer’s steel. <p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/5b475dee-9a1e-4537-8852-0e45873b2458">Tooth & Claw Montaigne Starter</a></p>'),
                    ussura_description_tac: _('<strong>Ussura</strong>: Yevgeni the Boar is a man so large and foreboding that even the elements seem to bow down before him. He has become something of a folk hero in the eyes of the Ussuran district and his reputation as such extends well beyond the cast of his shadow. He is not native to Five Sails, but has no memory of his life prior to his arrival. Because of this, he searches for the answers of his past. Yevgeni’s style is much like the man himself; bold, direct, and powerful. He prefers to get the job done himself rather than send anyone else to do the work.<p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/ea397cf9-957e-47c5-bb8d-03b252b0785a">Tooth & Claw Ussura Starter</a></p>'),
                    vodacce_description_tac: _('<strong>Vodacce</strong>: “Don” Constanzo Scarpa loves his city, for Five Sails is indeed his city, and he is willing to do whatever it takes to protect it, even if it is from itself. Reputation, family and loyalty are the things that are of paramount importance to Constanzo as he tries to advance politically through the ranks of the city’s elite. Constanzo’s style is cutthroat and brutal where the ends always justify the means. He cares not for what or even who gets sacrificed along the way as long as it advances his goals. <p>Deck: <a target="_blank" href="https://sailsdb.onrender.com/decks/9a5981c2-76b4-410f-8ef0-eab1c7c12862">Tooth & Claw Vodacce Starter</a></p>'),
                    custom_description: _('Paste your deck JSON above.  Use <a href="https://sailsdb.onrender.com" target="_blank">SailsDB</a> to generate the JSON code.'),
                    select_description: _('Confirm Deck'),
                }),  'city-wrapper', 'after');
            }
        }
        else if (! this.isSpectator)
        {
            dojo.destroy('deck-picker');
            dojo.removeClass('city', 'hidden');
            dojo.removeClass('approachDeck-container', 'hidden');
            // factionHand-placeholder will be shown by notification when cards are drawn
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
                this.addActionButton(`actRecruitAction`, _('Recruit'), () => this.basicRecruitActionCrewCapCheck());
            if (args._private.hasInPlayActions)
            {
                this.addActionButton(`btnInPlayAction`, _('In-Play Action'), () => this.bgaPerformAction('actHighDramaChooseInPlayActionStart', {})) 
                this.addTippyTooltip( 'btnInPlayAction', `<div class='_7sfs-basic-tooltip'>${_("Use an In-Play Action")}</div>` );
            }
            if (args._private.hasInHandActions)
            {
                this.addActionButton(`btnInHandAction`, _('In-Hand Action'), () => this.bgaPerformAction('actHighDramaChooseInHandActionStart', {})) 
                this.addTippyTooltip( 'btnInHandAction', `<div class='_7sfs-basic-tooltip'>${_("Use an In-Hand Action")}</div>` );
            }
            if (args._private.hasBrutes)
                this.addActionButton(`btnBrute`, _('Play Brute'), () => this.bgaPerformAction('actHighDramaChooseBruteStart', {})) 
                        
            if (! args._private.mustPerformAction)
                this.statusBar.addActionButton(_('Pass'), () => this.onConfirmPass(), { id: 'actPass', color: 'alert' });
        },

        'highDramaMoveActionChoosePerformer': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaMoveActionChooseLocation': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actCityLocationsSelected`, _('Confirm'), () => this.onCityLocationsSelected());
            dojo.addClass('actCityLocationsSelected', 'disabled');
        },

        'highDramaRecruitActionChoosePerformer': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaRecruitActionParley': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseYes`, _('Yes'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyYes', {}));
            this.addActionButton(`actChooseNo`, _('No'), () => this.bgaPerformAction('actHighDramaRecruitActionParleyNo', {}));
        },

        'highDramaRecruitActionChooseMercenary': () => {
            if (args.recruitType == this.NORMAL_RECRUIT_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaRecruitActionPayForMercenary': () => {
            if (args.recruitType == this.NORMAL_RECRUIT_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            else if (args.recruitType == this.KASPAR_RECRUIT_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backKaspar'}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onRecruitCharacterConfirmed());
        },

        'highDramaInPlayActionChooseAction'  : () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            args._private.actions.forEach((action, index) => {
                this.addActionButton(
                    `btnChooseAction_${action.id}`, action.name, () => this.bgaPerformAction('actHighDramaInPlayActionChosen', { actionId: action.id}))
            });
    },

        'highDramaInPlayActionConfirm'  : () => {
            this.addActionButton(`actConfirm`, _('Confirm'), () => this.bgaPerformAction('actHighDramaInPlayActionConfirm', {}));
            this.statusBar.addActionButton('Cancel', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
        },

        'highDramaInPlayActionChoosePerformer'  : () => {
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaInHandActionChooseAction'  : () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            args._private.actions.forEach((action) => { 
                this.addActionButton(
                    `btnChooseAction_${action.id}`, action.name, () => this.bgaPerformAction('actHighDramaInHandActionChosen', { actionId: action.id})) 
            });
        },

        'highDramaInHandActionChoosePerformer'  : () => {
            if (! args._private.abnormalFlow)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaInHandActionPay': () => {
            if (! args._private.abnormalFlow)
            {
                if (args._private.requiresPerformerSelected)
                    this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backPerformer'}), { id: 'actBack', color: 'alert' });
                else
                    this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backChooseAction'}), { id: 'actBack', color: 'alert' });
            }
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onActionCardFromHandPaymentConfirmed());
        },

        'highDramaBruteActionChooseBrute'  : () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandCardConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaBruteActionPayForBrute': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onPaymentConfirmed());
        },

        'highDramaEquipActionChoosePerformer': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaEquipActionChooseAttachmentLocation': () => {
            if (args._private.equipType === this.SMUGGLED_ITEM_EQUIP_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backSmuggledItem'}), { id: 'actBack', color: 'alert' });
            else if (args._private.equipType === this.NORMAL_EQUIP_TYPE) 
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            if (args._private.attachmentsInHand.length > 0) {
                this.addActionButton(`actChooseFromHand`, _('Equip from Hand'), () => this.bgaPerformAction('actSimpleTransition', {transition: 'equipFromHand'}));
            }
            if (args._private.attachmentsInPlay.length > 0) {
                this.addActionButton(`actChooseFromPlay`, _('Equip from Play'), () => this.bgaPerformAction('actSimpleTransition', {transition: 'equipFromPlay'}));
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onChooseHandCardConfirmed());
            dojo.addClass('actFactionCardsSelected', 'disabled');
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaEquipActionPayForAttachmentFromHand': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onPaymentConfirmed());
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            if (args._private.equipType === this.LETS_HAGGLE_EQUIP_TYPE) 
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backLetsHaggle'}), { id: 'actBack', color: 'alert' });
            else
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actFactionCardsSelected`, _('Confirm'), () => this.onPaymentConfirmed());
        },

        'highDramaClaimActionChoosePerformer': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaChallengeActionChoosePerformer': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaChallengeActionChooseTarget': () => {
            if (args.challengeType == this.NORMAL_CHALLENGE_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            else if (args.challengeType == this.TRISKELION_CHALLENGE_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backTriskelion'}), { id: 'actBack', color: 'alert' });
            else if (args.challengeType == this.EPEE_SANGLANTE_CHALLENGE_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backEpeeSanglante'}), { id: 'actBack', color: 'alert' });
            else if (args.challengeType == this.CAVALIER_HAT_CHALLENGE_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backCavalierHat'}), { id: 'actBack', color: 'alert' });

            this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'highDramaChallengeActionActivateTechnique': () => {
            if (args.challengeType != this.SERVO_SCARPA_CHALLENGE_TYPE && 
                args.challengeType != this.ANDRIANA_DONDOLOS_CHALLENGE_TYPE &&
                args.challengeType != this.WILHELM_DUNST_CHALLENGE_TYPE)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });

            args.techniques.forEach((technique) => { 
                this.addActionButton(
                    `actChooseTechnique${technique.id}-btn`, technique.name, () => this.bgaPerformAction('actHighDramaChallengeActionTechniqueActivated', { techniqueId: technique.id})) 
            });
            this.statusBar.addActionButton(_('Pass'), () => this.onPass(), { id: 'actPass', color: 'alert' });
        },
        
        'highDramaChallengeActionAcceptChallenge': () => {
            this.addActionButton(`btnAccept`, _('Accept'), () => this.bgaPerformAction('actHighDramaChallengeActionAccept', {})) 
            this.addActionButton(`btnRefuse`, _('Refuse'), () => this.bgaPerformAction('actHighDramaChallengeActionReject', {})) 
            this.addActionButton(`actChooseCardSelected`, _('Intervene'), () => this.onChooseInPlayCardConfirmed());
            if (args.challengeType == this.EPEE_SANGLANTE_CHALLENGE_TYPE || args.challengeType == this.UNSANCTIONED_DUEL_CHALLENGE_TYPE)
                dojo.addClass('btnRefuse', 'disabled');
            if (args.challengeType == this.AJA_CHALLENGE_TYPE && args.defenderFinesse < 3)
                dojo.addClass('btnRefuse', 'disabled');
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'playerReaction': () => {
            args._private.args.buttons.forEach((button, index) => {
                const buttonId = `actReaction-${index}`;
                if (button.text.includes('Pass') || button.text.includes('Decline')) {
                    this.statusBar.addActionButton(button.text, () => this.bgaPerformAction('actReactionForState', {reactionId: button.reaction}), { id: buttonId, color: 'alert' });
                } else {
                    this.addActionButton(buttonId, button.text, () => this.bgaPerformAction('actReactionForState', {reactionId: button.reaction}));
                }
                if (button.disabled) {
                    dojo.addClass(buttonId, 'disabled');
                }
                if (button.card) {
                    if (this.getGameUserPreference(this.USER_PREFERENCES_CARD_HOVER_TYPE) == 2) {
                        this.createTextTooltipForRisk(button.card, buttonId);
                    } else {
                        this.addTippyTooltip(buttonId, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(button.card.image) + button.card.image}" />`);
                    }
                }
            });
        },

        'playerPayForReaction': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
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
                this.addTippyTooltip( 'btnTechnique', `<div class='_7sfs-basic-tooltip'>${_("Add Technique from Character or Attachment")}</div>` );
            }
            if (args._private.combatCardAvailable)
            {
                this.addActionButton(`btnCombatCard`, _('Combat Card'), () => this.onDuelChooseCombatCardConfirmed());
                dojo.addClass('btnCombatCard', 'disabled');
                this.addTippyTooltip( 'btnCombatCard', `<div class='_7sfs-basic-tooltip'>${_("Play Combat card. Choose Maneuvers on card.")}</div>` );
            }
            if ( ! args._private.endDuelAvailable)
                this.addActionButton(`btnDone`, _('End Round'), () => this.bgaPerformAction('actDuelDoneRound', {})) 
            if (args._private.endDuelAvailable)
                this.addActionButton(`btnEndDuel`, _('End Duel'), () => this.bgaPerformAction('actDuelEndDuel', {})) 
        },

        'duelChooseTechnique': () => {
            this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            args.techniques.forEach((technique) => { 
                this.addActionButton(
                    `btnChooseTechnique_${technique.id}`, technique.name, () => this.bgaPerformAction('actDuelTechniqueChosen', { techniqueId: technique.id})) 
            });
        },

        'duelUseManeuverFromCombatCard': () => {
            if (! args._private.gambled && ! args._private.abnormalFlow)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            args._private.maneuvers.forEach((maneuver) => { 
                this.addActionButton(
                    `btnChooseManeuver_${maneuver.id}`, maneuver.name, () => this.bgaPerformAction('actDuelUseManeuverFromCombatCard', { maneuverId: maneuver.id})) 
            });
            this.addActionButton(`btnDecline`, _('Decline'), () => this.bgaPerformAction('actDuelUseManeuverFromCombatCardDeclined', {}));
        },

        'duelPayForManeuverFromCombatCard': () => {
            if (args._private.gambled || args._private.abnormalFlow)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBackWithTransition', { transition: 'backAbnormalFlow'}), { id: 'actBack', color: 'alert' });
            else
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actPayForCards`, _('Confirm'), () => this.onCombatCardPaymentConfirmed());
        },

        'duelChooseGambleCard': () => {
            if (args._private.cards.length == 0)
                this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
            this.addActionButton(`actChooseCardSelected`, _('Confirm Selection'), () => this.onChooseListCardConfirmed());
            dojo.addClass('actChooseCardSelected', 'disabled');
        },

        'planningPhaseResolveWhenRevealedCardsChooseOrder': () => {
            args.whenRevealedCards.forEach((card) => {
                this.addActionButton(
                    `actChooseWhenRevealedCard_${card.cardId}`,
                    `${card.playerName}: ${card.cardName}`,
                    () => this.bgaPerformAction('actChooseWhenRevealedCard', { cardId: card.cardId })
                );
            });
        },

        'duskPhaseDiscard': () => {
            this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardsChosenForDiscard());
            dojo.addClass('actChooseDiscardCards', 'disabled');

            //Code here instead of onEnteringState because multiactive client states are not ready at that point
            const player = this.gamedatas.players[this.player_id];
            const leader = player.leader;
            const panache = leader.panache;
            const count = this.factionHand.getCards().length;
            const expectedDiscardCount = count - panache;

            var translated = dojo.string.substitute(
                _("(${amount} card(s) to discard)"),
                {
                    amount: expectedDiscardCount
                }
            );

            const statusBarTitle = _('${you} have discarded ${discarded}/${count} card(s) down to your unmodified Leader Panache value of ${panache}:');
            this.bga.statusBar.setTitle(statusBarTitle, {
                discarded: this.factionHand.getSelection().length,
                count: expectedDiscardCount,
                panache: panache,
            });

            $('faction_hand_info').innerHTML = translated;
            this.factionHand.setSelectionMode('multiple');

        }
    };

    if( methods[stateName] )
        methods[stateName]()
    
    this.onUpdateActionButtons_7s5s( stateName, args );        
    this.onUpdateActionButtons_tac( stateName, args );
    this.onUpdateActionButtons_faf( stateName, args );
}

})
});
