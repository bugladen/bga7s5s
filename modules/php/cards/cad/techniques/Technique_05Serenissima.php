<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05Serenissima extends Technique_PlusOneParry
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        // Parent wraps clienttranslate("+1 Parry"); overwrite for the prototype test class.
        $this->Name = "Discard a Card; +1 Parry; Adversary Discards at Random";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return false;
        }

        // WHY: Parent PlusOneParry is duel-only. Actor-is-owner is still required (Corncob).
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // WHY: "Discard a card" is left of • — cost gate. Empty hand cannot pay.
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        return count($hand) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            // WHY createTechniqueTransitionEvent: HIGHEST_PRIORITY so the discard cost
            // picker finishes before Calculate applies +1 Parry (02006 / Dabney shape).
            $transition = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "05Serenissima",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state != States::DUEL_CHOOSE_TECHNIQUE_05SERENISSIMA)
        {
            return;
        }

        $owner = $this->getOwningCharacter($game->theah);
        $card = $game->getCardObjectFromDb($id);
        if ($card === null)
        {
            throw new UserException("Card not found.");
        }

        if ($card->ControllerId != $owner->ControllerId)
        {
            throw new UserException("You do not control this card.");
        }

        if ($card->Location != Game::LOCATION_HAND)
        {
            throw new UserException("Card not in your hand.");
        }

        $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
            $card->OwnerId,
            $card->Id,
            $owner->Id,
            $asPayment = false,
            $asPlayed = false,
            $asEffect = true
        );
        $game->theah->queueEvent($discardEvent);

        // WHY: Effect-side random discard — no adversary picker. Empty hand → skip (01093).
        // array_rand on empty warns/fatals — guard first (_01098).
        $adversary = $game->theah->getDuelRoundOpponent();
        if ($adversary !== null)
        {
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            if (count($hand) > 0)
            {
                $picked = $hand[array_rand($hand)];
                $game->notify->all("message", '${owner_inject_code}: ${adversary_inject_code}\'s controller discards ${card_inject_code} at random.', [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "adversary_inject_code" => $adversary->getInjectCode(),
                    "card_inject_code" => $picked->getInjectCode(),
                ]);

                $adversaryDiscard = EventFactory::createCardDiscardedFromHandEvent(
                    $picked->OwnerId,
                    $picked->Id,
                    $owner->Id,
                    $asPayment = false,
                    $asPlayed = false,
                    $asEffect = true
                );
                $game->theah->queueEvent($adversaryDiscard);
            }
        }

        $game->gamestate->nextState();
    }
}
