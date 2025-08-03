<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01067 extends Technique
{
    public bool $UseRiposteInstead;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Thrust or +1 Riposte if Musketeer at Location");
        $this->UseRiposteInstead = false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        //If there is more than one Musketeer at owner's location, switch to state where player can choose one to gain +1 Thrust or +1 Riposte.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $jeanUrbain = $this->getOwningCharacter($event->theah);
            $characters = $event->theah->getCharactersAtLocation($jeanUrbain->Location);
            $characters = array_filter($characters, fn($character) => 
                $character->Id != $jeanUrbain->Id && 
                $character->ControllerId == $jeanUrbain->ControllerId && 
                $character->hasTrait("Musketeer"));
            
            if (count($characters) > 0)
            {
                $transition = EventFactory::createTechniqueTransitionEvent($jeanUrbain->ControllerId, $jeanUrbain->Id, "01067", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            if ( ! $this->UseRiposteInstead)
            {
                $event->adversaryThreat += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds 1 Threat."), $this->Name);
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            if ($this->UseRiposteInstead)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds +1 Riposte."), $this->Name);
                $event->riposte += 1;
            }
            else
            {
                $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds +1 Thrust."), $this->Name);
                $event->thrust += 1;
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->UseRiposteInstead = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($id == 1)
        {
            $this->UseRiposteInstead = false;
            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses +1 Thrust for Jean Urbain\'s Technique.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);
        }
        else if ($id == 2)
        {
            $this->UseRiposteInstead = true;
            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses +1 Riposte for Jean Urbain\'s Technique.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);
        }
        $jean = $this->getOwningCharacter($game->theah);
        $jean->IsUpdated = true;

        $game->gamestate->nextState();
    }
}