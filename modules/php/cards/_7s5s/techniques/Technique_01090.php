<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01090 extends Technique
{
    private int $RevealedCardId = 0;
    private int $CardPlayerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Reveal and Replace the Top Card of Adversary's Faction Deck");
        $this->RevealedCardId = 0;
        $this->CardPlayerId = 0;
    }

    /**
     * WHY: Challenge has no duel round — getDuelRoundOpponent() fatals on a null actor.
     * Adversary is CHOSEN_TARGET via stHighDramaChallengeActionResolveTechnique /
     * GenerateThreat (Intervene can retarget). Same rule as Technique_01193 / 02026.
     */
    private function getAdversary(Theah $theah, int $adversaryId = 0): ?Character
    {
        if ($adversaryId)
        {
            return $theah->getCharacterById($adversaryId);
        }

        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            return $theah->getDuelRoundOpponent();
        }

        $chosenId = (int) $theah->game->globals->get(Game::CHOSEN_TARGET, 0);
        if (! $chosenId)
        {
            return null;
        }

        return $theah->getCharacterById($chosenId);
    }

    private function getActor(Theah $theah, int $actorId = 0): ?Character
    {
        if ($actorId)
        {
            return $theah->getCharacterById($actorId);
        }

        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            return $theah->getDuelRoundActor();
        }

        $performerId = (int) $theah->game->globals->get(Game::CHOSEN_PERFORMER, 0);
        if (! $performerId)
        {
            return null;
        }

        return $theah->getCharacterById($performerId);
    }

    private function revealAdversaryTopCard(Theah $theah, Character $actor, Character $adversary): void
    {
        $game = $theah->game;
        $owner = $this->getOwningCharacter($theah);

        $playerName = $game->getPlayerNameById($actor->ControllerId);
        $opponentName = $game->getPlayerNameById($adversary->ControllerId);

        $dbCardInfo = $game->getCardsOnTopOfPlayerFactionDeck($adversary->ControllerId, 1)[0];
        $card = $game->getCardObjectFromDb($dbCardInfo['id']);

        $this->RevealedCardId = $card->Id;
        $this->CardPlayerId = $adversary->ControllerId;
        $owner->IsUpdated = true;

        $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} reveals and replaces the top card of ${opponent_name}\'s Faction Deck. Card revealed: ${card_inject_code}.'), [
            "owner_inject_code" => $owner->getInjectCode(),
            "player_name" => $playerName,
            "opponent_name" => $opponentName,
            "card_inject_code" => $card->getInjectCode(),
        ]);

        $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "01090", $this->Id);
        $theah->queueEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            // WHY: Challenge Resolve runs before Accept/Refuse. Reveal must not fire on
            // Refuse — defer to EventGenerateChallengeThreat + CHALLENGE_ACCEPTED
            // (02026 / 04017 shape). In-duel Resolve is the real effect timing.
            if ($event->inDuel)
            {
                $actor = $this->getActor($event->theah, $event->actorId);
                $adversary = $this->getAdversary($event->theah, $event->adversaryId);
                if ($actor !== null && $adversary !== null)
                {
                    $this->revealAdversaryTopCard($event->theah, $actor, $adversary);
                }
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            // WHY: GENERATE_THREAT also runs on Refuse (wound threat). Intervene sets
            // CHALLENGE_ACCEPTED without EventChallengeAccepted. Gate so Refuse never
            // reveals; Accept/Intervene get the reveal from GENERATE_THREAT_EVENTS.
            if ($event->theah->game->globals->get(Game::CHALLENGE_ACCEPTED, false))
            {
                $actor = $this->getActor($event->theah, $event->actorId);
                $adversary = $this->getAdversary($event->theah, $event->adversaryId);
                if ($actor !== null && $adversary !== null)
                {
                    $this->revealAdversaryTopCard($event->theah, $actor, $adversary);
                }
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->RevealedCardId = 0;
            $this->CardPlayerId = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->RevealedCardId = 0;
            $this->CardPlayerId = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelNewRound && $this->CardPlayerId > 0)
        {
            $adversary = $event->theah->getDuelRoundActor();
            if ($this->CardPlayerId == $adversary->ControllerId)
            {
                $owner = $this->getOwningCharacter($event->theah);
                $transition = EventFactory::createTechniqueTransitionEvent($adversary->ControllerId, $owner->Id, "01090", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01090
            || $state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01090
            || $state == States::DUEL_NEW_ROUND_01090)
        {
            // WHY: CardPlayerId is the deck owner captured at reveal. Do not use
            // getDuelRoundOpponent() — on NewRound the actor IS the adversary, and on
            // Challenge there is no duel opponent yet.
            $args['opponentName'] = $game->getPlayerNameById($this->CardPlayerId);

            $card = $game->getCardObjectFromDb($this->RevealedCardId);
            $args['card'] = $card->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_NEW_ROUND_01090)
        {
            //Take a wound
            if ($id == 0)
            {
                $owner = $this->getOwningCharacter($game->theah);
                $actor = $game->theah->getDuelRoundActor();
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($actor->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }
            else
            {
                $actor = $game->theah->getDuelRoundActor();
                $deck = $game->getGameDeckObject();
                $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $actor->ControllerId);
                $hand = array_filter($hand, fn($card) => $card['id'] == $id);
                if (count($hand) == 0)
                {
                    throw new \BgaUserException($game->translate("Card is not in your hand."));
                }

                $owner = $this->getOwningCard($game->theah);
                $card = $game->getCardObjectFromDb($id);
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->ControllerId, $card->Id, $owner->Id, false, false, true);
                $game->theah->queueEvent($discardEvent);

                $card = $game->getCardObjectFromDb($this->RevealedCardId);

                // Move from faction deck into hand, then on to the dueling line
                // (mirrors the normal combat-card flow in actChooseCombatCard).
                // Done with direct DB ops so we can chain both moves synchronously
                // — using EventCardAddedToHand here would fire after the dueling-line
                // move and put the card back into the hand.
                $game->moveCard($card->Id, Game::LOCATION_HAND, $actor->ControllerId, $card);
                $game->theah->addCardToWorld($card);

                $game->notify->all("cardAddedToHand", clienttranslate('${player_name} added ${card_inject_code} to their Faction Hand.'), [
                    "player_id" => $actor->ControllerId,
                    "player_name" => $game->getPlayerNameById($actor->ControllerId),
                    "card_inject_code" => $card->getInjectCode(),
                    "card" => $card->getPropertyArray($game),
                    "handCount" => count($deck->getPlayerHand($actor->ControllerId)),
                ]);

                $game->globals->set(Game::CHOSEN_CARD, $card->Id);

                $event = EventFactory::createCombatCardAnnouncedEvent($actor->ControllerId, $owner->Id);
                $game->theah->queueEvent($event);

                $game->moveCard($card->Id, Game::LOCATION_DUELING_LINE, $actor->ControllerId, $card);

                $transition = EventFactory::createTransitionEvent($actor->ControllerId, $owner->Id, "01090_2", $this->Id);
                $game->theah->queueEvent($transition);
            }

            // WHY: Card text is "when their next round begins" (once). CardPlayerId
            // gates EventDuelNewRound — leave it set and the prompt re-fires every
            // adversary round for the rest of the duel.
            $this->RevealedCardId = 0;
            $this->CardPlayerId = 0;
            $owner = $this->getOwningCharacter($game->theah);
            $owner->IsUpdated = true;

            $game->gamestate->nextState();
        }
    }
}
