<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05Robin extends Technique
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "+1 Thrust (combat card is Flourish or Stealth)";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY: "Your combat card" only exists inside a duel round, so this is never a challenge-time Technique.
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // WHY: The "If ..." clause sits before the bullet, so it is the availability gate (not a resolve-time
        // effect gate). No engage cost is printed, so the attachment is not engaged.
        foreach ($theah->getCombatCardsForCurrentRound() as $combatCard)
        {
            if ($combatCard !== null
                && $combatCard->ControllerId == $owner->ControllerId
                && ($combatCard->hasTrait("Flourish") || $combatCard->hasTrait("Stealth")))
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(
                "%s: Technique [%s] adds 1 Thrust.",
                $attachment !== null ? $attachment->getInjectCode() : $this->Name,
                $this->Name
            );
        }
    }
}
