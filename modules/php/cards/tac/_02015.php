<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02015 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Teeth of the Drachen');
        $this->Image = "02015.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 15;

        $this->initializeFaction("Eisen");
        $this->Initiative = 85;
        $this->PanacheModifier = 0;

        $this->Traits = [
        ];

        $this->Text = clienttranslate("<p>Add a Renown to two locations with no Renown.</p><hr><p>Characters at uncontrolled locations cannot intervene.</p>");

        $this->resetCard();
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCharacterIntervened && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $character = $event->theah->getCharacterById($event->newTargetId);
            $location = $event->theah->getCityLocation($character->Location);
            if (!$location->isControlled())
            {
                $game = $event->theah->game;
                throw new UserException($game->translate("Teeth of the Drachen") . sprintf($game->translate(": %s is not controlled. Characters cannot intervene at this location."), $location->Name));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  
            Renown will be added to two locations that have no Renown. '), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            //Transition to the state where player can choose a location.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02015");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);            

        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02015)
        {            
            if (count($ids) != 2)
            {
                throw new UserException($game->translate("You must choose two locations that have no Renown."));
            }

            if ($ids[0] == $ids[1])
            {
                throw new UserException($game->translate("You must choose two different locations."));
            }

            foreach ($ids as $id)
            {
                $location = $game->theah->getCityLocation($id);
                if ($location->Renown > 0)
                {
                    throw new UserException(sprintf($game->translate("%s has/have Renown."), $location->Name));
                }

                $event = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $id, 1, $this->getInjectCode());
                $game->theah->eventCheck($event);
                $game->theah->queueEvent($event);
            }

            $game->gamestate->nextState();
        }
    }
}