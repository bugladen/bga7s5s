<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01085 extends RiskAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public int $LastTargetId = 0;
    public string $LastTargetLocation = "";

    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Move Character to Performer's Location");
        $this->RequiresPerformerSelected = true;
        $this->LastTargetId = 0;
        $this->LastTargetLocation = "";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // Get characters that are not at the performer's location
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $sorcerers = array_filter($characters, fn($character) => $character->HasTrait("Sorcerer"));
        if (count($sorcerers) == 0)
        {
            return false;
        }

        foreach ($sorcerers as $sorcerer)
        {
            $characters = $theah->getCharactersInPlayByPlayerId($playerId);
            foreach ($characters as $character)
            {
                if ($character->Location != $sorcerer->Location)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->HasTrait("Sorcerer"));

        return $characters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01085", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $playerId = $game->getActivePlayerId();
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        $characters = $game->theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_values(array_filter($characters, fn($character) => $character->Location != $performer->Location));

        $ids = array_map(fn($character) => $character->Id, $characters);

        $args["performerId"] = $performerId;
        $args["charactersIds"] = $ids;

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performer->Id)
        {
            return [false, $game->translate("Character cannot be the same as the performer")];
        }

        if ($character->Location == $performer->Location)
        {
            return [false, $game->translate("Character is already at the performer's location")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01085)
        {
            $porteTravel = $this->getOwningCard($game->theah);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            //Done
            if ($id == 0)
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($porteTravel->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $event = EventFactory::createSorcererAbilityPlayedEvent($porteTravel->ControllerId, $porteTravel->Id, $this->Id, $performer->Id, $this->LastTargetId, $this->LastTargetLocation);
                $game->theah->queueEvent($event);
    
                $game->gamestate->nextState();
                return;
            }

            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }


            $game->notify->all("message", $game->translate('Porté Travel: ${player_name} has chosen to move ${character_inject_code} to ${performer_inject_code}\'s location.'), [
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
                "performer_inject_code" => $performer->getInjectCode(),
            ]);

            $this->LastTargetId = $target->Id;
            $this->LastTargetLocation = $target->Location;
            $game->updateCardObjectInDb($porteTravel);
            $game->theah->addCardToWorld($porteTravel);

            $batchId = $game->getNextEventBatchId();

            $event = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $porteTravel->Id, 1, $porteTravel->getInjectCode(), $this->Id);
            $event->batchId = $batchId;
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $event = EventFactory::createSorcererAbilityStartEvent($porteTravel->ControllerId, $porteTravel->Id, $this->Id, $performer->Id, $this->LastTargetId, $this->LastTargetLocation);
            $event->batchId = $batchId;
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardMovingEvent($performer->ControllerId, $target->Id, $target->Location, $performer->Location, $engage = false, $porteTravel->Id, $this->Id);
            $event->batchId = $batchId;
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
            
            $transition = EventFactory::createTransitionEvent($performer->ControllerId, $porteTravel->Id, "01085", $this->Id);
            $game->theah->queueEvent($transition);
            
            $game->gamestate->nextState();
        }
    }
}