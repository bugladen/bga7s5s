<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01135 extends Maneuver
{
    private bool $IsActive;
    private bool $ReduceThrustNextRound;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+2 Parry, or Wound Adversary and Give -2 Thrust Next Round");
        $this->IsActive = false;
        $this->ReduceThrustNextRound = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        return $inDuel;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01135", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id && $this->IsActive && ! $this->ReduceThrustNextRound) 
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry += 2;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Parry."), $owner->getInjectCode());
        }

        if ($event instanceof EventDuelCalculateCombatCardStats && $this->IsActive && $this->ReduceThrustNextRound)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            if ($owner->ControllerId != $actor->ControllerId)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the Adversary's Thrust by 2."), $owner->getInjectCode());
                $event->removeThrust(2);
            }
        }

        //Deactive at the end of the next players round
        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {            
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            if ($owner->ControllerId != $actor->ControllerId)
            {
                $this->IsActive = false;
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->IsActive = false;
            $this->ReduceThrustNextRound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->IsActive = false;
            $this->ReduceThrustNextRound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01135)
        {
            $owner = $this->getOwningCard($game->theah);
            if ($id == 1)
            {
                $this->ReduceThrustNextRound = false;
            }
            else if ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${card_inject_code} activated: ${character_name} is wounded and their Thrust is reduced by 2 next round.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "character_name" => $game->theah->getDuelRoundOpponent()->Name,
                ]);
                $adversary = $game->theah->getDuelRoundOpponent();
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
                
                $this->ReduceThrustNextRound = true;
            }

            $this->IsActive = true;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState();
    }
}