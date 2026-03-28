<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

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

        $this->Name = clienttranslate("Take a Renown");
        $this->RequiresPerformerSelected = true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        //Filter out any performers that are engaged
        $performers = array_filter($performers, fn($performer) => !$performer->Engaged);

        return $performers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
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
                throw new \BgaUserException($event->theah->game->translate("You have already used this Action today."));
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
            $this->notifyUsedList($event->theah->game, $card->Id);
        }

        // Take Reknown action
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCardById($performerId);

            $playerId = $performer->ControllerId;
            $this->playersUsed[] = $playerId;
    
            $event->theah->game->notify->all("message", clienttranslate('${player_name} used Siren\'s Scream to take a Renown.'), [
                "player_name" => $event->theah->game->getPlayerNameById($playerId),
            ]);    
    
            $owner = $this->getOwningCard($event->theah);
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);
    
            $reknownEvent = EventFactory::createReknownRemovedFromCardEvent($playerId, $this->OwnerId, 1);
            $event->theah->queueEvent($reknownEvent);
            
            $playerEvent = EventFactory::createPlayerGainsReknownEvent($playerId, 1);
            $event->theah->queueEvent($playerEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $event->theah->queueEvent($actionResolvedEvent);

            $this->resetPlayerPassCount($event->theah->game);
            // $this->setUsed() not called because special use cases

            $this->notifyUsedList($event->theah->game, $owner->Id);
        }
    }

    private function notifyUsedList(Game $game, int $cardId): void
    {
        $game->notify->all("sirensScreamUsedListUpdated", '', [
            'cardId' => $cardId,
            'usedList' => $this->getUsedListData($game),
        ]);
    }

    public function getUsedListData(Game $game): array
    {
        $list = [];
        foreach ($this->playersUsed as $playerId) {
            $list[] = [
                'playerId' => $playerId,
                'playerName' => $game->getPlayerNameById($playerId),
                'playerColor' => $game->getPlayerColorById($playerId),
            ];
        }
        return $list;
    }
}