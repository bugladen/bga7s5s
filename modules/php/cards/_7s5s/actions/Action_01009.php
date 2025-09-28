<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01009 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Recruit Mercenary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($owner->Location, $includeUncontrolled = true);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Mercenary"));
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::RECRUIT_TYPE, Game::CIRILO_RECRUIT_TYPE);

            $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $owner->Id, $owner->Id);
            $event->theah->queueEvent($engageEvent);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01009", $this->Id);
            $event->theah->queueEvent($transition);

            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($game);
        }
    }
}