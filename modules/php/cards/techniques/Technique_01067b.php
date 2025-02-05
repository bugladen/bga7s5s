<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01067b extends Technique
{
    public bool $UseParryInstead = false;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Jean Urbain: +1 Thrust or Riposte";
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        //When activated, if there is more than one Musketeer as owner location, 
        //switch to state where player can choose one to gain +1 Thrust or +1 Riposte.
        if ($event instanceof EventResolveTechnique && $this->Active)
        {
            $owner = $event->theah->getCharacterById($this->OwnerId);
            $characters = $event->theah->getCharactersAtLocation($owner->Location);
            $characters = array_filter($characters, 
                fn($character) => $character->Id != $owner->Id && $character->ControllerId == $owner->ControllerId && in_array("Musketeer", $character->Traits));
            if (count($characters) > 0)
            {
                $transition = $event->theah->createEvent(Events::Transition);
                if ($transition instanceof EventTransition) {
                    $transition->playerId = $owner->ControllerId;
                    $transition->transition = '01067b';
                }
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventGenerateThreat && $this->Active)
        {
            $owner = $event->theah->getCharacterById($this->OwnerId);
            if ($owner && $owner->Id == $event->challenger->Id)
            {
                $characters = $event->theah->getCharactersAtLocation($owner->Location);
                $characters = array_filter($characters, 
                    fn($character) => $character->Id != $owner->Id && $character->ControllerId == $owner->ControllerId && in_array("Musketeer", $character->Traits));
                if ( ! $this->UseParryInstead)
                {
                    $event->threat += 1;
                    $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Threat.");
                }
            }
        }
    }
}