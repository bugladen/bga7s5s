<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01033 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Adversary Home");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $actor = $theah->getDuelRoundActor();
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);

        if ($theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        return $actor->ModifiedInfluence > $adversary->ModifiedInfluence;
    }

    // WHY: Same shape as Maneuver_01164 — keep the maneuver button visible when the
    // adversary is Harpooned so the player can attempt it and see why it failed.
    // Checks the adversary (this maneuver moves them home), not the actor.
    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary !== null
                && $event->theah->game->globals->get(Game::IN_DUEL, false)
                && $adversary->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException($event->theah->game->translate("This character is Harpooned and cannot move for the remainder of the duel."));
            }

            // WHY: Same activate-time shape as Harpoon — this maneuver moves the adversary
            // Home, which Lodestone (_03065) blocks. Fail at activate so the player sees
            // why instead of a silently-dropped queued move.
            if ($adversary !== null
                && $adversary->hasCondition(Game::LODESTONE_CONDITION))
            {
                throw new UserException($event->theah->game->translate("Lodestone prevents opponents from moving this character Home."));
            }
        }
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
            $moveEvent = EventFactory::createCardMovingEvent($adversary->ControllerId, $adversary->Id, $adversary->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
            $event->theah->queueEvent($moveEvent);
        }

        // EventManeuverCanceled handler not needed
    }
}
