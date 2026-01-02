<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01086 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage or Wound Adversary");
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id == $combatCard->Id)
        {
            $adversary = $theah->getDuelRoundOpponent();

            if ($adversary->hasTrait("Mercenary"))
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s reduces the cost of Maneuver by 1 because your Adversary is a Mercenary."), $owner->getInjectCode());
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            $owner = $this->getOwningCard($event->theah);
            if (! $adversary->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $event->adversaryId, $owner->Id, $this->Id);
                $event->theah->queueEvent($engageEvent);
            }
            else
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($event->adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($woundEvent);
            }
        }
    }
}