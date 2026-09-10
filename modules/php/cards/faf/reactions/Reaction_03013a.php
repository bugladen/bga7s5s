<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03013a extends CardReaction
{
    /** @var int[] character ids we granted Sorcerer this turn */
    private array $TaggedCharacterIds = [];

    // WHY: CardReaction::performReaction stacks EventReactionActivated with sourceId =
    // owner. Without this guard that event would immediately re-queue this Continuous
    // Reaction (sourceId == Daniella). Cleared when that EventReactionActivated is seen.
    private bool $SuppressNextSourceTrigger = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("(Continuous) Opposing character gains the Sorcerer Trait");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may grant an opposing character Sorcerer while at Daniella\'s location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleTargets($theah, $owner) as $character)
            {
                $array[] = $this->createButtonProperty(
                    $theah->game,
                    sprintf($theah->game->translate('Grant Sorcerer to %s'), $character->Name),
                    "grant-{$character->Id}"
                );
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * @return Character[] opposing characters at Daniella's location who do not already have Sorcerer
     */
    private function getEligibleTargets(Theah $theah, Character $owner): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $c) => ! $c->hasTrait("Sorcerer")
                && ! in_array($c->Id, $this->TaggedCharacterIds, true)
        ));
    }

    private function eventSourceIsOwner(Event $event, int $ownerId): bool
    {
        // WHY: Transition events stamp sourceId as the owning card for state routing,
        // not as an ability-effect source. Matching them double-prompts after every
        // ActionTriggered and was the leak that still caught Action_03013 (which queues
        // createTransitionEvent(..., "03013", $this->Id) with sourceId = Daniella).
        if ($event instanceof EventTransition)
        {
            return false;
        }

        // WHY: broad "any event with sourceId" gate — many ability-effect events stamp
        // sourceId as the card that caused them. Skip null/0 (framework / no source).
        if (! property_exists($event, 'sourceId'))
        {
            return false;
        }

        $sourceId = $event->sourceId;
        return $sourceId !== null && (int) $sourceId === $ownerId;
    }

    private function isFromAction03013(Event $event): bool
    {
        // WHY: Action_03013 is the sibling Continuous Action that already lets the
        // player grant Sorcerer to one opposing character. Reacting to its
        // Activated/Triggered/picker-Transition events would double-prompt.
        // Eddie: do not react to Action_03013.
        if ($event instanceof EventActionActivated || $event instanceof EventActionTriggered)
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source === null)
            {
                return false;
            }

            return $source->getAbilityById($event->actionId) instanceof Action_03013;
        }

        if ($event instanceof EventTransition)
        {
            // Action_03013 queues createTransitionEvent(..., transition "03013", internalId = action Id)
            if ($event->transition === '03013')
            {
                return true;
            }

            if ($event->internalId === '' || $event->sourceId === 0)
            {
                return false;
            }

            $source = $event->theah->getCardById($event->sourceId);
            if ($source === null)
            {
                return false;
            }

            return $source->getAbilityById($event->internalId) instanceof Action_03013;
        }

        return false;
    }

    private function maneuverOrTechniqueWithOwnerAsActor(Event $event, Character $owner): bool
    {
        // WHY: combat-card ManeuverActivated stamps ownerId as the combat card, not the
        // duel actor. TechniqueActivated often stamps the actor/owner card. Either way
        // the printed "ability use" for duel maneuvers/techniques is "Daniella is actor."
        if ($event instanceof EventManeuverActivated || $event instanceof EventTechniqueActivated)
        {
            $actor = $event->theah->getDuelRoundActor();
            if ($actor !== null && $actor->Id === $owner->Id)
            {
                return true;
            }

            // Challenge-time TechniqueActivated: ownerId is the technique's owning card
            // (Daniella), and there may be no duel round actor yet.
            if ($event instanceof EventTechniqueActivated && $event->ownerId === $owner->Id)
            {
                return true;
            }
        }

        return false;
    }

    private function tryQueueReaction(Event $event, Character $owner): void
    {
        if (count($this->getEligibleTargets($event->theah, $owner)) === 0)
        {
            return;
        }

        $owner->IsUpdated = true;
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        // WHY stackEvent: bumps priority above everything currently queued (min-1) so the
        // Continuous Reaction prompt runs before later ability follow-up events.
        // Same shape as Reaction_03013 (discount) / Reaction_01090.
        $event->theah->stackEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        $owner = $this->getOwningCharacter($event->theah);

        // WHY location-scoped: same as Action_03013 — opposing Daniella = co-location.
        if ($event instanceof EventCardMoved && $owner !== null)
        {
            if ($event->cardId === $owner->Id)
            {
                $this->clearTaggedSorcerers($event->theah);
            }
            elseif (in_array($event->cardId, $this->TaggedCharacterIds, true)
                && $event->toLocation !== $owner->Location)
            {
                $this->untagCharacter($event->theah, $event->cardId);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $owner !== null)
        {
            if ($event->characterId === $owner->Id)
            {
                $this->clearTaggedSorcerers($event->theah);
            }
            elseif (in_array($event->characterId, $this->TaggedCharacterIds, true))
            {
                $this->untagCharacter($event->theah, $event->characterId);
            }
        }

        if (! $this->isAvailable())
        {
            return;
        }

        if ($owner === null)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        // Consume the EventReactionActivated that our own performReaction stacked.
        if ($this->SuppressNextSourceTrigger
            && $event instanceof EventReactionActivated
            && $this->eventSourceIsOwner($event, $owner->Id))
        {
            $this->SuppressNextSourceTrigger = false;
            $owner->IsUpdated = true;
            return;
        }

        if ($this->isFromAction03013($event))
        {
            return;
        }

        $triggered = $this->eventSourceIsOwner($event, $owner->Id)
            || $this->maneuverOrTechniqueWithOwnerAsActor($event, $owner);

        if ($triggered)
        {
            $this->tryQueueReaction($event, $owner);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        // WHY: set before parent so the stacked EventReactionActivated is suppressed.
        // Pass/decline do not emit EventReactionActivated (CardReaction skips them).
        if ($reactionId !== 'pass' && str_starts_with($reactionId, 'grant-'))
        {
            $this->SuppressNextSourceTrigger = true;
        }

        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId !== 'pass' && str_starts_with($reactionId, 'grant-'))
        {
            $characterId = (int) substr($reactionId, strlen('grant-'));
            $character = $game->theah->getCharacterById($characterId);

            if ($character !== null
                && $character->ControllerId !== $owner->ControllerId
                && $character->Location === $owner->Location
                && ! $character->hasTrait("Sorcerer")
                && ! in_array($character->Id, $this->TaggedCharacterIds, true))
            {
                $character->addTrait($game, "Sorcerer");
                $this->TaggedCharacterIds[] = $character->Id;
                $owner->IsUpdated = true;

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} grants ${character_inject_code} Sorcerer while at Daniella\'s location.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);
            }

            // Continuous Reaction: intentionally do NOT call $this->setUsed(true).
            // The reaction remains available and can fire on every qualifying ability use.
        }

        $game->gamestate->nextState("done");
    }

    private function untagCharacter(Theah $theah, int $characterId): void
    {
        $index = array_search($characterId, $this->TaggedCharacterIds, true);
        if ($index === false)
        {
            return;
        }

        unset($this->TaggedCharacterIds[$index]);
        $this->TaggedCharacterIds = array_values($this->TaggedCharacterIds);

        $character = $theah->getCharacterById($characterId);
        if ($character !== null)
        {
            $character->removeTrait($theah->game, "Sorcerer");
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function clearTaggedSorcerers(Theah $theah): void
    {
        if (empty($this->TaggedCharacterIds))
        {
            return;
        }

        foreach (array_values($this->TaggedCharacterIds) as $characterId)
        {
            $this->untagCharacter($theah, $characterId);
        }
    }
}
