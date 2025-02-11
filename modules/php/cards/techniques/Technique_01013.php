<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01013 extends Technique
{
    public bool $UseThrust;
    public bool $AllowEffect;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Vissenta Scarpa: Add Thrust or Parry";
        $this->UseThrust = false;
        $this->AllowEffect = false;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        switch(true)
        {
            case $event instanceof EventResolveTechnique && $event->techniqueId == $this->Id:
                $handler = function(EventResolveTechnique $event) {
                    $vissenta = $event->theah->getCharacterById($this->OwnerId);
                    $adversary = $event->theah->getCharacterById($event->adversaryId);
        
                    if ($vissenta->Wounds >= $adversary->Wounds)
                    {
                        $this->AllowEffect = true;
                        $vissenta->IsUpdated = true;
        
                        $event->theah->game->globals->set(Game::CHOSEN_TECHNIQUE, $this->Id);
        
                        $transition = $event->theah->createEvent(Events::Transition);
                        if ($transition instanceof EventTransition) {
                            $transition->playerId = $vissenta->ControllerId;
                            $transition->priority = Event::HIGH_PRIORITY;
                            $transition->transition = '01013';
                        }
                        $event->theah->queueEvent($transition);
        
                        $event->theah->game->notifyPlayer($vissenta->ControllerId, 'message', clienttranslate('You are activating technique ${technique_name}.'), [
                            'i18n' => ['technique_name'],
                            'technique_name' => $this->Name,
                        ]);
                    }
                    else 
                    {
                        $event->theah->game->notifyPlayer($vissenta->ControllerId, 'message', clienttranslate('Vissenta must have equal or more wounds than her adversary to use her technique.'), []);
                    }
                };
                $handler($event);
                break;

            case $event instanceof EventGenerateChallengeThreat && $this->Active && $this->AllowEffect:
                $handler = function(EventGenerateChallengeThreat $event) {
                    if ($this->UseThrust)
                    {
                        $event->threat += 1;
                        $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Threat.");
                    }
                };
                $handler($event);
                break;

            case $event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id && $this->AllowEffect:
                $handler = function(EventDuelCalculateTechniqueValues $event) {
                    if ($this->UseThrust)
                    {
                        $event->thrust += 1;
                        $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Thrust.");
                    }
                    else
                    {
                        $event->parry += 1;
                        $event->explanations[] = clienttranslate("Technique [{$this->Name}] adds 1 Parry.");
                    }        
                };
                $handler($event);
                break;
        }
    }

    public function cleanup()
    {
        parent::cleanup();
        $this->UseThrust = false;
        $this->AllowEffect = false;
    }
}