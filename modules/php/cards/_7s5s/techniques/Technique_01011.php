<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01011 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Thrust for each OTHER Red Hand at same location");
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $characters = $event->theah->getCharactersAtLocation($actor->Location);
            $redHands = array_values(array_filter($characters, fn($character) => $character->Id != $actor->Id && $character->ControllerId == $actor->ControllerId && $character->hasTrait("Red Hand")));

            $event->adversaryThreat += count($redHands);
            $event->explanations[] = $event->theah->game->translate("+1 Threat for each OTHER Red Hand at same location");
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $characters = $event->theah->getCharactersAtLocation($actor->Location);
            $redHands = array_values(array_filter($characters, fn($character) => $character->Id != $actor->Id && $character->ControllerId == $actor->ControllerId && $character->hasTrait("Red Hand")));

            $event->thrust += count($redHands);
            $event->explanations[] = $event->theah->game->translate("+1 Thrust for each OTHER Red Hand at same location");
        }
    }
}