<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\GameFramework\GameStateBuilder;
use Bga\GameFramework\StateType;
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
        $this->Image = "img/cards/7s5s/152.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 152;

        $this->Faction = "";
        $this->Initiative = 30;
        $this->PanacheModifier = -2;

        $this->Traits = [
            "Ad Hoc", 
            "Demoralize",
        ];

        $this->Actions = [
            new Action_01152a(),
            new Action_01152b(),
        ];
    }

    public function addStates(Game $game)
    {
        parent::addStates($game);

        //WIP
        //This an attempt to move state construction out of the monolithic states.inc.php file.
        //There is currently no callback from the framework to add states after the state array is built.
        //So this is on hold for now.
        if (!$game->inStateMachine(States::PLANNING_PHASE_RESOLVE_SCHEMES_01152))
        {
            $state = GameStateBuilder::create()
                ->name(States::PLANNING_PHASE_RESOLVE_SCHEMES_01152)
                ->description(clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} may choose a City Location to place a Reknown onto.'))
                ->descriptionMyTurn(clienttranslate('Until Morale Improves') . clienttranslate(': ${you} may choose a City Location to place a Reknown onto: '))
                ->type(StateType::ACTIVE_PLAYER)
                ->args("argsEmpty")
                ->possibleActions([
                    "actFromCardWithLocations", 
                    "actFromCardPass"
                ])
                ->transitions([
                    "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2, 
                    "reknownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
                ])
                ->build();

            $game->addState(States::PLANNING_PHASE_RESOLVE_SCHEMES_01152, $state);
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves. 
            ${player_name} may choose a city location to place reknown onto. 
            If they choose not to, they may move a Reknown from a city location to an adjacent location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition  = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01152", $this->Id);
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
                throw new \BgaUserException(sprintf($game->translate("%s does not have any reknown to move."), $location));
    
            $event = EventFactory::createReknownRemovedFromLocationEvent($this->ControllerId, $location, 1, sprintf($game->translate("%s: Moving Reknown from one Location to an adjacent location"), $this->getInjectCode()));
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->globals->set(GAME::CHOSEN_LOCATION, $location);
    
            $game->gamestate->nextState("locationChosen");
        }

        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3)
        {
            $location = $ids[0];

            $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, sprintf($game->translate("%s: Moving Reknown from one Location to an adjacent location"), $this->getInjectCode()));
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->gamestate->nextState("");
        }
    }
}