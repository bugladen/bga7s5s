<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01205 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kidnap Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $giacinto = $this->getOwningCharacter($theah);

        if (! $giacinto->isControlled())
        {
            return false;
        }

        if (! $theah->cardInCity($giacinto))
        {
            return false;
        }

        if ( $giacinto->Engaged )
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($giacinto->Location);
        $characters = array_filter($characters, fn($c) => $c->ControllerId != $giacinto->ControllerId);
        
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01205", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Effects must wait until EventCharacterTargeted survives. Unyielding Loyalty
        // (and Maryam / Vittoria) set canceled=true during that event. Queuing engage+move
        // beside the cost engage let UL land on a later Moving after Elena was already
        // Engaged — cancel stopped the kidnap move but not the effect engage. Cost
        // (Engage Giacinto) is queued first with no batchId so it always pays.
        // Giacinto sits in a city row (before HAND in buildCity), so this handler may
        // queue effects before UL cancels; batchId + deleteEventBatch still strips them.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $giacinto = $this->getOwningCharacter($event->theah);
            $victimId = $game->globals->get(Game::CHOSEN_CARD);
            $victim = $event->theah->getCharacterById($victimId);
            $locationName = $game->globals->get(Game::CHOSEN_LOCATION);

            if ($victim === null || $locationName === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            $victimEngageEvent = EventFactory::createCardEngagedEvent($giacinto->ControllerId, $victim->Id, $giacinto->Id, $this->Id);
            $victimEngageEvent->batchId = $batchId;
            $event->theah->queueEvent($victimEngageEvent);

            // WHY engage=false: Engage is its own clause above. Tying engage to CardMoved
            // left Lodestone/cancel targets engaged when only the move was stopped (01104).
            $giacintoMoveEvent = EventFactory::createCardMovingEvent($giacinto->ControllerId, $giacinto->Id, $giacinto->Location, $locationName, false, $giacinto->Id, $this->Id);
            $giacintoMoveEvent->batchId = $batchId;
            $event->theah->queueEvent($giacintoMoveEvent);

            $victimMoveEvent = EventFactory::createCardMovingEvent($giacinto->ControllerId, $victim->Id, $victim->Location, $locationName, false, $giacinto->Id, $this->Id);
            $victimMoveEvent->batchId = $batchId;
            $event->theah->queueEvent($victimMoveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($giacinto->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205)
        {
            $giacinto = $this->getOwningCharacter($game->theah);
            $characters = $game->theah->getCharactersAtLocation($giacinto->Location);
            $characters = array_values(array_filter($characters, fn($c) => $c->ControllerId != $giacinto->ControllerId));

            $args["characterId"] = $giacinto->Id;
            $args["targetCharacterIds"] = array_map(fn($c) => $c->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205_2)
        {
            $giacinto = $this->getOwningCharacter($game->theah);
            $locations = $game->theah->getAdjacentCityLocations($giacinto->Location, $includeHome = false);

            $victimId = $game->globals->get(Game::CHOSEN_CARD);

            $args["characterId"] = $giacinto->Id;
            $args["victimId"] = $victimId;
            
            $args["locations"] = $locations;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $giacinto = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $giacinto->ControllerId)
        {
            return [false, $game->translate("You cannot target your own character.")];
        }

        if ($character->Location != $giacinto->Location)
        {
            return [false, $game->translate("Target character is not at Giacinto's location.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205)
        {
            $targetCharacter = $game->theah->getCharacterById($id);
            if ($targetCharacter == null)
            {
                throw new UserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $targetCharacter);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_CARD, $targetCharacter->Id);

            $game->gamestate->nextState("victimChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01205_2)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            $giacinto = $this->getOwningCharacter($game->theah);
            $victimId = $game->globals->get(Game::CHOSEN_CARD);
            $victim = $game->theah->getCharacterById($victimId);
    
            $locations = $game->theah->getAdjacentCityLocations($giacinto->Location, $includeHome = false);
            if ( ! in_array($location->Name, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to Location %s."), $location->Name, $giacinto->Location));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $location->Name);

            // WHY no batchId: printed cost. Must survive Unyielding Loyalty / Maryam cancel
            // of the effect batch. Not moved to announceAction — multi-step UI can still
            // Back from location pick; cost pays only on final location commit. (Risk
            // Actions use announce for Night of Drinking; this City Action is not canceled by 01109.)
            $giacintoEngageEvent = EventFactory::createCardEngagedEvent($giacinto->ControllerId, $giacinto->Id, $giacinto->Id, $this->Id);
            $game->theah->eventCheck($giacintoEngageEvent);
            $game->theah->queueEvent($giacintoEngageEvent);

            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent($giacinto->ControllerId, $victim->Id, $giacinto->Id, $this->Id);
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
