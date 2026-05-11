<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02025;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02025 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Tea and Cakes');
        $this->Image = "02025.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 25;

        $this->initializeFaction('Montaigne');
        $this->Initiative = 19;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate('Bureaucracy'),
            clienttranslate('Bargain'),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to any location. Target opponent adds a Renown to any location.</p><hr><p><b>Diplomat City Action:</b> Target an opposing character with equal or lower [Influence] • Move them, your performer and a Renown from this location to the same adjacent location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02025(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a City Location to place a Renown onto. Then a target opponent must choose a City Location to place a Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "02025");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02025 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_3)
        {
            $locations = $game->theah->getCityLocations();
            $args["locationIds"] = array_map(fn($location) => $location->Name, array_values($locations));
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_2)
        {
            $players = $game->loadPlayersBasicInfos();
            $availablePlayers = array_filter($players, fn($player) => $player["player_id"] != $this->ControllerId);
            $args["opponents"] = array_map(fn($player) => ['id' => $player['player_id'], 'name' => $player['player_name']], array_values($availablePlayers));
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_2)
        {
            $players = $game->loadPlayersBasicInfos();
            if (! isset($players[$id]))
            {
                throw new UserException($game->translate("Invalid opponent"));
            }

            if ($id == $this->ControllerId)
            {
                throw new UserException($game->translate("You cannot choose yourself"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has chosen ${opponent_name} to place a Renown.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "opponent_name" => $players[$id]['player_name'],
            ]);

            $transition = EventFactory::createTransitionEvent($id, $this->Id, "02025_3");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02025)
        {
            $location = $ids[0];

            $loc = $game->theah->getCityLocation($location);
            if ($loc == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $renownEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02025_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_3)
        {
            $location = $ids[0];

            $loc = $game->theah->getCityLocation($location);
            if ($loc == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $opponentId = $game->getActivePlayerId();

            $renownEvent = EventFactory::createReknownAddedToLocationEvent($opponentId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has chosen to place a Renown onto ${location}.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($opponentId),
                "location" => $location,
                "i18n" => ["location"],
            ]);

            $game->gamestate->nextState("");
        }
    }
}
