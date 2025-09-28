<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01083 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Challenge Only Leaders Can Intervene");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->canChallenge());
        foreach ($characters as $character)
        {
            $adversaries = $theah->getCharactersAtLocation($character->Location);
            $adversaries = array_filter($adversaries, fn($adversary) => $adversary->isControlled() && $adversary->ControllerId != $playerId);
            if (count($adversaries) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->theah->game->globals->set(Game::CHALLENGE_TYPE, Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE);
            $event->theah->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '01083', $this->Id);
            $event->theah->queueEvent($transition);

            $this->resetPlayerPassCount($event->theah->game);
        }
    }

        
}