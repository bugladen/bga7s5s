<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02021 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry if Anghos has more Influence than the adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY: "adversary" is in the cost (before •) — duel-only, not a challenge
        // Technique. Same rule as Technique_02023 / 01066.
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $anghos = $this->getOwningCharacter($theah);
        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        return $anghos->Influence > $adversary->Influence;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds 1 Parry because Anghos has more Influence than the adversary."), $owner->getInjectCode(), $this->Name);
        }
    }
}
