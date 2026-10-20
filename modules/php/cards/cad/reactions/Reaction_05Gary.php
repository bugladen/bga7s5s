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
    // offer redirect to Rena / Pass. Pass re-stacks the clone; skipNextEvent prevents
    // re-offering. Destination is always Rena (printed "Wound Rena instead"), not a picker.
    // The offer itself is stacked, not queued: a MEDIUM wound batch (Action_01012
    // wounds Sibella, then the target) would otherwise resolve before priority-6.
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
        // Name the victim. Action_01012 wounds Sibella and then the target in one
        // batch; without a name the prompt looks like it belongs to the other wound.
        $suffix = '${you} may wound Rena instead';
        if ($this->savedWoundEvent !== null)
        {
            $victim = $theah->getCharacterById($this->savedWoundEvent->characterId);
            if ($victim instanceof Character)
            {
                $suffix .= ' of ' . $victim->Name;
            }
        }

        return parent::getReactionDescription($theah) . $suffix . ': ';
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

        // WHY stackEvent: the transition's own priority (6) is *after* MEDIUM (3).
        // Action_01012 already queued Sibella's wound and the target's wound at
        // MEDIUM. queueEvent let the target resolve — and this reaction ignore it,
        // because savedWoundEvent was set — before the player was asked. stackEvent
        // puts the choice ahead of those still-queued wounds.
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
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
            // stackEvent so this clone is the next BeingWounded. queueEvent appends it
            // behind the rest of the MEDIUM batch, and the next victim consumes
            // skipNextEvent — that victim lands with no prompt, then this clone is offered.
            if ($this->savedWoundEvent !== null)
            {
                $this->skipNextEvent = true;
                $game->theah->stackEvent($this->savedWoundEvent);
                $this->savedWoundEvent = null;
                $owner->IsUpdated = true;
                // WHY: performReaction is outside runEvents, so IsUpdated is never flushed.
                // skipNextEvent has to be on the row before the next request reloads Rena.
                $game->updateCardObjectInDb($owner);
            }
        }

        $game->gamestate->nextState("done");
    }
}
