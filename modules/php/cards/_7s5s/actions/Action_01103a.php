<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01103a extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claim a City Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $firstPlayer = $theah->game->globals->get(Game::FIRST_PLAYER);
        if ($playerId != $firstPlayer)
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Pirate"));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = parent::getPerformersForAction($playerId, $theah);
        $characters = array_values(array_filter($characters, fn($character) => $character->hasTrait("Pirate")));
        
        return $characters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $claimEvent = EventFactory::createLocationClaimedEvent($event->playerId, $performerId, $performer->Location);
            $event->theah->queueEvent($claimEvent);
        }
    }
}
