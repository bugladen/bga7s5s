<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelAttemptGamble;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02037 extends Technique
{
    public bool $CancelAdversaryGamble;

    public int $BlockedAdversaryCharacterId;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Block adversary gamble');
        $this->CancelAdversaryGamble = false;
        $this->BlockedAdversaryCharacterId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);

        return $inDuel;
    }

    private function clearGambleLock(Theah $theah): void
    {
        $this->CancelAdversaryGamble = false;
        $this->BlockedAdversaryCharacterId = 0;
        $owner = $this->getOwningCard($theah);
        if ($owner)
            $owner->IsUpdated = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->CancelAdversaryGamble = true;
            $this->BlockedAdversaryCharacterId = $event->adversaryId;
            $owner = $this->getOwningCard($event->theah);
            if ($owner)
                $owner->IsUpdated = true;
        }

        $owningCharacter = $this->getOwningCharacter($event->theah);
        if ($event instanceof EventDuelNewRound && $owningCharacter && $owningCharacter->Id == $event->actorId && $this->CancelAdversaryGamble)
        {
            $this->clearGambleLock($event->theah);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
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

        if ($event instanceof EventDuelAttemptGamble && $this->CancelAdversaryGamble && $event->actorId == $this->BlockedAdversaryCharacterId)
        {
            throw new UserException($event->theah->game->translate("Mysta's Technique prevents the adversary from gambling this round."));
        }
    }
}
