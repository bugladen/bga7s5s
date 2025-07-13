<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Technique_01067 extends Technique
{
    public bool $UseParryInstead;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Thrust or +1 Riposte");
        $this->UseParryInstead = false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        //If there is more than one Musketeer as owner location, switch to state where player can choose one to gain +1 Thrust or +1 Riposte.
        if ($event instanceof EventTechniqueActivated && $event->techniqueId == $this->Id)
        {
            $jeanUrbain = $this->getOwningCharacter($event->theah);
            $characters = $event->theah->getCharactersAtLocation($jeanUrbain->Location);
            $characters = array_filter($characters, fn($character) => 
                $character->Id != $jeanUrbain->Id && 
                $character->ControllerId == $jeanUrbain->ControllerId && 
                $character->hasTrait("Musketeer"));
            if (count($characters) > 0)
            {
                $transition = EventFactory::createTechniqueTransitionEvent($jeanUrbain->ControllerId, $jeanUrbain->Id, "01067b", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $jeanUrbain = $event->theah->getCharacterById($this->OwnerId);
            if ($jeanUrbain && $jeanUrbain->Id == $event->actorId)
            {
                $characters = $event->theah->getCharactersAtLocation($jeanUrbain->Location);
                $characters = array_filter($characters, fn($character) => 
                    $character->Id != $jeanUrbain->Id && 
                    $character->ControllerId == $jeanUrbain->ControllerId && 
                    $character->hasTrait("Musketeer"));
                if ( ! $this->UseParryInstead)
                {
                    $event->adversaryThreat += 1;
                    $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds 1 Threat."), $this->Name);
                }
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->UseParryInstead = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($id == 1)
        {
            $this->UseParryInstead = false;
            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses +1 Thrust for Jean Urbain\'s Technique.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);
        }
        else if ($id == 2)
        {
            $this->UseParryInstead = true;
            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses +1 Riposte for Jean Urbain\'s Technique.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);
        }
        $jean = $this->getOwningCharacter($game->theah);
        $jean->IsUpdated = true;

        $game->gamestate->nextState();
    }
}