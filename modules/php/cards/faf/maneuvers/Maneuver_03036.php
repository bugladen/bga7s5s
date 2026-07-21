<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03036 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte per other dueling-line card; adversary may discard");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null && $actor->hasTrait('Duelist');
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id == $combatCard->Id)
        {
            $actor = $theah->getDuelRoundActor();
            $adversary = $theah->getDuelRoundOpponent();
            if ($actor !== null && $adversary !== null
                && $actor->ModifiedFinesse > $adversary->ModifiedFinesse)
            {
                $discount += 1;
                $explanations[] = sprintf(
                    $theah->game->translate("%s reduces the cost of Maneuver by 1 because your participant has more Finesse than the adversary."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
            unset($cards[$owner->Id]);
            $count = count($cards);
            if ($count > 0)
            {
                $event->riposte += $count;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: adds %d Riposte (1 for each other card in your dueling line)."),
                    $owner->getInjectCode(),
                    $count
                );
            }
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
            unset($cards[$owner->Id]);
            if (count($cards) < 3)
            {
                return;
            }

            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary === null)
            {
                return;
            }

            // WHY: Skip the discard chooser when the hand is empty — otherwise the
            // adversary would be stuck in an activeplayer state with nothing to pick.
            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            if (count($hand) == 0)
            {
                return;
            }

            $transitionEvent = EventFactory::createTransitionEvent(
                $adversary->ControllerId,
                $owner->Id,
                "03036",
                $this->Id
            );
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03036)
        {
            $card = $game->getCardObjectFromDb($id);

            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $playerId = $game->getActivePlayerId();

            if ($card->ControllerId != $playerId)
            {
                throw new \BgaUserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Card not in your hand"));
            }

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $card->OwnerId,
                $card->Id,
                $owner->Id,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
        }
    }
}
