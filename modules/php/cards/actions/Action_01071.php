<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01071 extends SchemeCityAction
{
    public bool $firstWoundOccured = false;
    
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = "Issue Fight Challenge";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => in_array("Musketeer", $character->Traits));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        $performers = array_filter($performers, fn($performer) => in_array("Musketeer", $performer->Traits));

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $event->theah->game->globals->set(Game::CHALLENGE_TYPE, Game::EPEE_SANGLANTE_CHALLENGE_TYPE);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01071");
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterWounded)
        {
            $scheme = $this->getOwningCard($event->theah);
            if ($scheme->Location == Game::LOCATION_PLAYER_HOME)
            {
                $game = $event->theah->game;
                $inDuel = $game->globals->get(Game::IN_DUEL);
                $challengeType = $game->globals->get(Game::CHALLENGE_TYPE);
                if ($inDuel && $challengeType == Game::EPEE_SANGLANTE_CHALLENGE_TYPE && ! $this->firstWoundOccured)
                {
                    $woundedCharacter = $event->theah->getCharacterById($event->characterId);
                    $woundedPlayerReknown = $game->getPlayerReknown($woundedCharacter->ControllerId);
                    $agressor = $event->theah->getCharacterById($event->sourceId);

                    if ($woundedPlayerReknown > 0)
                    {
                        $stealEvent = EventFactory::createPlayerGainsReknownEvent($agressor->ControllerId, 1);
                        $event->theah->queueEvent($stealEvent);
                        
                        $loseEvent = EventFactory::createPlayerLosesReknownEvent($woundedCharacter->ControllerId, 1);
                        $event->theah->queueEvent($loseEvent);
                        
                        $game->notifyAllPlayers("message", clienttranslate('Épée Sanglante: <strong>${agressor_name}</strong> is the first player to wound in this duel. They will steal 1 Reknown from <strong>${player_name}</strong>.'), [
                            "agressor_name" => $game->getPlayerNameById($agressor->ControllerId),
                            "player_name" => $game->getPlayerNameById($woundedCharacter->ControllerId),
                        ]);
                    }
                    else
                    {
                        $game->notifyAllPlayers("message", clienttranslate('Épée Sanglante: <strong>${agressor_name}</strong> is the first player to wound in this duel. However, <strong>${player_name}</strong> has no Reknown to steal.'), [
                            "agressor_name" => $game->getPlayerNameById($agressor->ControllerId),
                            "player_name" => $game->getPlayerNameById($woundedCharacter->ControllerId),
                        ]);
                    }

                    $this->firstWoundOccured = true;
                    $scheme->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $scheme = $this->getOwningCard($event->theah);
            if ($scheme->Location == Game::LOCATION_PLAYER_HOME)
            {
                $this->firstWoundOccured = false;
                $scheme->IsUpdated = true;
            }
        }
    }
}