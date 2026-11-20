<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Covert Reaction: When you discard a Discovery or Alquimia • Wound target opposing character.
class Reaction_05Quack extends CardReaction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Wound target opposing character after discarding Discovery or Alquimia";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound a target opposing character: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        foreach ($this->getValidWoundTargets($theah) as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, 'Wound ' . $character->Name, "wound-{$character->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);
        if ($owner === null)
        {
            return [false, "Invalid reaction owner."];
        }

        if ($character->ControllerId == $owner->ControllerId)
        {
            return [false, "You cannot wound a character that is controlled by you."];
        }

        if ($character->Location != $owner->Location)
        {
            return [false, "Character is not at Takama's location."];
        }

        if (! $character->canBeWoundedByOpponentAbilities())
        {
            return [false, "Opponents' abilities cannot wound this character."];
        }

        return [true, ""];
    }

    private function isCovertSite(Theah $theah, Character $owner): bool
    {
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($owner->Location);
        return $cityLocation !== null && ! $cityLocation->isControlled();
    }

    /**
     * Opposing characters at Takama's location that can be wounded by opponent abilities.
     *
     * @return Character[]
     */
    private function getValidWoundTargets(Theah $theah): array
    {
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || $theah->game->characterIsInDiscardOrLocker($owner))
        {
            return [];
        }

        $targets = [];
        foreach ($theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId) as $character)
        {
            if (! ($character instanceof Character))
            {
                continue;
            }

            if ($theah->game->characterIsInDiscardOrLocker($character))
            {
                continue;
            }

            if (! $character->canBeWoundedByOpponentAbilities())
            {
                continue;
            }

            $targets[] = $character;
        }

        return $targets;
    }

    // WHY: Hand, faction-from-play, and city-discard are three pipelines. Cloud Bottle
    // (Alquimia) destroy via Terraria is EventCardDiscardedFromPlay — hand-only missed it.
    private function isYourDiscardEvent(Event $event, int $controllerId): bool
    {
        if ($event instanceof EventCardDiscardedFromHand || $event instanceof EventCardDiscardedFromPlay)
        {
            return $event->ownerId == $controllerId;
        }

        if ($event instanceof EventCardAddedToCityDiscardPile)
        {
            return $event->playerId == $controllerId;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable())
        {
            return;
        }

        if (! ($event instanceof EventCardDiscardedFromHand
            || $event instanceof EventCardDiscardedFromPlay
            || $event instanceof EventCardAddedToCityDiscardPile))
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

        // WHY: "you discard" — opponent discards (e.g. Quack Action forcing them) do not trigger.
        if (! $this->isYourDiscardEvent($event, $owner->ControllerId))
        {
            return;
        }

        // WHY: Covert Reaction — only at uncontrolled City locations (Seadog / FunkyCheese).
        if (! $this->isCovertSite($event->theah, $owner))
        {
            return;
        }

        // WHY runEventHubAfterCards=true: card still at from-location during handleEvent, traits intact.
        $card = $event->theah->getCardById($event->cardId);
        if ($card === null)
        {
            $card = $event->theah->game->getCardObjectFromDb($event->cardId);
        }
        if ($card === null)
        {
            return;
        }

        if (! $card->hasTrait("Discovery") && ! $card->hasTrait("Alquimia"))
        {
            return;
        }

        // WHY: Valid-target gate — no Pass-only prompt when no opposing character.
        if (count($this->getValidWoundTargets($event->theah)) === 0)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if (str_starts_with($reactionId, 'wound-'))
        {
            $owner = $this->getOwningCharacter($game->theah);
            $targetId = (int) substr($reactionId, strlen('wound-'));
            $target = $game->theah->getCharacterById($targetId);

            if ($owner === null
                || $target === null
                || $game->characterIsInDiscardOrLocker($owner))
            {
                throw new UserException("Choose an opposing character at this location");
            }

            // WHY: Covert still required at resolve — claim mid-reaction must not complete.
            if (! $this->isCovertSite($game->theah, $owner))
            {
                throw new UserException("Takama must be at an uncontrolled City location.");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->notify->all("message", '${owner_inject_code}: ${player_name} wounds ${target_inject_code}.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
