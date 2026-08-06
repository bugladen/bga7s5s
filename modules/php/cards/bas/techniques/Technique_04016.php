<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04016 extends Technique
{
    // WHY: Public so IsUpdated persistence on the attachment keeps the deferred
    // EndOfRound threat effect across the duel round resolve cycle.
    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("At end of your round, each participant gains a threat");
        $this->IsActive = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        // Gambling Technique: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $this->IsActive = true;
            if ($attachment !== null)
            {
                $attachment->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $attachment = $this->getOwningCard($event->theah);

            // WHY: "your round" = the equipped character's round as actor, not any EndOfRound.
            // Only clear here when we fire — cancel / DuelEnd still wipe a stranded IsActive.
            if ($owner !== null && $event->actorId == $owner->Id)
            {
                $event->theah->game->notify->all("message", clienttranslate('${technique_inject_code}: each participant gains a threat.'), [
                    "technique_inject_code" => $attachment !== null ? $attachment->getInjectCode() : $this->Name,
                ]);

                $threatEvent = EventFactory::createThreatModifiedEvent(1, 1);
                $event->theah->queueEvent($threatEvent);

                $this->IsActive = false;
                if ($attachment !== null)
                {
                    $attachment->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->IsActive = false;
            $attachment = $this->getOwningCard($event->theah);
            if ($attachment !== null)
            {
                $attachment->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuelEnd && $this->IsActive)
        {
            $this->IsActive = false;
            $attachment = $this->getOwningCard($event->theah);
            if ($attachment !== null)
            {
                $attachment->IsUpdated = true;
            }
        }
    }
}
