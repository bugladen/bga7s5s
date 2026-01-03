<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;

class Action_01015 extends SchemeCityAction implements IAbilityThatTargetsCards, IAbilityThatTargetsCharacters
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
            $characters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($playerId));

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
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));

            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
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
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($character->ControllerId == $scheme->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot destroy your own character"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer->Location != $character->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location"));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} used the [${action_name}] action.'), [
                "i18n" => ["action_name"],
                "scheme_inject_code" => $scheme->getInjectCode(),
                "action_name" => $this->Name,
                "player_name" => $game->getActivePlayerName(),
            ]);

            $performer->unEquipAllAttachments($game->theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent($performer->ControllerId, $performer->Id, $scheme->getInjectCode());
            $game->theah->queueEvent($destroyEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $scheme->Id, 1, $scheme->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);
            
            $game->gamestate->nextState("characterChosen");
        }
    }


}