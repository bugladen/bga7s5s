<?php

/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See https://en.doc.boardgamearena.com/Studio for more information.
 */

 namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

 class Action_01143 extends SchemeCityAction
 {
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claim Location, You Win Ties");
        $this->RequiresPerformerSelected = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $game = $event->theah->game;
            $scheme = $this->getOwningCard($event->theah);

            $game->globals->set(Game::CLAIMING_PLAYER, $scheme->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::CONTEMPT_AND_HATRED_PRESSURE_TYPE);

            $game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code}: ${player_name} has used the [${action}] Action.'), [
                'i18n' => ['action'],
                'player_name' => $game->getPlayerNameById($scheme->ControllerId),
                'action' => $this->Name,
                'scheme_inject_code' => $scheme->getInjectCode(),
            ]);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            $engageEvent = EventFactory::createCardEngagedEvent($scheme->ControllerId, $performerId);
            $event->theah->queueEvent($engageEvent);

            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($scheme->ControllerId, $performerId, $performer->Location, [Game::STAT_INFLUENCE]);
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($scheme->ControllerId, $scheme->Id, "01143", $this->Id);
            $event->theah->queueEvent($transitionEvent);

            $this->setUsed($event->theah, true);
        }
    }

 }
