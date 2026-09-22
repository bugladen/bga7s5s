<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04051 extends Scheme
{
    // WHY: Forced at Planning End refers to "the chosen player" from resolve.
    // Game::CHOSEN_OPPONENT is phase-local; this must survive until Planning End.
    public int $chosenOpponentId = 0;
    public string $firstRenownLocation = '';
    public string $claimedLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('A Shared Interest');
        $this->Image = '04051.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 51;

        $this->initializeFaction("Neutral");

        $this->Initiative = 31;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate("Bargain"),
            clienttranslate("Savvy"),
        ];

        $this->Text = clienttranslate("<p>Choose an opponent to add a Renown to any location. Then, add a Renown to a different location.</p>
<hr />
<p><b>Forced:</b> At the end of Planning, claim a <b>City</b> location. Then, the chosen player claims a different <b>City</b> location.</p>");

        $this->resetCard();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function getOpponentChoices(Game $game): array
    {
        $opponents = [];
        foreach ($game->loadPlayersBasicInfos() as $playerId => $player)
        {
            $playerId = (int)$playerId;
            if ($playerId == $this->ControllerId)
            {
                continue;
            }
            $opponents[] = ['id' => $playerId, 'name' => $player['player_name']];
        }

        return $opponents;
    }

    /**
     * @return string[]
     */
    public function getCityLocationNames(Theah $theah): array
    {
        return array_values(array_map(fn($location) => $location->Name, $theah->getCityLocations()));
    }

    /**
     * City locations the player may claim right now.
     *
     * @return string[]
     */
    public function getClaimableCityLocationNames(Theah $theah, int $playerId, string $excludeLocation = ''): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($excludeLocation !== '' && $location->Name === $excludeLocation)
            {
                continue;
            }
            if ($theah->canLocationBeClaimedBy($playerId, $location->Name))
            {
                $names[] = $location->Name;
            }
        }

        return $names;
    }

    private function clearPersistedPicks(): void
    {
        $this->chosenOpponentId = 0;
        $this->firstRenownLocation = '';
        $this->claimedLocation = '';
        $this->IsUpdated = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose an opponent. That opponent will place a Renown, then ${player_name} will place a Renown on a different location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($event->playerId),
            ]);

            $this->clearPersistedPicks();

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04051");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhasePlanningEnd && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $game = $event->theah->game;

            if ($this->chosenOpponentId <= 0)
            {
                return;
            }

            $claimable = $this->getClaimableCityLocationNames($event->theah, $this->ControllerId);
            if (count($claimable) === 0)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code}: There are no City locations that can be claimed.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                ]);
                $this->clearPersistedPicks();
                $game->updateCardObjectInDb($this);

                return;
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code} triggers a Forced ability at the end of Planning. ${player_name} must claim a City location. Then ${opponent_name} must claim a different City location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "opponent_name" => $game->getPlayerNameById($this->chosenOpponentId),
            ]);

            $this->claimedLocation = '';
            $this->IsUpdated = true;

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "04051");
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->clearPersistedPicks();
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04051)
        {
            $args["opponents"] = $this->getOpponentChoices($game);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_2)
        {
            $args["locationIds"] = $this->getCityLocationNames($game->theah);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_3)
        {
            $args["locationIds"] = array_values(array_filter(
                $this->getCityLocationNames($game->theah),
                fn(string $name) => $name !== $this->firstRenownLocation
            ));
        }

        if ($state == States::PLANNING_PHASE_END_04051)
        {
            $args["locationIds"] = $this->getClaimableCityLocationNames($game->theah, $this->ControllerId);
        }

        if ($state == States::PLANNING_PHASE_END_04051_2)
        {
            $args["locationIds"] = $this->getClaimableCityLocationNames(
                $game->theah,
                $this->chosenOpponentId,
                $this->claimedLocation
            );
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04051)
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

            $this->chosenOpponentId = $id;
            $this->IsUpdated = true;
            $game->updateCardObjectInDb($this);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has chosen ${opponent_name} to place a Renown.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "opponent_name" => $players[$id]['player_name'],
            ]);

            // WHY: MEDIUM_PRIORITY so this transition runs after any pending events,
            // and the opponent becomes active for the Renown pick.
            $transition = EventFactory::createTransitionEvent($id, $this->Id, "04051_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_2)
        {
            $location = $ids[0];
            $valid = $this->getCityLocationNames($game->theah);
            if (! in_array($location, $valid, true))
            {
                throw new UserException($game->translate("Location is not a city location."));
            }

            $opponentId = $game->getActivePlayerId();
            if ($opponentId != $this->chosenOpponentId)
            {
                throw new UserException($game->translate("It is not your turn to place Renown."));
            }

            $this->firstRenownLocation = $location;
            $this->IsUpdated = true;
            $game->updateCardObjectInDb($this);

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($opponentId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has chosen to place a Renown onto ${location}.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($opponentId),
                "location" => $location,
                "i18n" => ["location"],
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "04051_3");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_3)
        {
            $location = $ids[0];
            $valid = array_values(array_filter(
                $this->getCityLocationNames($game->theah),
                fn(string $name) => $name !== $this->firstRenownLocation
            ));
            if (! in_array($location, $valid, true))
            {
                throw new UserException($game->translate("Location must be a different city location."));
            }

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has chosen to place a Renown onto ${location}.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "location" => $location,
                "i18n" => ["location"],
            ]);

            $this->firstRenownLocation = '';
            $this->IsUpdated = true;
            $game->updateCardObjectInDb($this);

            $game->gamestate->nextState();
        }

        if ($state == States::PLANNING_PHASE_END_04051)
        {
            $location = $ids[0];
            $valid = $this->getClaimableCityLocationNames($game->theah, $this->ControllerId);
            if (! in_array($location, $valid, true))
            {
                throw new UserException($game->translate("That City location cannot be claimed."));
            }

            $this->claimedLocation = $location;
            $this->IsUpdated = true;
            $game->updateCardObjectInDb($this);

            $claimEvent = EventFactory::createLocationClaimedEvent($this->ControllerId, null, $location);
            $game->theah->queueEvent($claimEvent);

            $opponentClaimable = $this->getClaimableCityLocationNames(
                $game->theah,
                $this->chosenOpponentId,
                $location
            );

            // WHY: "Then" is contingent — if the chosen player has no other claimable
            // City location, skip their pick rather than soft-locking Planning End.
            if (count($opponentClaimable) === 0)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${opponent_name} has no different City location that can be claimed.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "opponent_name" => $game->getPlayerNameById($this->chosenOpponentId),
                ]);
                $this->clearPersistedPicks();
                $game->updateCardObjectInDb($this);
                $game->gamestate->nextState();

                return;
            }

            $transition = EventFactory::createTransitionEvent($this->chosenOpponentId, $this->Id, "04051_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

        if ($state == States::PLANNING_PHASE_END_04051_2)
        {
            $location = $ids[0];
            $valid = $this->getClaimableCityLocationNames(
                $game->theah,
                $this->chosenOpponentId,
                $this->claimedLocation
            );
            if (! in_array($location, $valid, true))
            {
                throw new UserException($game->translate("That City location cannot be claimed."));
            }

            $opponentId = $this->chosenOpponentId;
            $claimEvent = EventFactory::createLocationClaimedEvent($opponentId, null, $location);
            $game->theah->queueEvent($claimEvent);

            $this->clearPersistedPicks();
            $game->updateCardObjectInDb($this);

            $game->gamestate->nextState();
        }
    }
}
