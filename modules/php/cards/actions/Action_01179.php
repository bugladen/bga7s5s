<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01179 extends EventCityAction
{
    private array $playersUsed = [];
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Siren's Scream: Take a Reknown";
        $this->RequiresPerformer = true;
    }

    public function getCharactersForAction(int $playerId, Theah $theah): array
    {
        $characters = parent::getCharactersForAction($playerId, $theah);

        //Filter out any characters that are engaged
        $characters = array_filter($characters, fn($character) => !$character->Engaged);

        return $characters;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $card = $this->getOwningCard($theah);
        if ($card->Reknown == 0)
        {
            return false;
        }

        // Check if player has used this Action today
        if (in_array($playerId, $this->playersUsed))
        {
            return false;
        }

        return true;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        //Check to see if player has already used this Action today
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            if (in_array($event->playerId, $this->playersUsed))
            {
                throw new \BgaUserException(_("You have already used this Action today."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        //Clear players used list at the end of the day
        if ($event instanceof EventDuskEndOfDay)
        {
            $this->playersUsed = [];
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }

        // Take Reknown action
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCardById($performerId);

            $playerId = $performer->ControllerId;
            $this->playersUsed[] = $playerId;
    
            $event->theah->game->notifyAllPlayers("message", clienttranslate('${player_name} is using Siren\'s Scream to take a Reknown.'), [
                "player_name" => $event->theah->game->getPlayerNameById($playerId),
            ]);    
    
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performerId);
            $event->theah->queueEvent($engageEvent);
    
            $reknownEvent = EventFactory::createReknownRemovedFromCardEvent($playerId, $this->OwnerId, 1);
            $event->theah->queueEvent($reknownEvent);
            
            $playerEvent = EventFactory::createPlayerGainsReknownEvent($playerId, 1);
            $event->theah->queueEvent($playerEvent);
        }
    }
}