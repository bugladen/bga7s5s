<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

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

class Action_04011 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Target Mercenary Home");
    }

    /**
     * @return list<Character>
     */
    private function getEligibleMercenaries(Theah $theah, Character $hans): array
    {
        $characters = $theah->getCharactersAtLocation($hans->Location);
        // WHY: "in play and not available" = controlled Mercenary (not uncontrolled city recruit fodder).
        // No "opposing" printed — own Mercenaries are legal.
        return array_values(array_filter(
            $characters,
            fn(Character $character) => $character->hasTrait("Mercenary") && $character->isControlled()
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $hans = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($hans))
        {
            return false;
        }

        return count($this->getEligibleMercenaries($theah, $hans)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04011", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04011)
        {
            $hans = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $hans->Id;

            $mercenaries = $this->getEligibleMercenaries($game->theah, $hans);
            $args['ids'] = array_values(array_map(fn(Character $character) => $character->Id, $mercenaries));
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $hans = $this->getOwningCharacter($game->theah);

        if ($character->Location != $hans->Location)
        {
            return [false, $game->translate("Character is not at the same location as Hans Offenheim")];
        }

        if (! $character->hasTrait("Mercenary"))
        {
            return [false, $game->translate("Character is not a Mercenary")];
        }

        if (! $character->isControlled())
        {
            return [false, $game->translate("Mercenary must be in play and not available")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04011)
        {
            $character = $game->theah->getCharacterById($id);

            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $hans = $this->getOwningCharacter($game->theah);

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $moveEvent = EventFactory::createCardMovingEvent(
                $hans->ControllerId,
                $character->Id,
                $character->Location,
                Game::LOCATION_PLAYER_HOME,
                $engage = false,
                $hans->Id,
                $this->Id
            );
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($hans->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
