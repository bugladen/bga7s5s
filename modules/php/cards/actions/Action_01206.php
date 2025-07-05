<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01206 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure Location to Claim");        
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ( ! $theah->cardInCity($owner))
        {
            return false;
        }

        $coat = $this->getOwningAttachment($theah);
        return ! $coat->Engaged;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $performer = $this->getOwningCharacter($event->theah);
            $coat = $this->getOwningAttachment($event->theah);

            $game = $event->theah->game;
            $game->globals->set(Game::CLAIMING_PLAYER, $performer->ControllerId);
            $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
            $game->globals->set(Game::CLAIM_TYPE, Game::CAPTAINS_COAT_CLAIM_TYPE);

            $this->setUsed($event->theah, true);

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${player_name} is using Captain\'s Coat Action to claim a location.'), [
                'player_name' => $performer->Name,
            ]);

            $engageEvent = EventFactory::createCardEngagedEvent($coat->ControllerId, $coat->Id);
            $event->theah->queueEvent($engageEvent);

            $pressureTypes = $event->theah->getPressureTypesForClaim($performer);
            $claimEvent = EventFactory::createClaimOccuringEvent($performer->ControllerId, $performer->Id, $performer->Location, $pressureTypes);
            $event->theah->queueEvent($claimEvent);

            $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $performer->Id, "01206");
            $event->theah->queueEvent($transitionEvent);
        }
    }
}