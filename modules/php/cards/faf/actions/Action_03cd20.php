<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03cd20 extends EventCityAction
{
    // WHY: City Actions that stay in play are once-per-Day per player. The framework's
    // Used flag is global — without playersUsed, a failed pressure by A locks B out.
    private array $playersUsed = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure with Finesse; add a city card and return this card to your Home");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        if (in_array($playerId, $this->playersUsed))
        {
            return false;
        }

        return true;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            if (in_array($event->playerId, $this->playersUsed))
            {
                throw new UserException($event->theah->game->translate("You have already used this Action today."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->playersUsed = [];
            $card = $this->getOwningCard($event->theah);
            if ($card != null)
            {
                $card->IsUpdated = true;
                $this->notifyUsedList($event->theah->game, $card->Id);
            }
        }

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;

            $game->globals->set(Game::PRESSURING_PLAYER, $event->playerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_FINESSE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($event->playerId, $performer->Id, $performer->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $playerId = $event->playerId;

            if ($event->success)
            {
                $location = $owner->Location;

                // Add the top card of the City Deck to this location.
                $topCards = $game->getCardsOnTopOfCityDeck(1);
                if (count($topCards) > 0)
                {
                    $topCard = array_values($topCards)[0];
                    $cityCardEvent = EventFactory::createCityCardAddedToLocationEvent((int)$topCard['id'], $location);
                    $event->theah->queueEvent($cityCardEvent);
                }

                // Put this card in the player's Home. Updating ControllerId before queueing
                // the move ensures game->moveCard() writes the right card_location_arg, and
                // EventCardMoved's notify carries the new controllerId so the client renders
                // it at the right home anchor.
                $owner->ControllerId = $playerId;
                $owner->IsUpdated = true;

                $moveEvent = EventFactory::createCardMovingEvent(
                    $playerId,
                    $owner->Id,
                    $location,
                    Game::LOCATION_PLAYER_HOME,
                    false,
                    $owner->Id,
                    $this->Id
                );
                $event->theah->queueEvent($moveEvent);

                $game->notify->all("message",
                    clienttranslate('${card_inject_code}: pressure succeeded. ${player_name} puts this card in their Home.'),
                    [
                        "card_inject_code" => $owner->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($playerId),
                    ]
                );
            }

            // WHY: Mark this player only, then clear the global Used flag set by central
            // confirm — otherwise a failed pressure greys the action out for every player.
            if (!in_array($playerId, $this->playersUsed))
            {
                $this->playersUsed[] = $playerId;
            }
            $this->notifyUsedList($game, $owner->Id);
            $this->setUsed($event->theah, false);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    private function notifyUsedList(Game $game, int $cardId): void
    {
        $game->notify->all("earlyMorningArrangementsUsedListUpdated", '', [
            'cardId' => $cardId,
            'usedList' => $this->getUsedListData($game),
        ]);
    }

    public function getUsedListData(Game $game): array
    {
        $list = [];
        foreach ($this->playersUsed as $playerId)
        {
            $list[] = [
                'playerId' => $playerId,
                'playerName' => $game->getPlayerNameById($playerId),
                'playerColor' => $game->getPlayerColorById($playerId),
            ];
        }
        return $list;
    }
}
