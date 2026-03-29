<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02014;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02014 extends Scheme implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Kaspar's Occupation");
        $this->Image = "02014.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 14;

        $this->initializeFaction("Eisen");
        $this->Initiative = 70;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Logistics"),
            clienttranslate("Demoralize"),
        ];

        $this->Text = clienttranslate("<p>Add or move a Renown to [Forums].</p><hr><p><b>Leader City Action:</b> Pressure your <b>Leader</b>'s location with [com]. You succeed even if tied. If successful, look at the top four cards of the City Deck. Discard any number of them and replace the rest in any order.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02014(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.
            Renown will either be added to The City Forum, or Renown will be moved FROM another location to The City Forum.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            //Transition to the state where player can choose a location.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02014");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);            
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02014)
        {
            if ($id == 1)
            {
                $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
                $game->theah->eventCheck($event);
                $game->theah->queueEvent($event);

                $game->gamestate->nextState();
            }
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02014)
        {
            $locationName = $ids[0];

            if ($locationName == Game::LOCATION_CITY_FORUM)
            {
                throw new UserException($game->translate("You cannot move Renown FROM The City Forum."), $locationName);
            }

            $location = $game->theah->getCityLocation($locationName);
            if ($location->Reknown == 0)
            {
                throw new UserException(sprintf($game->translate("%s does not have any Renown to move."), $locationName));
            }

            $event = EventFactory::createReknownRemovedFromLocationEvent($this->ControllerId, $locationName, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode(), $isMove = true);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState();            
        }
    }
}