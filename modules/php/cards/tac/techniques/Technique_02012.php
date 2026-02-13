<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02012 extends Technique
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Wound Turais. Remove all Threat from him.');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        return $owner->hasTrait('Berserker') && $owner->Wounds + 1 < $owner->ModifiedResolve;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $event->theah->queueEvent(EventFactory::createCharacterBeingWoundedEvent($owner->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id));
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $threat = $event->theah->getCurrentDuelThreat($owner->Id);
            $event->parry += $threat;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] removes %d Threat."), $owner->getInjectCode(), $this->Name, $threat);
        }
    }
}