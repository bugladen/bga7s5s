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
                case "setupTable_01006":
                    // Default action: Pass or take first available option
                    $this->actPass();
                    break;

                case "planningPhaseResolveSchemes_01044":
                case "planningPhaseResolveSchemes_01045":
                case "planningPhaseResolveSchemes_01150":
                case "planningPhaseResolveSchemes_01071":
                case "planningPhaseResolveSchemes_01072":
                case "planningPhaseResolveSchemes_01125_3":
                case "planningPhaseResolveSchemes_01125_4":
                case "planningPhaseResolveSchemes_01144_2":
                    $this->gamestate->nextState("");
                    break;

                // Planning Phase States
                case "planningPhaseResolveSchemes_01016":
                case "planningPhaseResolveSchemes_01016_2":
                case "planningPhaseResolveSchemes_01143":
                    // Default action: Pass or take first available option
                    $this->actPass();
                    break;

                case "planningPhaseResolveSchemes_01125":
                case "planningPhaseResolveSchemes_01125_2":
                case "planningPhaseResolveSchemes_01126":
                case "planningPhaseResolveSchemes_01144":
                case "planningPhaseResolveSchemes_01145":
                case "planningPhaseResolveSchemes_01145_2":
                case "planningPhaseResolveSchemes_01152":
                case "planningPhaseResolveSchemes_01152_2":
                case "planningPhaseResolveSchemes_01152_3":
                    // Default action: Pass or take first available option
                    $this->actPass("pass");
                    break;

                case "planningPhaseResolveSchemes_01126_2":
                    $this->actBack();
                    break;

                case "highDramaBeginning_01144":
                    $this->actPass("pass");
                    break;

                case "highDramaBeginning_01144_2":
                    $this->gamestate->nextState("");
                    break;

                // High Drama Player Turn States
                case "highDramaPlayerTurn":
                    // Default action: Pass turn
                    $this->actHighDramaPass();
                    break;

                case "highDramaChallengeActionAcceptChallenge":
                    // Default action: Reject
                    $this->actHighDramaChallengeActionReject();
                    break;

                case "highDramaChallengeActionChoosePerformer":
                case "highDramaChallengeActionChooseTarget":
                case "highDramaChallengeActionActivateTechnique":
                case "highDramaClaimActionChoosePerformer":
                case "highDramaEquipActionChoosePerformer":
                case "highDramaEquipActionChooseAttachmentLocation":
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
                case "highDramaInPlayActionChoosePerformer":
                case "highDramaInHandActionChooseAction":
                case "highDramaInHandActionChoosePerformer":
                case "highDramaBruteActionChooseBrute":
                case "highDramaBruteActionPayForBrute":
                    // Default action: Go back to main turn
                    $this->actBack();
                    break;

                case "highDramaInHandActionPay":
                    $this->gamestate->nextState("backChooseAction");
                    break;

                // High Drama Player Turn Event States (Card-Specific)
                case "highDramaPhase01011": // Servo Scarpa
                case "highDramaPhase01012": // Sibella Scarpa
                case "highDramaPhase01020": // Dante
                case "highDramaPhase01044": // Armed and Marshaled
                case "highDramaPhase01044_2": // Armed and Marshaled target
                case "highDramaPhase01044_3": // Armed and Marshaled manipulation
                case "highDramaPhase01046a": // Dark Gift location
                case "highDramaPhase01049": // Polished Flintlock
                case "highDramaPhase01055": // Last Word character
                case "highDramaPhase01055_2": // Last Word location
                case "highDramaPhase01056": // Move Along
                case "highDramaPhase01058": // Press the Advantage
                case "highDramaPhase01059": // Regroup
                case "highDramaPhase01060": // Stratege location
                case "highDramaPhase01060_2": // Stratege performers
                case "highDramaPhase01060_3": // Stratege destination
                case "highDramaPhase01068": // Léontine Giroux character
                case "highDramaPhase01068_2": // Léontine Giroux location
                case "highDramaPhase01069": // Maxime De Lafayette character
                case "highDramaPhase01069_2": // Maxime De Lafayette discard
                case "highDramaPhase01072_3": // Réputation Méritée muster
                case "highDramaPhase01076": // Blood Mark location
                case "highDramaPhase01076_2": // Blood Mark character        
                case "highDramaPhase01147": // Let's Haggle
                case "highDramaPhase01148": // Marooned
                case "highDramaPhase01149": // Midnight Shipment
                case "highDramaPhase01152a": // Until Morale Improves
                case "highDramaPhase01152b": // Until Morale Improves
                case "highDramaPhase01156": // Matchlock Musket discard
                case "highDramaPhase01156_2": // Matchlock Musket target
                case "highDramaPhase01156_3": // Matchlock Musket choice
        
                    $this->actBack();
                    break;

                case "highDramaPhase01008_4": // Cesca Del Rosso
                case "highDramaPhase01015": // The Great Game
                case "highDramaPhase01017": // Alcee
                case "highDramaPhase01019": // Buratino
                case "highDramaPhase01024": // Bravos
                case "highDramaPhase01025": // Fate's Burden
                case "highDramaPhase01026": // For the Family
                case "highDramaPhase01028": // Pack Tactics
                case "highDramaPhase01028_2": // Pack Tactics
                case "highDramaPhase01029": // The Pressure Is On
                case "highDramaPhase01030": // Pull the Strand
                case "highDramaPhase01034": // Wrath of the Don
                case "highDramaPhase01034_2": // Wrath of the Don target
                case "highDramaPhase01049_2": // Polished Flintlock engage
                case "highDramaPhase01056_2": // Move Along choice
                case "highDramaPhase01072_2": // Réputation Méritée city card
                case "highDramaPhase01081": // Gallant Deeds
                case "highDramaPhase01085": // Porté Travel
                case "highDramaPhase01086": // Status Matters
                case "highDramaPhase01148_3": // Marooned discard
                case "highDramaPhase01148_4": // Marooned manipulate
                case "highDramaPhase01160": // Bleed Out
                case "highDramaPhase01161": // Boon

                    $this->gamestate->nextState();
                    break;

                case "highDramaPhase01029": // The Pressure Is On
                case "highDramaPhase01035_3": // Kaspar recruit choice
                case "highDramaPhase01035_4": // Kaspar parley choice
                case "highDramaPhase01038_3": // Otto Streit attachment choice
                case "highDramaPhase01180": // Kaj Kousei
                case "highDramaPhase01180_2": // Kaj Kousei
                case "highDramaPhase01180_3": // Kaj Kousei artifact choice
                case "highDramaPhase01180_4": // Kaj Kousei performer
                case "highDramaPhase01180_5": // Kaj Kousei payment
                case "highDramaPhase01185": // Risky Undertaking
                case "highDramaPhase01189a": // Move reknown from
                case "highDramaPhase01189b": // Move reknown to
                case "highDramaPhase01192": // Gustavo
                case "highDramaPhase01192_2": // Gustavo
                case "highDramaPhase01192_3": // Gustavo risk choice
                case "highDramaPhase01194": // Adelheide attachment
                case "highDramaPhase01194_2": // Adelheide character
                case "highDramaPhase01197": // Kalla character from
                case "highDramaPhase01197_2": // Kalla attachment
                case "highDramaPhase01197_3": // Kalla character to
                case "highDramaPhase01200": // Crystal Eye opponent
                case "highDramaPhase01200_2": // Crystal Eye card
                case "highDramaPhase01205": // Kidnap character
                case "highDramaPhase01205_2": // Kidnap location
                    // Default action: Pass or take first available option
                    $this->gamestate->nextState("pass");
                    break;

                // Challenge Action States
                case "highDramaChallengeActionResolveTechnique_01063": // Bastien's Technique
                case "highDramaChallengeActionResolveTechnique_01067": // Jean Urbain's Technique
                    $this->gamestate->nextState("");
                    break;

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
                case "duelChooseTechnique_01013": // Vissenta Scarpa's Technique
                case "duelChooseTechnique_01036": // Daniela's Technique
                case "duelChooseTechnique_01067": // Jean Urbain's Technique
                case "duelChooseTechnique_01063": // Bastien's Technique
                    $this->gamestate->nextState("");
                    break;

                // Duel Resolve Maneuver States
                case "duelResolveManeuver_01051": // Answering the Call
                case "duelResolveManeuver_01059": // Regroup
                case "duelResolveManeuver_01079": // Disarm
                case "duelResolveManeuver_01079_2": // Disarm choice
                case "duelResolveManeuver_01165": // Copy Technique
                    $this->gamestate->nextState("");
                    break;

                // Duel Apply Combat Card Stats States
                case "duelApplyCombatCardStats_01085": // Porté Travel
                    // Default action: Pass or take first available option
                    $this->gamestate->nextState("");
                    break;

                // Dusk Phase States
                case "duskPhaseBegin01177": // Penya choice
                case "duskPhaseBegin01177_2": // Penya card order
                    // Default action: Pass
                    $this->actPass();
                    break;

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
                    // Default action: Pick first available deck
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked');
                    break;

                // Setup Table States
                case "setupTable_01006_2":
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
                    break;

                // Planning Phase
                case "planningPhase":
                    // Default action: Mark as planned (auto-pick first available)
                    $this->actDayPlanned(0, 0);
                    // $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned');
                    break;

                // Acknowledgment States
                case "planningPhaseResolveSchemes_01016_3":
                case "planningPhaseResolveSchemes_01147": // Let's Haggle
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
                    break;

                case "highDramaPhase01008": // Cesca Del Rosso
                case "highDramaPhase01035": // Kaspar
                case "highDramaPhase01038": // Otto Streit
                case "highDramaPhase01180": // Kaj Kousei
                case "highDramaPhase01192": // Gustavo
                    // Default action: Acknowledge
                    $this->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
                    break;

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