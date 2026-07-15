<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03050 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Riposte (combat card is Flourish or Sorcery)");
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

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // "If Daichi's combat card is a Flourish or Sorcery"
        $combatCards = $theah->getCombatCardsForCurrentRound();
        foreach ($combatCards as $combatCard)
        {
            if ($combatCard->ControllerId == $owner->ControllerId
                && ($combatCard->hasTrait("Flourish") || $combatCard->hasTrait("Sorcery")))
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
            $owner = $this->getOwningCharacter($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Riposte."), $owner->getInjectCode(), $this->Name);
            $this->setUsed($event->theah, true);
        }
    }
}
