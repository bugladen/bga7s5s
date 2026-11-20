<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05FunkyCheese extends CardReaction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Wound Leichenberg and target opposing non-Leader";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP.
        return parent::getReactionDescription($theah)
            . '${you} may wound Leichenberg and a target opposing non-Leader: ';
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
            return [false, "Character is not at Leichenberg's location."];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, "You cannot target a Leader."];
        }

        if (! $character->canBeWoundedByOpponentAbilities())
        {
            return [false, "Opponents' abilities cannot wound this character."];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventPressureOccuring) || ! $this->isAvailable())
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

        // WHY: Covert Reaction — only at uncontrolled City locations (Seadog / Valeri / Wardogb).
        if (! $event->theah->cardInCity($owner))
        {
            return;
        }

        $cityLocation = $event->theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return;
        }

        if ($event->location != $owner->Location)
        {
            return;
        }

        // WHY: Valid-target gate — no Pass-only prompt when no opposing non-Leader.
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
                throw new UserException("Choose an opposing non-Leader at this location");
            }

            // WHY: Covert still required at resolve — claim mid-reaction must not complete.
            if (! $game->theah->cardInCity($owner))
            {
                throw new UserException("Leichenberg must be at an uncontrolled City location.");
            }
            $cityLocation = $game->theah->getCityLocation($owner->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                throw new UserException("Leichenberg must be at an uncontrolled City location.");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            // WHY: Owner is both announcer and self-wound victim — avoid duplicating the same inject key.
            $game->notify->all("message", '${owner_inject_code}: ${player_name} wounds himself and ${target_inject_code}.', [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            // WHY: Printed order — Wound Leichenberg, then the target (Excalibur dual-wound).
            $woundSelf = EventFactory::createCharacterBeingWoundedEvent(
                $owner->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundSelf);

            $woundTarget = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundTarget);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }

    /**
     * Opposing non-Leaders at Leichenberg's location that can be wounded by opponent abilities.
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

            if ($character->hasTrait("Leader"))
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
}
