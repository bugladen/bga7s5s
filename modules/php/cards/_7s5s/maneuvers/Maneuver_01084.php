<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGetCostForManeuverFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01084 extends Maneuver
{
    public bool $IncreaseAdversaryThrust;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Parry, Draw Card, Adversary Bonus");
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

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelGetCostForManeuverFromHand && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $adversary = $event->theah->getCharacterById($event->adversaryId);

            if ($adversary->Engaged)
            {
                $event->discount += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the cost of Maneuver by 1 because your Adversary is engaged."), $owner->Name);
            }
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->IncreaseAdversaryThrust = true;
            $owner->IsUpdated = true;

            $game = $event->theah->game;
            $card = $game->playerDrawCard($event->playerId);
            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, $card, $game->translate("<strong>Master of the Valroux Style</strong> Maneuver effect"));
            $event->theah->queueEvent($addEvent);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = $owner->Name;
        }

        if ($event instanceof EventDuelCalculateCombatCardStats && $this->IncreaseAdversaryThrust)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $adversaryId = $event->theah->getDuelOpponentId($actor->Id);
            $adversary = $event->theah->getCharacterById($adversaryId);

            if ($adversary->ControllerId == $owner->ControllerId)
            {
                $event->thrust += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("<strong>%s</strong> increases the Adversary's Thrust by %d"), $owner->Name, 1);
                $this->IncreaseAdversaryThrust = false;
                $owner->IsUpdated = true;
            }
        }
    }
}