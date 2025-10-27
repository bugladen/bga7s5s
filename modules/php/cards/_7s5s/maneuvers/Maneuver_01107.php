<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01107 extends Maneuver
{
    private bool $WillDieFromWound;
    private bool $AdversaryId;
    private string $AdversaryLocation;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Adversary");
        $this->WillDieFromWound = false;
        $this->AdversaryId = 0;
        $this->AdversaryLocation = "";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $woundsTaken = $theah->duelParticipantWoundsTaken($actor->Id);
        return ($woundsTaken > 0);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $adversaryId = $event->theah->getDuelOpponentId($actor->Id);
            $adversary = $event->theah->getCharacterById($adversaryId);

            $owner = $this->getOwningCard($event->theah);
            $this->WillDieFromWound = ($adversary->ModifiedResolve - $adversary->Wounds == 1);
            $this->AdversaryId = $adversaryId;
            $this->AdversaryLocation = $adversary->Location;
            $owner->IsUpdated = true;

            $woundEvent = EventFactory::createCharacterWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);
        }

        if ($event instanceof EventCharacterDestroyed && $this->WillDieFromWound && $this->AdversaryId == $event->characterId)
        {
            $actor = $event->theah->getDuelRoundActor();            

            $claimEvent = EventFactory::createLocationClaimedEvent($actor->ControllerId, $actor->Id, $this->AdversaryLocation);
            $event->theah->queueEvent($claimEvent);
        }

        if ($event instanceof EventDuelEndOfRound && $this->WillDieFromWound)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->WillDieFromWound = false;
            $this->AdversaryId = 0;
            $this->AdversaryLocation = "";
            $owner->IsUpdated = true;
        }
    }
}