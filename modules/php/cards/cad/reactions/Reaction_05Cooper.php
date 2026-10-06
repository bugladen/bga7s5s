<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Cooper extends CardReaction
{
    // WHY: Cancel-first like Cascade Reaction_02059 / Spaulders Reaction_04053 —
    // clone the wound, offer Ignore/Pass. Pass re-queues the clone; skipNextEvent
    // prevents re-offering on that re-queue. No engage / wealth cost (printed Reaction
    // has none — contrast Spaulders engage and Cascade pay).
    private ?EventCharacterBeingWounded $savedWoundEvent = null;
    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Ignore Opponent's Wound";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP. Double quotes
        // interpolate undefined $you and fatal when args refresh (e.g. mid-04040).
        return parent::getReactionDescription($theah)
            . '${you} may ignore a wound from an opponent\'s ability: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, "Ignore Wound", 'ignoreWound');
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

        // WHY: Text names Vissenta — only wounds to her, not any controlled character
        // (Cascade can cover any of yours; Spaulders covers the equipped host).
        if ($event->characterId != $owner->Id)
        {
            return;
        }

        // WHY: Mirror Cascade / Spaulders — only wounds sourced from an opponent's ability.
        // Empty abilityId / missing ability = non-ability wound (e.g. duel threat resolve).
        if ($event->abilityId === '')
        {
            return;
        }

        $source = $event->theah->getCardById($event->sourceId);
        if ($source === null)
        {
            return;
        }

        $ability = $source->getAbilityById($event->abilityId);
        if ($ability === null)
        {
            return;
        }

        $abilityOwner = $ability->getOwningCard($event->theah);
        if ($abilityOwner === null || $abilityOwner->ControllerId == $owner->ControllerId)
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

        if ($reactionId === 'ignoreWound')
        {
            $game->notify->all("message", '${reaction_inject_code}: ${player_name} ignored the wound.', [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $this->savedWoundEvent = null;
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
        }

        if ($reactionId === 'pass')
        {
            if ($this->savedWoundEvent !== null)
            {
                $game->theah->queueEvent($this->savedWoundEvent);
                $this->savedWoundEvent = null;
                $this->skipNextEvent = true;
            }
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
