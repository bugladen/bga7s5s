<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01082 extends Maneuver
{
    private int $FinalStrikeParticipantId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Final Strike: +2 Threat and Gain Lethal");

        $this->FinalStrikeParticipantId = 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->FinalStrikeParticipantId = $event->theah->getDuelOpponentId($event->adversaryId);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->FinalStrikeParticipantId)
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            if ($inDuel)
            {
                $challengerId = $event->theah->getDuelChallengerId();
                $defenderId = $event->theah->getDuelDefenderId();

                $character = $event->theah->getCardById($this->FinalStrikeParticipantId);
                $adversaryId = $event->theah->getDuelOpponentId($this->FinalStrikeParticipantId);
                $adversary = $event->theah->getCardById($adversaryId);

                $challengerThreatAdded = $this->FinalStrikeParticipantId == $challengerId ? 0 : 2;
                $defenderThreatAdded = $this->FinalStrikeParticipantId == $defenderId ? 0 : 2;

                $challengerThreatIsLethal = $this->FinalStrikeParticipantId == $challengerId ? null : true;
                $defenderThreatIsLethal = $this->FinalStrikeParticipantId == $defenderId ? null : true;

                $threatModifiedEvent = EventFactory::createThreatModifiedEvent($challengerThreatAdded, $defenderThreatAdded, $challengerThreatIsLethal, $defenderThreatIsLethal);
                $event->theah->queueEvent($threatModifiedEvent);

                $owner = $this->getOwningCard($game->theah);
                $game->notifyAllPlayers("message", clienttranslate('${maneuver_inject_code}: ${character_inject_code} used Final Strike to add 2 Threat to ${adversary_inject_code} and gain Lethal.'), [
                    "maneuver_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                    "adversary_inject_code" => $adversary->getInjectCode(),
                    "adversary_name" => $adversary->Name,
                ]);
            }
        }
    }
}