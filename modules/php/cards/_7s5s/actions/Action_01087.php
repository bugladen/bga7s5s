<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01087 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Your Non-Mercenary Performer");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $performers = $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_filter($performers, fn($performer) => ! $performer->hasTrait("Mercenary") && $performer->Engaged);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($performer) => ! $performer->hasTrait("Mercenary") && $performer->Engaged));
        return $performers;
    }

    public function handleEvent(Event $event): void
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $owner = $this->getOwningCard($event->theah);
            $engardeEvent = EventFactory::createCardEngardedEvent($performer->ControllerId, $performer->Id, $owner->Id);
            $event->theah->queueEvent($engardeEvent);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the [${action}] Action from ${owner_inject_code}'), [
                'i18n' => ['action'],
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'action' => $this->Name,
                'owner_inject_code' => $owner->getInjectCode(),
            ]);
        }
    }
}