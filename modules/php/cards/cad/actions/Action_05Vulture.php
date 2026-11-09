<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Vulture extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert: Wound target opposing Scion or equipped character";
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $owner): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId);
        return array_values(array_filter(
            $opposing,
            function (Character $character) use ($theah, $owner)
            {
                if (! $character->canBeWoundedByOpponentAbilities())
                {
                    return false;
                }

                return $character->hasTrait("Scion")
                    || $character->countsAsEquipped($theah, $owner->ControllerId);
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        // WHY: Covert Action — Covert abilities only at uncontrolled City locations (Valeri / Overcoat).
        $cityLocation = $theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            return false;
        }

        return count($this->getValidTargets($theah, $owner)) > 0;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        if ($character->Location != $owner->Location)
        {
            return [false, "Target must be at Gusev's location."];
        }

        if (! $character->canBeWoundedByOpponentAbilities())
        {
            return [false, "Opponents' abilities cannot wound or move wounds to this character."];
        }

        if (! $character->hasTrait("Scion") && ! $character->countsAsEquipped($game->theah, $owner->ControllerId))
        {
            return [false, "Target must be a Scion or an equipped character."];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Vulture", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05VULTURE)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
            $args['ids'] = array_values(array_map(
                fn(Character $character) => $character->Id,
                $this->getValidTargets($game->theah, $owner)
            ));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state != States::HIGH_DRAMA_PLAYER_TURN_05VULTURE)
        {
            return;
        }

        $owner = $this->getOwningCharacter($game->theah);

        // WHY: Keep Covert honest if control flipped between announce and resolve.
        $cityLocation = $game->theah->getCityLocation($owner->Location);
        if ($cityLocation === null || $cityLocation->isControlled())
        {
            throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
        }

        $target = $game->theah->getCharacterById($id);
        if ($target === null)
        {
            throw new UserException("Character not found.");
        }

        [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
        if (! $isValid)
        {
            throw new UserException($errorMessage);
        }

        // WHY: Quick Reflexes cancels the wound before it is logged. Name the target here.
        $game->notify->all("message", '${owner_inject_code} targets ${target_inject_code} to be wounded.', [
            "owner_inject_code" => $owner->getInjectCode(),
            "target_inject_code" => $target->getInjectCode(),
        ]);

        $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
            $target->Id,
            $owner->Id,
            1,
            $owner->getInjectCode(),
            $this->Id
        );
        $game->theah->eventCheck($woundEvent);
        $game->theah->queueEvent($woundEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
        $game->theah->queueEvent($actionResolvedEvent);

        $game->gamestate->nextState("characterChosen");
    }
}
