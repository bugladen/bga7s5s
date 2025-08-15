<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01072 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Réputation Méritée");
        $this->Image = "img/cards/7s5s/072.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 72;

        $this->Faction = "Montaigne";
        $this->Initiative = 62;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Camaraderie", 
            "Honor",
        ];

        $this->Actions = [
            new Action_01072(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a city location with no Reknown to place reknown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01072");
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01072)
        {
            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($location) => $location->Reknown == 0);
            if (count($locations) > 0)
            {
                throw new \BgaUserException($game->translate("There are locations with no Reknown."));
            }

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01072)
        {            
            $location = $ids[0];     
            
            $loc = $game->theah->getCityLocation($location);
            if ($loc->Reknown > 0)
            {
                throw new \BgaUserException(sprintf($game->translate("%s already has reknown."), $location));
            }

            $reknownEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($reknownEvent);
            $game->theah->queueEvent($reknownEvent);
    
            $game->gamestate->nextState("");
        }
    }
}