<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01058 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Opposing Character Home Engaged");
        $this->RequiresPerformerSelected = true;
    }

    private function getAvailablePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($performer->Location);
            $opposingCharacters = array_values(array_filter($opposingCharacters, fn($opposingCharacter) => 
                            $opposingCharacter->isNotControlledByPlayer($playerId) && 
                            $opposingCharacter->ModifiedCombat < $performer->ModifiedCombat &&
                            ! $opposingCharacter->hasTrait("Leader")));     

            if (count($opposingCharacters) > 0)
                $availablePerformers[] = $performer;
        }

        return $availablePerformers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $performers = $this->getAvailablePerformers($playerId, $theah);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getAvailablePerformers($playerId, $theah);        
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01058", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Do not queue engage/Home beside the cancel hook. Unyielding Loyalty
        // stores only the event it intercepts and deleteEventBatchs the rest. Queuing
        // engage + move with no batchId let UL cancel the engage (or offer on move)
        // while the sibling still fired — Sibella went Home engaged after a paid cancel.
        // Gate effects on EventCharacterTargeted (Amour Action_01104 / Come Hither
        // Action_01162 / Giacinto Action_01205): UL holds the targeting event; Pass /
        // NoD re-queue it and this handler emits the full package again.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);

            if ($character === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            // WHY: Engage and go Home are separate clauses — queue Engage first, then move
            // with engage=false (Action_01104 / Lodestone pattern). Tying engage to
            // CardMoved left cancel/Lodestone targets engaged when only the move stopped.
            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
            $engageEvent->batchId = $batchId;
            $event->theah->queueEvent($engageEvent);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
            $moveEvent->batchId = $batchId;
            $event->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01058)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $opposingCharacters = array_values(array_filter($characters, fn($character) => 
                            $character->isNotControlledByPlayer($performer->ControllerId) && 
                            $character->ModifiedCombat < $performer->ModifiedCombat &&
                            ! $character->hasTrait("Leader")));

            $args['characterIds'] = array_map(fn($character) => $character->Id, $opposingCharacters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $target): array
    {
        if ($target->hasTrait("Leader"))
        {
            return [false, $game->translate("Cannot move a Leader HOME")];
        }
        
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($target->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        if ($target->ModifiedCombat >= $performer->ModifiedCombat)
        {
            return [false, $game->translate("Character has an equal or higher Combat stat than the performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01058)
        {
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

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->notify->all("message", clienttranslate('${player_name} has chosen to engage ${character_inject_code} and move them HOME.'), [
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            $owner = $this->getOwningCard($game->theah);

            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
