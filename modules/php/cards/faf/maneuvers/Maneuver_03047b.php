<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelAttemptGamble;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03047b extends Maneuver
{
    public bool $CancelAdversaryGamble;

    public int $BlockedAdversaryCharacterId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Adversary Cannot Gamble Next Round");
        $this->CancelAdversaryGamble = false;
        $this->BlockedAdversaryCharacterId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor->hasTrait("Duelist");
    }

    private function clearGambleLock(Theah $theah): void
    {
        $this->CancelAdversaryGamble = false;
        $this->BlockedAdversaryCharacterId = 0;
        $owner = $this->getOwningCard($theah);
        if ($owner)
        {
            $owner->IsUpdated = true;
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $adversary = $event->theah->getDuelRoundOpponent();
            $this->CancelAdversaryGamble = true;
            $this->BlockedAdversaryCharacterId = $adversary->Id;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        // WHY: Mirror Technique_02037 — lock lasts through the adversary's next round
        // and clears when our controller becomes the round actor again. Risk Maneuvers
        // have no owning character, so key off ControllerId instead of character Id.
        $owner = $this->getOwningCard($event->theah);
        if ($event instanceof EventDuelNewRound
            && $this->CancelAdversaryGamble
            && $owner
            && $event->theah->getCharacterById($event->actorId)->ControllerId == $owner->ControllerId)
        {
            $this->clearGambleLock($event->theah);
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->clearGambleLock($event->theah);
        }

        if ($event instanceof EventDuelEnd && $this->CancelAdversaryGamble)
        {
            $this->clearGambleLock($event->theah);
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventDuelAttemptGamble
            && $this->CancelAdversaryGamble
            && $event->actorId == $this->BlockedAdversaryCharacterId)
        {
            throw new UserException($event->theah->game->translate("Proper Drama's Maneuver prevents the adversary from gambling this round."));
        }
    }
}
