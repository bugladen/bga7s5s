<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Gary extends CardReaction
{
    // WHY: Cancel-first like Altruistic / Cross of the Martyrs — clone the wound,
    // offer redirect to Rena / Pass. Pass re-queues the clone; skipNextEvent prevents
    // re-offering. Destination is always Rena (printed "Wound Rena instead"), not a picker.
    private ?EventCharacterBeingWounded $savedWoundEvent = null;
    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Wound Rena Instead";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound Rena instead: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, "Wound Rena", 'woundRena');
        $array[] = $this->createButtonProperty($theah->game, "Pass", 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCharacterBeingWounded) || $event->canceled)
        {
            return;
        }

        // WHY: Kaspar (_03014) / Breastplate (_01153) zero or reduce wounds in eventCheck
        // before handleEvent. "Would suffer a wound" is false when wounds are already 0 —
        // intercepting would cancel a no-op and re-queue a 0-wound clone (Buratino→Kaspar
        // swallowed the effect with no wound on Rena).
        if ($event->wounds <= 0)
        {
            return;
        }

        if (! $this->isAvailable())
        {
            return;
        }

        if ($this->savedWoundEvent !== null)
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        if ($this->skipNextEvent)
        {
            $this->skipNextEvent = false;
            $owner->IsUpdated = true;
            return;
        }

        // WHY: Redirecting a wound already on Rena is a no-op; skip to avoid loops.
        if ($event->characterId == $owner->Id)
        {
            return;
        }

        $victim = $event->theah->getCharacterById($event->characterId);
        if (! ($victim instanceof Character))
        {
            return;
        }

        // WHY: Printed "a character at this location" — same city/home site as Rena.
        if ($victim->Location != $owner->Location)
        {
            return;
        }

        $this->savedWoundEvent = clone $event;
        unset($this->savedWoundEvent->theah);
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'woundRena')
        {
            if ($this->savedWoundEvent !== null)
            {
                $this->savedWoundEvent->characterId = $owner->Id;
                $game->theah->queueEvent($this->savedWoundEvent);
                $this->savedWoundEvent = null;
            }

            $game->notify->all("message", '${reaction_inject_code}: ${player_name} used Reaction to wound ${rena_inject_code} instead.', [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "rena_inject_code" => $owner->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }
        else if ($reactionId == 'pass')
        {
            // WHY: Pass re-releases the original wound (02016 fix — not Altruistic free-cancel).
            if ($this->savedWoundEvent !== null)
            {
                $this->skipNextEvent = true;
                $game->theah->queueEvent($this->savedWoundEvent);
                $this->savedWoundEvent = null;
                $owner->IsUpdated = true;
            }
        }

        $game->gamestate->nextState("done");
    }
}
