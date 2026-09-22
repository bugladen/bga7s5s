<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04026 extends AttachmentReaction
{
    // WHY: Clone of EventLocationPressured fields — performReaction runs after the
    // original event is gone; need playerId/performer/location/etc. to rebuild a failed Result.
    private ?int $pressuredPlayerId = null;
    private ?int $pressuredPerformerId = null;
    private string $pressuredLocation = '';
    private string $pressuredPressureType = '';
    private string $pressuredTotalsExplanation = '';
    private bool $pressuredHighDramaBasicAction = false;
    private string $pressuredAbilityId = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure at this location fails (difference ≤1)");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah)
            . $theah->game->translate('${you} may engage this card for the Pressure to Fail: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Fail Pressure'), 'failPressure');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventLocationPressured))
        {
            return;
        }

        if (! $event->success || $event->difference > 1)
        {
            return;
        }

        if (! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner === null || $owner->Engaged)
        {
            return;
        }

        $owningCharacter = $this->getOwningCharacter($event->theah);
        if ($owningCharacter === null || $event->location != $owningCharacter->Location)
        {
            return;
        }

        // WHY: Mirror Objection (_01027) — only interrupt another player's pressure.
        // Printed text does not say "opponent," but failing your own success is not the intent.
        if ($event->playerId == $owner->ControllerId)
        {
            return;
        }

        $this->pressuredPlayerId = $event->playerId;
        $this->pressuredPerformerId = $event->performerId;
        $this->pressuredLocation = $event->location;
        $this->pressuredPressureType = $event->pressureType;
        $this->pressuredTotalsExplanation = $event->totalsExplanation;
        $this->pressuredHighDramaBasicAction = $event->highDramaBasicAction;
        $this->pressuredAbilityId = $event->abilityId;
        $owner->IsUpdated = true;

        // WHY: HIGH_PRIORITY so the offer interrupts before LocationPressureResult resolves.
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $transition->priority = Event::HIGH_PRIORITY;
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId === 'failPressure')
        {
            $owner = $this->getOwningAttachment($game->theah);
            if ($owner === null || $this->pressuredPlayerId === null)
            {
                $game->gamestate->nextState("done");
                return;
            }

            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: Difference of Pressure was 1 or less. ${player_name} used Reaction and FAILED Pressure.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            // Delete the existing pressure result event and replace with a failed pressure result event
            $game->theah->deletePressureResultEvents();
            $failEvent = EventFactory::createLocationPressureResultEvent(
                $this->pressuredPlayerId,
                $this->pressuredPerformerId,
                $this->pressuredLocation,
                $this->pressuredPressureType,
                false,
                $this->pressuredTotalsExplanation,
                $this->pressuredHighDramaBasicAction,
                $this->pressuredAbilityId
            );
            $game->theah->queueEvent($failEvent);

            $this->clearPressuredContext();
            $owner->IsUpdated = true;
            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }

    private function clearPressuredContext(): void
    {
        $this->pressuredPlayerId = null;
        $this->pressuredPerformerId = null;
        $this->pressuredLocation = '';
        $this->pressuredPressureType = '';
        $this->pressuredTotalsExplanation = '';
        $this->pressuredHighDramaBasicAction = false;
        $this->pressuredAbilityId = '';
    }
}
