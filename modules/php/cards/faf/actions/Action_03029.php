<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03029 extends SchemeCityAction implements ISorcererAbility
{
    private const MOVE_TO_PERFORMER = 1;
    private const MOVE_FROM_PERFORMER = 2;

    public int $MoveMode = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Performer and Move Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        foreach ($this->getPerformersForAction($playerId, $theah) as $performer)
        {
            if ($this->hasOptionToPerformer($theah, $playerId, $performer)
                || $this->hasOptionFromPerformer($theah, $playerId, $performer))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Sorcerer")
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03029", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        $playerId = $game->getActivePlayerId();

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03029)
        {
            $args["performerId"] = $performerId;
            $args["optionToPerformerAvailable"] = $performer !== null
                && $this->hasOptionToPerformer($game->theah, $playerId, $performer);
            $args["optionFromPerformerAvailable"] = $performer !== null
                && $this->hasOptionFromPerformer($game->theah, $playerId, $performer);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03029_2)
        {
            $args["performerId"] = $performerId;
            $args["characterIds"] = array_map(
                fn(Character $character) => $character->Id,
                $this->getEligibleCharactersForMode($game->theah, $playerId, $performer, $this->MoveMode)
            );
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03029_3)
        {
            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            $args["performerId"] = $performerId;
            $args["characterId"] = $characterId;
            $args["locationIds"] = $character !== null
                ? $this->getValidDestinationLocations($game->theah, $character)
                : [];
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, $game->translate("Performer not found.")];
        }

        if ($character->ControllerId != $performer->ControllerId)
        {
            return [false, $game->translate("You do not control that character.")];
        }

        if ($this->MoveMode == self::MOVE_TO_PERFORMER)
        {
            if ($character->Location == $performer->Location)
            {
                return [false, $game->translate("Character is already at the performer's location.")];
            }

            return [true, ""];
        }

        if ($this->MoveMode == self::MOVE_FROM_PERFORMER)
        {
            if ($character->Location != $performer->Location)
            {
                return [false, $game->translate("Character is not at the performer's location.")];
            }

            if (count($this->getValidDestinationLocations($game->theah, $character)) == 0)
            {
                return [false, $game->translate("Character has no valid destination.")];
            }

            return [true, ""];
        }

        return [false, $game->translate("Invalid move option.")];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        $owner = $this->getOwningCard($game->theah);
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03029)
        {
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            if ($id == self::MOVE_TO_PERFORMER)
            {
                if (! $this->hasOptionToPerformer($game->theah, $performer->ControllerId, $performer))
                {
                    throw new UserException($game->translate("No character is available to move to the performer's location."));
                }
            }
            else if ($id == self::MOVE_FROM_PERFORMER)
            {
                if (! $this->hasOptionFromPerformer($game->theah, $performer->ControllerId, $performer))
                {
                    throw new UserException($game->translate("No character is available to move from the performer's location."));
                }
            }
            else
            {
                throw new UserException($game->translate("Invalid move option."));
            }

            $this->MoveMode = $id;
            $game->updateCardObjectInDb($owner);


            $game->gamestate->nextState("optionChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03029_2)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            if ($this->MoveMode == self::MOVE_TO_PERFORMER)
            {
                $this->resolveMove($game, $owner, $performer, $character, $performer->Location);
                $this->MoveMode = 0;
                $owner->IsUpdated = true;
                $game->gamestate->nextState("done");
                return;
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);
            $game->gamestate->nextState("characterChosen");
            return;
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03029_3)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            if ($performer === null || $character === null)
            {
                throw new UserException($game->translate("Invalid character selection."));
            }

            $locationName = $ids[0];
            $validLocations = $this->getValidDestinationLocations($game->theah, $character);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException(sprintf($game->translate("Location %s is not a valid destination."), $locationName));
            }

            $this->resolveMove($game, $owner, $performer, $character, $locationName);

            $game->globals->set(Game::CHOSEN_CARD, null);
            $this->MoveMode = 0;
            $owner->IsUpdated = true;

            $game->gamestate->nextState("locationChosen");
        }
    }

    private function resolveMove(
        Game $game,
        Card $owner,
        Character $performer,
        Character $character,
        string $destinationLocation
    ): void
    {
        if ($character->Location == $destinationLocation)
        {
            throw new UserException($game->translate("Character is already at that location."));
        }

        $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent(
            $owner->ControllerId,
            $owner->Id,
            $this->Id,
            $performer->Id,
            $character->Id,
            $character->Location
        );
        $game->theah->queueEvent($sorceryStartEvent);

        $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
            $performer->Id,
            $owner->Id,
            1,
            $owner->getInjectCode(),
            $this->Id
        );
        $game->theah->eventCheck($woundEvent);
        $game->theah->queueEvent($woundEvent);

        $moveEvent = EventFactory::createCardMovingEvent(
            $character->ControllerId,
            $character->Id,
            $character->Location,
            $destinationLocation,
            false,
            $owner->Id,
            $this->Id
        );
        $game->theah->eventCheck($moveEvent);
        $game->theah->queueEvent($moveEvent);

        $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent(
            $owner->ControllerId,
            $owner->Id,
            $this->Id,
            $performer->Id,
            $character->Id,
            $destinationLocation
        );
        $game->theah->queueEvent($sorceryPlayedEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
        $game->theah->queueEvent($actionResolvedEvent);
    }

    private function hasOptionToPerformer(Theah $theah, int $playerId, Character $performer): bool
    {
        return count($this->getEligibleCharactersForMode(
            $theah,
            $playerId,
            $performer,
            self::MOVE_TO_PERFORMER
        )) > 0;
    }

    private function hasOptionFromPerformer(Theah $theah, int $playerId, Character $performer): bool
    {
        return count($this->getEligibleCharactersForMode(
            $theah,
            $playerId,
            $performer,
            self::MOVE_FROM_PERFORMER
        )) > 0;
    }

    /**
     * @return list<Character>
     */
    private function getEligibleCharactersForMode(
        Theah $theah,
        int $playerId,
        Character $performer,
        int $moveMode
    ): array
    {
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);

        if ($moveMode == self::MOVE_TO_PERFORMER)
        {
            return array_values(array_filter(
                $characters,
                fn(Character $character) => $character->Location != $performer->Location
            ));
        }

        if ($moveMode == self::MOVE_FROM_PERFORMER)
        {
            return array_values(array_filter(
                $characters,
                fn(Character $character) => $character->Location == $performer->Location
                    && count($this->getValidDestinationLocations($theah, $character)) > 0
            ));
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function getValidDestinationLocations(Theah $theah, Character $character): array
    {
        $locations = array_map(
            fn($location) => $location->Name,
            $theah->getCityLocations()
        );

        if ($character->Location != Game::LOCATION_PLAYER_HOME)
        {
            $locations[] = Game::LOCATION_PLAYER_HOME;
        }

        return array_values(array_filter(
            $locations,
            fn(string $location) => $location != $character->Location
        ));
    }
}
