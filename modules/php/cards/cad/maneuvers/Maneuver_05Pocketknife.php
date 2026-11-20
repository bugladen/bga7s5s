<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_05Pocketknife extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Wound adversary; return a dueling-line card to hand";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY: Gambling Maneuver — mechanical cost of how the combat card was obtained,
        // not a trait. Always gate IN_DUEL and DUEL_GAMBLED (Pattern E).
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            // WHY: Printed order Wound → return-to-hand. Skip wound only when adversary is gone
            // (Henry shape) so the hand-return half can still fire.
            if ($adversary !== null && ! $event->theah->game->characterIsInDiscardOrLocker($adversary))
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $adversary->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $event->theah->eventCheck($woundEvent);
                $event->theah->queueEvent($woundEvent);
            }

            $cards = $this->getDuelingLineCards($event->theah, $owner->ControllerId);
            if (count($cards) > 0)
            {
                // WHY: createTransitionEvent (not TechniqueTransition) — same priority as wound,
                // so FIFO keeps Wound before the picker (03036 shape).
                $transition = EventFactory::createTransitionEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    "05Pocketknife",
                    $this->Id
                );
                $event->theah->queueEvent($transition);
            }
            else
            {
                // WHY: Checklist 9 — empty line must not dead-end (edge case; combat card
                // is normally already on the line when a Maneuver resolves).
                $event->theah->game->notify->all("message", '${maneuver_inject_code}: No cards in the dueling line to return to hand.', [
                    "maneuver_inject_code" => $owner->getInjectCode(),
                ]);
            }
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_05POCKETKNIFE)
        {
            $owner = $this->getOwningCard($game->theah);
            $cards = $owner !== null
                ? $this->getDuelingLineCards($game->theah, $owner->ControllerId)
                : [];
            $args["cards"] = array_map(
                fn($card) => $card->getPropertyArray($game),
                $cards
            );
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state != States::DUEL_RESOLVE_MANEUVER_05POCKETKNIFE)
        {
            return;
        }

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            throw new UserException("Card not found");
        }

        $validIds = array_map(
            fn($card) => $card->Id,
            $this->getDuelingLineCards($game->theah, $owner->ControllerId)
        );

        if (! in_array($id, $validIds))
        {
            throw new UserException("Selected card is not in your dueling line.");
        }

        // WHY: EventCardAddedToHand moves from wherever (dueling line) into hand and
        // sets ControllerId — same path Improvising / Terrell-style returns use.
        $addEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $id);
        $game->theah->queueEvent($addEvent);

        $game->gamestate->nextState();
    }

    /**
     * @return array<int, \Bga\Games\SeventhSeaCityOfFiveSails\cards\Card>
     */
    private function getDuelingLineCards(Theah $theah, int $playerId): array
    {
        return array_values($theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $playerId));
    }
}
