<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04034 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Explosive Ultimatum");
        $this->Image = "04034.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 34;

        $this->initializeFaction("Castille");

        $this->Initiative = 28;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Cunning"),
            clienttranslate("Sabotage"),
        ];

        // WHY: Art has "may" move instead + "choose"; scaffold omitted may and typo'd "chose".
        $this->Text = clienttranslate("<p>Add a Renown to any location. If you have the fewest Renown, you may move a Renown to an adjacent location instead.</p>
<hr />
<p><b>City Action:</b> If an opponent controls this location • They may choose to lose control of it. If they do not, wound all opposing characters there.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04034(),
        ];
    }

    /**
     * Unique fewest only — ties do not qualify for the move-instead option.
     * WHY: Eddie — move path requires no tie for Renown (same discipline as _01144).
     */
    public function playerHasFewestRenown(Game $game, int $playerId): bool
    {
        $playerScore = $game->getPlayerReknown($playerId);
        $atLowest = 0;
        foreach ($game->loadPlayersBasicInfos() as $id => $_)
        {
            $score = $game->getPlayerReknown((int)$id);
            if ($score < $playerScore)
            {
                return false;
            }
            if ($score == $playerScore)
            {
                $atLowest++;
            }
        }

        return $atLowest == 1;
    }

    public function anyCityLocationHasRenown(Theah $theah): bool
    {
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Renown > 0)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function getCityLocationNamesWithRenown(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Renown > 0)
            {
                $names[] = $location->Name;
            }
        }

        return $names;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;
            $hasFewest = $this->playerHasFewestRenown($game, $this->ControllerId);
            $canMove = $hasFewest && $this->anyCityLocationHasRenown($event->theah);

            if ($canMove)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must add a Renown to a city location, or may move a Renown to an adjacent location instead (fewest Renown).'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $event->playerName,
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a city location to place a Renown onto.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $event->playerName,
                ]);
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04034");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034)
        {
            // WHY: Only offer move when fewest AND something exists to move (01152 idiom).
            $args["canMoveRenown"] = $this->playerHasFewestRenown($game, $this->ControllerId)
                && $this->anyCityLocationHasRenown($game->theah);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_2)
        {
            $args["locationIds"] = $this->getCityLocationNamesWithRenown($game->theah);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_3)
        {
            $fromLocation = $game->globals->get(Game::CHOSEN_LOCATION);
            $args["location"] = $fromLocation;
            $args["locationIds"] = $game->theah->getAdjacentCityLocations($fromLocation, $includeHome = false);
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034)
        {
            if (! $this->playerHasFewestRenown($game, $this->ControllerId))
            {
                throw new UserException($game->translate("You do not have the fewest Renown."));
            }

            if (! $this->anyCityLocationHasRenown($game->theah))
            {
                throw new UserException($game->translate("There are no Renown on any locations to move. You must place a Renown."));
            }

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034)
        {
            $location = $ids[0];
            if (! $game->theah->locationInCity($location))
            {
                throw new UserException($game->translate("Location must be a City location."));
            }

            $event = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState("renownPlaced");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_2)
        {
            $location = $ids[0];
            $valid = $this->getCityLocationNamesWithRenown($game->theah);
            if (! in_array($location, $valid, true))
            {
                throw new UserException(sprintf($game->translate("%s does not have any Renown to move."), $location));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $location);
            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_3)
        {
            $location = $ids[0];
            $fromLocation = $game->globals->get(Game::CHOSEN_LOCATION);
            $adjacent = $game->theah->getAdjacentCityLocations($fromLocation, $includeHome = false);
            if (! in_array($location, $adjacent, true))
            {
                throw new UserException($game->translate("Location must be adjacent to the source location."));
            }

            $batchId = $game->getNextEventBatchId();

            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent($this->ControllerId, $fromLocation, $location, 1, $this->getInjectCode());
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent($this->ControllerId, $fromLocation, 1, $this->getInjectCode());
            $removeEvent->batchId = $batchId;
            $game->theah->eventCheck($removeEvent);
            $game->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode(), $isMove = true);
            $addEvent->batchId = $batchId;
            $game->theah->eventCheck($addEvent);
            $game->theah->queueEvent($addEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
