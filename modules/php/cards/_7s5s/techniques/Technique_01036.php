<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_01036 extends Technique
{
    private bool $MoveDaniela;
    private string $MoveLocation;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move to Adjacent Location");
        $this->ResetOnDuelEnd = false;
        $this->ResetOnDayEnd = true;

        $this->MoveDaniela = false;
        $this->MoveLocation = "";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event); 

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01036", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventDuelEndOfRound && $this->MoveDaniela)
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('<strong>Daniella Dietrich</strong> Technique triggers and she moves to ${location}.'), [
                "location" => $this->MoveLocation,
            ]);

            $daniela = $this->getOwningCharacter($event->theah);
            $moveEvent = EventFactory::createCardMovedEvent($daniela->ControllerId, $daniela->Id, $daniela->Location, $this->MoveLocation, $engage = false, $daniela->Id);
            $event->theah->queueEvent($moveEvent);

            $this->MoveDaniela = false;
            $this->MoveLocation = "";
            $daniela->IsUpdated = true;
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01036)
        {
            $daniela = $this->getOwningCharacter($game->theah);
            $args["locationIds"] = $game->theah->getAdjacentCityLocations($daniela->Location, $includeHome = false);
   
        }

        return $args;
    }

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01036)
        {
            $location = $ids[0];
            $daniela = $this->getOwningCharacter($game->theah);
    
            $locations = $game->theah->getAdjacentCityLocations($daniela->Location, $includeHome = false);
            if (! in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate('Location is not adjacent to %s.'), $daniela->Name));
            }
    
            $this->MoveDaniela = true;
            $this->MoveLocation = $location;
            $game->updateCardObjectInDb($daniela);

            $game->gamestate->nextState();
        }
    }
}