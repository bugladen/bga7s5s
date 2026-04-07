<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01084 extends Maneuver
{
    public bool $IncreaseAdversaryThrust;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte, Draw Card, Adversary Bonus");
        $this->IncreaseAdversaryThrust = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor->HasTrait('Duelist');
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id == $combatCard->Id)
        {
            $adversary = $theah->getDuelRoundOpponent();
            if ($adversary->Engaged)
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s reduces the cost of Maneuver by 1 because your Adversary is engaged."), $owner->getInjectCode());
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->IncreaseAdversaryThrust = true;
            $owner->IsUpdated = true;

            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, sprintf($game->translate("%s Maneuver effect"), $owner->getInjectCode()));
            $event->theah->queueEvent($addEvent);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());
        }

        if ($event instanceof EventDuelCalculateCombatCardStats && $this->IncreaseAdversaryThrust)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $adversaryId = $event->theah->getDuelOpponentId($actor->Id);
            $adversary = $event->theah->getCharacterById($adversaryId);

            //Confusing, but if you played this last round, you are the adversary for this round, and your opponent is the actor
            if ($adversary->ControllerId == $owner->ControllerId)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("%s increases the Adversary's Thrust by %d"), $owner->getInjectCode(), 1);
                $event->addThrust(1);
                $this->IncreaseAdversaryThrust = false;
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEnd && $this->IncreaseAdversaryThrust)
        {
            $this->IncreaseAdversaryThrust = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }
}