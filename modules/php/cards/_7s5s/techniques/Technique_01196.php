<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01196 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Riposte");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        return $inDuel;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $adversary = $event->theah->getCharacterById($event->adversaryId);

            if ($actor->ModifiedCombat + $actor->ModifiedInfluence >= $adversary->ModifiedCombat + $adversary->ModifiedInfluence)
            {
                $event->riposte += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("Technique: +1 Riposte from her technique because Angeline Dèmone has more Combat and Influence than %s."), $adversary->Name);
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner->Id == $event->actorId)
            {
                $actor = $event->theah->getCharacterById($event->actorId);
                $adversary = $event->theah->getCharacterById($event->adversaryId);

                if ($actor->ModifiedCombat + $actor->ModifiedInfluence >= $adversary->ModifiedCombat + $adversary->ModifiedInfluence)
                {
                    $event->adversaryThreat += 1;
                    $event->explanations[] = sprintf($event->theah->game->translate("Technique: +1 Threat from her technique because Angeline Dèmone has more Combat and Influence than %s."), $adversary->Name);
                }
            }
        }

    }
}