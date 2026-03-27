<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01152a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01152b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01152 extends Scheme implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Until Morale Improves");
        $this->Image = "01152.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 152;

        $this->Initiative = 30;
        $this->PanacheModifier = -2;

        $this->Traits = [
            clienttranslate("Ad Hoc"), 
            clienttranslate("Demoralize"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to any location or move a Renown to an adjacent location.</p><p>[BAR]</p><p>City Action: Wound your performer • En garde target character at this location.</p><p>City Action: Wound your performer • Engage target character at this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01152a(),
            new Action_01152b(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. 
            ${player_name} may choose a city location to place Renown onto. 
            If they choose not to, they may move a Renown from a city location to an adjacent location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition  = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01152", $this->Id);
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);
        
        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3)
        {
            $args["location"] = $game->globals->get(GAME::CHOSEN_LOCATION);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01152)
        {
            $location = $ids[0];
        
            $event = EventFactory::createReknownAddedToLocationEvent($game->getActivePlayerId(), $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->gamestate->nextState("reknownPlaced");
        }

        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2)
        {
            $location = $ids[0];

            //Check if the location actually has reknown to move
            $reknown = $game->getReknownForLocation($location);
            if ($reknown <= 0)
                throw new \BgaUserException(sprintf($game->translate("%s does not have any Renown to move."), $location));
    
            $game->globals->set(GAME::CHOSEN_LOCATION, $location);
    
            $game->gamestate->nextState("locationChosen");
        }

        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3)
        {
            $location = $ids[0];

            $fromLocation = $game->globals->get(GAME::CHOSEN_LOCATION);
            $event = EventFactory::createReknownRemovedFromLocationEvent($this->ControllerId, $fromLocation, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);    

            $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode(), $isMove = true);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->gamestate->nextState("locationChosen");
        }
    }
}