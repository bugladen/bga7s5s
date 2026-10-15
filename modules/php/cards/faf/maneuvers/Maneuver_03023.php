<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03023 extends Maneuver
{
    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Carry Threat Forward");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // Gambling Maneuver: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        return true;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // WHY: Suppress leftover-threat conversion at queue-time (eventCheck), not in
        // handleEvent. Same trap as Breastplate _01153: reactions that clone+cancel
        // EventCharacterBeingWounded during handleEvent (Reaction_01181 and kin) can
        // capture wounds before this maneuver runs, then re-queue the clone after we
        // log "carries over" and zero the original — character still takes the wounds.
        // eventCheck runs in queueEvent before serialize, so clones see wounds=0.
        if (! ($event instanceof EventCharacterBeingWounded) || ! $this->IsActive || $event->canceled)
        {
            return;
        }

        $theah = $event->theah;
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $event->characterId != $actor->Id)
        {
            return;
        }

        // The end-of-round threat-to-wound conversion is the only wound event whose
        // characterId is the duel actor and whose sourceId is the adversary.
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        if ($event->sourceId != $adversaryId)
        {
            return;
        }

        $adversary = $theah->getCharacterById($adversaryId);

        // "Adversary absent" means not at the actor's location (destroyed or moved).
        // If absent, the maneuver does nothing — wounds resolve normally.
        if ($adversary === null
            || $theah->game->characterIsInDiscardOrLocker($adversary)
            || $adversary->Location != $actor->Location)
        {
            return;
        }

        $carryOver = $event->wounds;
        if ($carryOver <= 0)
        {
            return;
        }

        $event->wounds = 0;

        $game = $theah->game;
        $challengerId = $theah->getDuelChallengerId();
        if ($actor->Id == $challengerId)
        {
            $pending = $game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0);
            $game->globals->set(Game::PENDING_CHALLENGER_THREAT, $pending + $carryOver);
        }
        else
        {
            $pending = $game->globals->get(Game::PENDING_DEFENDER_THREAT, 0);
            $game->globals->set(Game::PENDING_DEFENDER_THREAT, $pending + $carryOver);
        }

        $owner = $this->getOwningCard($theah);
        $game->notify->all("message", clienttranslate('${card_inject_code}: Threat is not converted to wounds; ${carryOver} threat carries over to the next round.'), [
            "card_inject_code" => $owner->getInjectCode(),
            "carryOver" => $carryOver,
        ]);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $this->IsActive = true;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {
            $this->IsActive = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }
}
