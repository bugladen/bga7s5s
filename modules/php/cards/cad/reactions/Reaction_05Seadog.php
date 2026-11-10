<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Seadog extends CardReaction
{
    private ?EventCardMoving $cardMovingEvent = null;
    private string $affectedCharacterName = '';

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Cancel movement from this location";
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->cardMovingEvent === null)
        {
            return $base;
        }

        // WHY: Prototype CAD — plain sprintf, no translate wrappers.
        return $base . sprintf(
            '${you} may cancel the movement of %s to %s: ',
            $this->affectedCharacterName,
            $this->cardMovingEvent->toLocation
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, 'Cancel Movement', 'cancel');
        $array[] = $this->createButtonProperty($theah->game, 'Decline', 'decline');
        return $array;
    }

    private function locationIsUncontrolled(Theah $theah, string $location): bool
    {
        $cityLocation = $theah->getCityLocation($location);
        return $cityLocation !== null && ! $cityLocation->isControlled();
    }

    private function interceptMoving(EventCardMoving $event, Character $owner, Character $character): void
    {
        $this->cardMovingEvent = clone $event;
        unset($this->cardMovingEvent->theah);
        $this->affectedCharacterName = $character->Name;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoving) || $event->canceled || $event->unstoppable || ! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $owner->isControlled())
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        // WHY: Covert Reaction — Covert abilities only at uncontrolled City locations (Valeri / Overcoat).
        if (! $event->theah->cardInCity($owner) || ! $this->locationIsUncontrolled($event->theah, $owner->Location))
        {
            return;
        }

        if (in_array($owner->Id, $event->cancelDeclinedByCardIds, true))
        {
            return;
        }

        // Character must currently be at Mikael's location (fromLocation still live;
        // EventCardMoving.runEventHubAfterCards defers the location write).
        if ($event->fromLocation != $owner->Location)
        {
            return;
        }

        $character = $event->theah->getCardById($event->cardId);
        if (! ($character instanceof Character))
        {
            return;
        }

        // WHY: Text is "a character" with no "your" / "opponent's effect" — any Character
        // leaving this location is legal, including framework sourceId==0 dusk moves.
        $this->interceptMoving($event, $owner, $character);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'cancel')
        {
            $game->notify->all("message", '${reaction_inject_code}: ${player_name} cancelled the movement of ${character_name}.', [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_name" => $this->affectedCharacterName,
            ]);

            $this->setUsed($game->theah, true);
            $this->cardMovingEvent = null;
            $this->affectedCharacterName = '';
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        if ($reactionId == 'decline')
        {
            // WHY: Moving has cancelDeclinedByCardIds (Stubborn / Ise / Jak-Sen). Re-queue
            // with owner id so handleEvent does not immediately re-catch the same move.
            if ($this->cardMovingEvent !== null)
            {
                $this->cardMovingEvent->cancelDeclinedByCardIds[] = $owner->Id;
                $game->theah->queueEvent($this->cardMovingEvent);
            }

            $this->cardMovingEvent = null;
            $this->affectedCharacterName = '';
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        $game->gamestate->nextState("done");
    }
}
