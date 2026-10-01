<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03069b extends Maneuver_03069a
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Riposte; Swap Participant with Other Character");
    }

    // WHY: Parent (a) requires another character at the location for the swap.
    // Gambling mode still grants +1 Riposte when alone; only the swap half is
    // skipped. Gate on Maneuver base + DUEL_GAMBLED, not a's location count.
    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! Maneuver::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        return (bool) $theah->game->globals->get(Game::DUEL_GAMBLED, false);
    }

    // WHY: Parent's Harpoon gate blocks the whole maneuver. Gambling mode still grants
    // +1 Riposte when Harpooned; only the swap half is illegal. Skip activate-time throw
    // so calc can run; Resolve below skips the chooser when Harpooned or alone.
    public function eventCheck(Event $event)
    {
        Maneuver::eventCheck($event);
    }

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            $harpooned = $actor !== null
                && $event->theah->game->globals->get(Game::IN_DUEL, false)
                && $actor->hasCondition(Game::HARPOON_CONDITION);
            $noSwapTargets = $actor !== null
                && count($this->getOtherCharactersAtDuelLocation($event->theah, $actor->ControllerId)) === 0;

            // WHY: Do not queue the shared 03069 chooser — player would be stuck
            // (no Back on that state / empty ids). Riposte still applies via Calculate.
            if ($harpooned || $noSwapTargets)
            {
                Maneuver::handleEvent($event);

                $owner = $this->getOwningCard($event->theah);
                if ($harpooned)
                {
                    $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: ${actor_inject_code} is Harpooned and cannot be swapped. +1[Riposte] still applies.'), [
                        "card_inject_code" => $owner->getInjectCode(),
                        "actor_inject_code" => $actor->getInjectCode(),
                    ]);
                }
                else
                {
                    $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: No other character to swap with. +1[Riposte] still applies.'), [
                        "card_inject_code" => $owner->getInjectCode(),
                    ]);
                }
            }
            else
            {
                parent::handleEvent($event);
            }
        }
        else
        {
            parent::handleEvent($event);
        }

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s adds 1 Riposte."),
                $owner->getInjectCode()
            );
        }
    }
}
