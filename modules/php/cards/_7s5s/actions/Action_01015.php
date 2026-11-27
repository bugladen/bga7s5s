<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;

class Action_01015 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Destroy Your Character, Wound Target Character');
        $this->RequiresPerformerSelected = true;
    }

    private function getPerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            $characters = $theah->getCharactersAtLocation($performer->Location);
            $characters = array_filter($characters, fn($character) => $character->Id != $performer->Id);

            if (count($characters) > 0)
            {
                $availablePerformers[] = $performer;
            }
        }

        return $availablePerformers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getPerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $scheme = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($scheme->ControllerId, $scheme->Id, "01015", $this->Id);
            $event->queueEvent($transitionEvent);            
        }

        // WHY: Destroy + wound + resolve are ALL resolution effects of the action, so all are
        // gated on the targeting surviving. If targeting is canceled, the performer must not be
        // destroyed while the wound is stripped.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $scheme = $this->getOwningCard($theah);
            $target = $theah->getCharacterById($event->targetId);
            $performerId = $theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $theah->getCharacterById($performerId);
            if ($scheme === null || $target === null || $performer === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $theah->game->getNextEventBatchId();

            $performer->unEquipAllAttachments($theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent($performer->ControllerId, $performer->Id, $scheme->getInjectCode());
            $destroyEvent->batchId = $batchId;
            $theah->queueEvent($destroyEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $scheme->Id, 1, $scheme->getInjectCode(), $this->Id);
            $woundEvent->batchId = $batchId;
            $theah->queueEvent($woundEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01015)
        {
            $scheme = $this->getOwningCard($game->theah);
            $args['schemeId'] = $scheme->Id;
            
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->Id != $performer->Id));

            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performer->Id)
        {
            return [false, $game->translate("Target character is the performer being destroyed")];
        }

        if ($performer->Location != $character->Location)
        {
            return [false, $game->translate("Target character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01015)
        {
            $scheme = $this->getOwningCard($game->theah);
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->notify->all("message", clienttranslate('${player_name} uses ${card_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($scheme->ControllerId),
                "card_inject_code" => $scheme->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. Destroy performer + wound + ActionResolved emit from
            // handleEvent only when EventCharacterTargeted is not canceled (Cloud Bottle / UL / Maryam).
            // Queuing them beside the pick meant "when targeted" reactions never saw this action.
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $scheme->ControllerId,
                $character->Id,
                $scheme->Id,
                $this->Id
            );
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }


}