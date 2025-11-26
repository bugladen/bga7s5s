<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01139 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Spend a Reknown, Take Two More Actions");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return $theah->game->getPlayerReknown($playerId) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;

            $owner = $this->getOwningCard($event->theah);
            if ($owner instanceof _01139)
            {
                $owner->goToLocker = true;
                $owner->IsUpdated = true;
            }

            $reknownEvent = EventFactory::createPlayerLosesReknownEvent($event->playerId, 1);
            $event->theah->queueEvent($reknownEvent);

            $game->globals->set(Game::EXTRA_ACTIONS, 2);

            $game->notify->all("extraActions", clienttranslate('${player_name} NOW HAS TWO EXTRA ACTIONS'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId)
            ]);
        }
    }

}