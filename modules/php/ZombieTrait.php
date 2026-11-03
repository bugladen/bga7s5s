<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait ZombieTrait
{
    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function doZombieTurn(array $state, int $playerId): void
    {
        $stateName = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($stateName) {
                // setupTable_05Gary / setupTable_01006 live in their State class zombie()
                // planningPhaseResolveSchemes_01044 / 01045 / 01071 / 01072 / 01125 live in their State class zombie()
                // planningPhaseResolveSchemes_01126 / _2 / 01143 / 01144 / _2 / 01145 / _2 / 01150 / 01151 / _2 / 01152 / _2 / _3 live in their State class zombie()
                // highDramaBeginning_01144 / _2 live in their State class zombie()

                // Planning Phase States
                // planningPhaseResolveSchemes_01016 / _2 live in their State class zombie()

                // High Drama Player Turn States
                case "highDramaPlayerTurn":
                    // Default action: Pass turn
                    $this->actHighDramaPass();
                    break;

                case "highDramaChallengeActionAcceptChallenge":
                    // Default: Reject, unless refusal is blocked (unrefusable challenge types, Mōri Daichi).
                    $args = $this->argsHighDramaChallengeActionAcceptChallenge();
                    $challengeType = $args['challengeType'] ?? $this->globals->get(Game::CHALLENGE_TYPE);
                    if (! empty($args['cannotRefuseDueToDaichi'])
                        || $challengeType == Game::EPEE_SANGLANTE_CHALLENGE_TYPE
                        || $challengeType == Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE
                        || $challengeType == Game::STAND_YOUR_GROUND_CHALLENGE_TYPE
                        || $challengeType == Game::RAGE_CHALLENGE_TYPE)
                    {
                        $this->actHighDramaChallengeActionAccept();
                    }
                    else
                    {
                        $this->actHighDramaChallengeActionReject();
                    }
                    break;

                case "highDramaChallengeActionChoosePerformer":
                case "highDramaChallengeActionChooseTarget":
                case "highDramaChallengeActionActivateTechnique":
                case "highDramaClaimActionChoosePerformer":
                case "highDramaEquipActionChoosePerformer":
                case "highDramaEquipActionChooseAttachmentFromHand":
                case "highDramaEquipActionPayForAttachmentFromHand":
                case "highDramaEquipActionChooseAttachmentFromPlay":
                case "highDramaEquipActionPayForAttachmentFromPlay":
                case "highDramaMoveActionChoosePerformer":
                case "highDramaMoveActionChooseLocation":
                case "highDramaRecruitActionChoosePerformer":
                case "highDramaRecruitActionParley":
                case "highDramaRecruitActionChooseMercenary":
                case "highDramaRecruitActionPayForMercenary":
                case "highDramaInPlayActionChooseAction":
                case "highDramaInPlayActionConfirm":
                case "highDramaInHandActionChooseAction":
                case "highDramaInHandActionChoosePerformer":
                case "highDramaBruteActionChooseBrute":
                case "highDramaBruteActionPayForBrute":
                    // Default action: Go back to main turn
                    $this->actBack();
                    break;

                case "highDramaInPlayActionChoosePerformer":
                    // WHY: This state has no actBack transition. Empty list → cancel
                    // escape; otherwise pick the first legal performer.
                    $args = $this->argsHighDramaInPlayActionChoosePerformer();
                    if (empty($args['ids']))
                    {
                        $this->actHighDramaInPlayActionCancelNoPerformer();
                    }
                    else
                    {
                        $this->actHighDramaInPlayActionPerformerChosen((int)$args['ids'][0]);
                    }
                    break;

                case "highDramaEquipActionChooseAttachmentLocation":
                    // WHY: SMUGGLED_ITEM has no human back UI, but zombie must unwind via
                    // backSmuggledItem toward a state where it can pass — not advance deeper.
                    if ($this->globals->get(Game::EQUIP_TYPE) == Game::SMUGGLED_ITEM_EQUIP_TYPE)
                    {
                        $this->actBackWithTransition('backSmuggledItem');
                    }
                    else
                    {
                        $this->actBack();
                    }
                    break;

                case "highDramaInHandActionPay":
                    $this->gamestate->nextState("backChooseAction");
                    break;

                // High Drama Player Turn Event States (Card-Specific)
                // highDramaPhase01011 / 01015 / 01044 / 01028_2 / 01035_4 live in their State class zombie()
                // highDramaPhase01055 / _2 / 01060_2 / _3 / 01068_2 / 01069_2 / 01072_2 / 01076_2 live in their State class zombie()
                // highDramaPhase01147 / 01148 / 01149 / 01152a / 01152b live in their State class zombie()

                // highDramaPhase01012 / 01017 / 01019 / 01020 / 01046a / 01049 / 01068 / 01069 / 01076 / 01086 live in their State class zombie()
                // highDramaPhase01156 / 01185 / 01189a / 01189b live in their State class zombie()

                // highDramaPhase01024–01030 / 01034 / 01049_2 / 01056 / _2 / 01058 / 01059 / 01060 / 01072 / 01081 / 01085 live in their State class zombie()
                // highDramaPhase01148_3 / _4 / 01156_2 / _3 / 01160 / 01161 live in their State class zombie()

                // highDramaPhase01035_3 / 01035v2_3 / 01038_3 live in their State class zombie()
                // highDramaPhase01180 / _2 / _4 / _5 / 01192 / _2 / _3 / 01194 / _2 / 01197 / _2 / _3 live in their State class zombie()
                // highDramaPhase01200 / _2 / 01205 / _2 live in their State class zombie()
                // planningPhaseResolveSchemes_01200 / _2 / planningPhaseApproach01200 / _2 live in their State class zombie()
                // duelResolveManeuver_01200 / _2 / duelEndOfRound_01200 / _2 live in their State class zombie()

                // highDramaPhase01035v2_4 lives in State_highDramaPhase01035v2_4::zombie

                // Challenge Action States
                // highDramaChallengeActionResolveTechnique_01063 / 01067 live in their State class zombie()

                // Duel States
                case "duelChooseAction":
                case "duelChooseTechnique":
                case "duelUseManeuverFromCombatCard":
                case "duelPayForManeuverFromCombatCard":
                case "duelChooseGambleCard":
                    // Default action: End round or pass
                    $this->actDuelDoneRound();
                    break;

                // Duel Choose Technique States
                // duelChooseTechnique_01013 / 01036 / 01063 / 01067 live in their State class zombie()
                // duelEndOfRound_01031 lives in State_duelEndOfRound_01031::zombie

                // Duel Resolve Maneuver States
                // duelResolveManeuver_01051 / 01059 / 01079 / _2 / 01164 / 01165 / 01200 / _2 live in their State class zombie()

                // Duel Apply Combat Card Stats States
                // duelApplyCombatCardStats_01085 lives in State_duelApplyCombatCardStats_01085::zombie

                // Duel End Of Round States
                // duelEndOfRound_01200 / _2 live in their State class zombie()

                // Dusk Phase States
                // duskPhaseBegin01177 / _2 live in their State class zombie()

                // Generic Reaction States
                case "playerReaction":
                    // Default action: Done with reaction
                    $this->gamestate->nextState("done");
                    break;

                case "playerPayForReaction":
                    // Default action: Go back
                    $this->actBack();
                    break;

                default:
                    throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
            }
            return;
        }

        if ($state["type"] === "multipleactiveplayer") {
            switch ($stateName) {
                // Deck Picking
                case "pickDecks":
                    // WHY: Releasing the zombie without writing deck_source lets the
                    // last pick transition into buildTable → buildDecks, which then
                    // fatals on $deck->faction (null json_decode). Must assign a deck.
                    // Cannot call actPickDeck — it uses getCurrentPlayerId(), which
                    // fails on the zombie/cron path ("Not logged").
                    // Random Core matches State_DeckAssignment's random-core path and
                    // the Core-only pool shown in argAvailableDecks.
                    $starter_decks = json_decode(StarterDecks::$decksJson);
                    $core_decks = array_values(array_filter(
                        $starter_decks->decks,
                        fn($deck) => ($deck->set ?? null) === "Core"
                    ));
                    $chosen_deck = $core_decks[array_rand($core_decks)];
                    $deck_json = addslashes(json_encode($chosen_deck));
                    $sql = "UPDATE player SET deck_source = '$deck_json' WHERE player_id='$playerId'";
                    $this->DbQuery($sql);

                    if (Game::TOURNAMENT_LOCK_DECKS && $this->bga->tournament->isTournament())
                    {
                        $this->bga->tournament->storePlayerGameData($playerId, 'deck_source', $chosen_deck);
                    }

                    $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked');
                    break;

                // Setup Table States
                // setupTable_05Gary_2 / setupTable_01006_2 live in their State class zombie()
                case "duelChooseTechnique_03043":
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
                    break;

                // Planning Phase
                case "planningPhase":
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned');
                    break;

                // Acknowledgment States
                // planningPhaseResolveSchemes_01016_3 / 01147 live in their State class zombie()

                // highDramaPhase01008 / 01035 / 01035v2 / 01038 / 01180 / 01192 live in their State class zombie()

                // Dusk Phase Discard
                case "duskPhaseDiscard":
                    // Default action: Auto-discard to panache limit
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'cardsDiscarded');
                    break;

                default:
                    throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
            }
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
    }
}