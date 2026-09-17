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

    /**
     * @return list<string>
     */
    private function cityLocationNamesWithNoRenown(Game $game): array
    {
        $locations = $game->theah->getCityLocations();
        $names = [];
        foreach ($locations as $location) {
            if ($location->Renown == 0) {
                $names[] = $location->Name;
            }
        }
        return $names;
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
            $game = $event->theah->game;
            $emptyNames = $this->cityLocationNamesWithNoRenown($game);
            // WHY: "two locations with no Renown" is do-as-much-as-possible — if fewer than
            // two empty locations exist, place on what remains (or skip if none). Hard-requiring
            // two picks stranded the player when only one empty location was available.
            if (count($emptyNames) == 0) {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. There are no city locations without Renown; no Renown is placed.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                ]);
                return;
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose city location(s) with no Renown to place Renown onto (up to two different locations).'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02015");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02015) {
            $args["locationIds"] = $this->cityLocationNamesWithNoRenown($game);
            $args["requiredLocationCount"] = min(2, count($args["locationIds"]));
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02015)
        {
            $empty = $this->cityLocationNamesWithNoRenown($game);
            $required = min(2, count($empty));

            if (count($ids) != $required)
            {
                if ($required == 2) {
                    throw new UserException($game->translate("You must choose two different locations that have no Renown."));
                }
                throw new UserException($game->translate("You must choose a location that has no Renown."));
            }

            $ids = array_values(array_unique($ids));
            if (count($ids) != $required)
            {
                throw new UserException($game->translate("You must choose different locations."));
            }

            foreach ($ids as $id)
            {
                if (! in_array($id, $empty, true))
                {
                    throw new UserException($game->translate("Each chosen location must have no Renown."));
                }

                $location = $game->theah->getCityLocation($id);
                if ($location == null)
                {
                    throw new UserException($game->translate("Location not found"));
                }

                $event = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $id, 1, $this->getInjectCode());
                $game->theah->eventCheck($event);
                $game->theah->queueEvent($event);
            }

            $game->gamestate->nextState();
        }
    }
}
