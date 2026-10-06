<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Maneuver_01129 extends Maneuver
{
    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maneuvers and Techniques not usable for the rest of the Duel");
    }

    /**
     * WHY: Rest-of-duel ban must survive Miyato/Ota (02043a) sending the Risk to The
     * Locker (and removing the maneuver clone at NewRound). Locker cards are omitted
     * from buildCity, so instance eventCheck never runs. Global + Theah::eventCheck
     * mirrors Mireli (01135) / Unravel (_04010). Do NOT put locker into buildCity.
     *
     * @return array{sourceInjectCode:string,maneuverId:string}|null
     */
    public static function getLock(Game $game): ?array
    {
        $lock = $game->globals->get(Game::BORETS_MANEUVER_TECHNIQUE_LOCK, null);
        return is_array($lock) ? $lock : null;
    }

    public static function armLock(Game $game, string $sourceInjectCode, string $maneuverId): void
    {
        $game->globals->set(Game::BORETS_MANEUVER_TECHNIQUE_LOCK, [
            'sourceInjectCode' => $sourceInjectCode,
            'maneuverId' => $maneuverId,
        ]);
    }

    public static function assertNotLocked(Event $event): void
    {
        if (! ($event instanceof EventManeuverActivated || $event instanceof EventTechniqueActivated))
        {
            return;
        }

        $lock = self::getLock($event->theah->game);
        if ($lock === null)
        {
            return;
        }

        $source = $lock['sourceInjectCode'] ?? '';
        $message = $event instanceof EventManeuverActivated
            ? $event->theah->game->translate("You cannot activate Maneuvers while %s is active.")
            : $event->theah->game->translate("You cannot activate Techniques while %s is active.");
        throw new \BgaUserException(sprintf($message, $source));
    }

    public static function clearLockForManeuver(Game $game, string $maneuverId): void
    {
        $lock = self::getLock($game);
        if ($lock === null)
        {
            return;
        }
        if (($lock['maneuverId'] ?? '') !== $maneuverId)
        {
            return;
        }
        $game->globals->delete(Game::BORETS_MANEUVER_TECHNIQUE_LOCK);
    }

    public static function clearLock(Game $game): void
    {
        $game->globals->delete(Game::BORETS_MANEUVER_TECHNIQUE_LOCK);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->IsActive = true;
            $owner->IsUpdated = true;
            self::armLock($event->theah->game, $owner->getInjectCode(), $this->Id);
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            self::clearLockForManeuver($event->theah->game, $this->Id);
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd)
        {
            self::clearLock($event->theah->game);
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }
}
