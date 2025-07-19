<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01075 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Pressure Location with Influence");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $owner = $this->getOwningCharacter($theah);

        if ($owner->Engaged)
        {
            return false;
        }

        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);

            $game->globals->set(Game::CLAIMING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::CLAIM_TYPE, Game::TABARD_CLAIM_TYPE);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the [${action}] Action from <strong>${owner_name}</strong>'), [
                'i18n' => ['action'],
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'action' => $this->Name,
                'owner_name' => $owner->Name,
            ]);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01075", $this->Id);
            $event->theah->queueEvent($transitionEvent);

        }
    }
}