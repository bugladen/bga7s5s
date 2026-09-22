<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04041 extends CardReaction
{
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCardEngaged $cardEngagedEvent = null;
    private string $affectedCharacterName = '';
    private bool $skipNextEngagedEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel opponent move or engage on your character");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->cardMovingEvent !== null)
        {
            return $base . sprintf(
                $theah->game->translate('${you} may cancel the movement of %s to %s: '),
                $this->affectedCharacterName,
                $theah->game->translate($this->cardMovingEvent->toLocation)
            );
        }

        return $base . sprintf(
            $theah->game->translate('${you} may cancel the engage of %s: '),
            $this->affectedCharacterName
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->cardMovingEvent !== null)
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Movement'), 'cancel');
        }
        else
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Engage'), 'cancel');
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    /**
     * Mirror Reaction_04021 / Reaction_03031: opponent ability = source card (or
     * in-play action owner) controlled by a different non-zero player.
     * sourceId==0 (framework auto-engage / dusk move-home) is not an "opponent's effect."
     */
    private function isOpponentEffect(Theah $theah, int $sourceId, string $abilityId, int $ownerPlayerId): bool
    {
        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            return $source->ControllerId != $ownerPlayerId && $source->ControllerId != 0;
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action && $action instanceof ICardAbility)
        {
            $owningCard = $action->getOwningCard($theah);
            return $owningCard !== null
                && $owningCard->ControllerId != $ownerPlayerId
                && $owningCard->ControllerId != 0;
        }

        return false;
    }

    private function interceptMoving(EventCardMoving $event, Character $owner, Character $character): void
    {
        $this->cardMovingEvent = clone $event;
        unset($this->cardMovingEvent->theah);
        $this->cardEngagedEvent = null;
        $this->affectedCharacterName = $character->Name;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    private function interceptEngaged(EventCardEngaged $event, Character $owner, Character $character): void
    {
        if ($this->skipNextEngagedEvent)
        {
            $this->skipNextEngagedEvent = false;
            $owner->IsUpdated = true;
            return;
        }

        $this->cardEngagedEvent = clone $event;
        unset($this->cardEngagedEvent->theah);
        $this->cardMovingEvent = null;
        $this->affectedCharacterName = $character->Name;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoving && ! $event->canceled && ! $event->unstoppable && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null || ! $owner->isControlled())
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($owner))
            {
                return;
            }

            // City Reaction
            if (! $event->theah->cardInCity($owner))
            {
                return;
            }

            if (in_array($owner->Id, $event->cancelDeclinedByCardIds, true))
            {
                return;
            }

            if (! $this->isOpponentEffect($event->theah, $event->sourceId, $event->abilityId, $owner->ControllerId))
            {
                return;
            }

            $character = $event->theah->getCardById($event->cardId);
            if (! ($character instanceof Character))
            {
                return;
            }

            if ($character->ControllerId != $owner->ControllerId)
            {
                return;
            }

            // Character must currently be at Jak-Sen's location (fromLocation still live;
            // EventCardMoving.runEventHubAfterCards defers the location write).
            if ($event->fromLocation != $owner->Location)
            {
                return;
            }

            $this->interceptMoving($event, $owner, $character);
        }

        if ($event instanceof EventCardEngaged && ! $event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null || ! $owner->isControlled())
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($owner))
            {
                return;
            }

            // City Reaction
            if (! $event->theah->cardInCity($owner))
            {
                return;
            }

            if (! $this->isOpponentEffect($event->theah, $event->sourceId, $event->abilityId, $owner->ControllerId))
            {
                return;
            }

            $character = $event->theah->getCharacterById($event->cardId);
            if ($character === null)
            {
                return;
            }

            if ($character->ControllerId != $owner->ControllerId)
            {
                return;
            }

            if ($character->Location != $owner->Location)
            {
                return;
            }

            $this->interceptEngaged($event, $owner, $character);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'cancel')
        {
            if ($this->cardMovingEvent !== null)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} cancelled the movement of ${character_name}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_name" => $this->affectedCharacterName,
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} cancelled the engage of ${character_name}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_name" => $this->affectedCharacterName,
                ]);
            }

            $this->setUsed($game->theah, true);
            $this->cardMovingEvent = null;
            $this->cardEngagedEvent = null;
            $this->affectedCharacterName = '';
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        if ($reactionId == 'decline')
        {
            // WHY: Moving has cancelDeclinedByCardIds (Stubborn / Ise). Engaged does
            // not — use skipNextEngagedEvent so the re-queued engage is not re-caught
            // (Unyielding Loyalty / Hexenwerk shape).
            if ($this->cardMovingEvent !== null)
            {
                $this->cardMovingEvent->cancelDeclinedByCardIds[] = $owner->Id;
                $game->theah->queueEvent($this->cardMovingEvent);
            }
            else if ($this->cardEngagedEvent !== null)
            {
                $this->skipNextEngagedEvent = true;
                $game->theah->queueEvent($this->cardEngagedEvent);
            }

            $this->cardMovingEvent = null;
            $this->cardEngagedEvent = null;
            $this->affectedCharacterName = '';
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        $game->gamestate->nextState("done");
    }
}
