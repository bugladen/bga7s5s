<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02056 extends Technique
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('+1 Parry or +1 Riposte (3+ Finesse)');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah)) {
            return false;
        }

        return (bool) $theah->game->globals->get(Game::IN_DUEL, false);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed because this technique is always available
        
        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $character = $this->getOwningCharacter($event->theah);
            if ($character === null) {
                return;
            }

            if ($character->ModifiedFinesse >= 3) {
                $event->riposte += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('%s: Technique [%s] adds +1 Riposte (3+ Finesse).'),
                    $owner->getInjectCode(),
                    $this->Name
                );
            } else {
                $event->parry += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('%s: Technique [%s] adds +1 Parry.'),
                    $owner->getInjectCode(),
                    $this->Name
                );
            }
        }
    }
}
