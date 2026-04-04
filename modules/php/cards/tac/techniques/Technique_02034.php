<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02034 extends Technique
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('+1 Parry or +1 Riposte (Aldana in dueling line)');
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

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) {
            $torvo = $this->getOwningCharacter($event->theah);
            if ($torvo === null) {
                return;
            }

            $cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $torvo->ControllerId);
            $hasAldana = false;
            foreach ($cards as $card) {
                if ($card->hasTrait(clienttranslate('Aldana'))) {
                    $hasAldana = true;
                    break;
                }
            }

            if ($hasAldana) {
                $event->riposte += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('Technique [%s] adds +1 Riposte (an Aldana card is in Torvo\'s dueling line).'),
                    $this->Name
                );
            } else {
                $event->parry += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate('Technique [%s] adds +1 Parry.'),
                    $this->Name
                );
            }
        }
    }
}
