<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02052 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gutter Full of Roses");
        $this->Image = "02052.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 52;

        $this->initializeFaction("Neutral");
        $this->Initiative = 9;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Riot'),
            clienttranslate('Brawl'),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [Bazaar]. Then, move a Renown to [Bazaar].</p><hr><p><b>Forced:</b> When a player's adversary is destroyed during a duel at [Bazaar] • That player collects a Renown from [Bazaar]. <i>(This Forced ability activates for any player.)</i></p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Resolve effect: Add a Renown to Bazaar, then move a Renown to Bazaar
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $addEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($addEvent);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "02052");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        // Forced: When a player's adversary is destroyed during a duel at Bazaar,
        // that player collects a Renown from Bazaar (activates for any player)
        if ($event instanceof EventCharacterDestroyed && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);
            if ($inDuel)
            {
                $challengerId = $event->theah->getDuelChallengerId();
                $defenderId = $event->theah->getDuelDefenderId();
                $challenger = $event->theah->getCharacterById($challengerId);
                $defender = $event->theah->getCharacterById($defenderId);

                // Check if the duel is at Bazaar
                $duelLocation = $challenger->Location;
                if ($duelLocation == Game::LOCATION_CITY_BAZAAR)
                {
                    // The player whose adversary was destroyed is the one who wins
                    // If the destroyed character belongs to the challenger, defender's adversary was NOT destroyed
                    // The destroyed character IS the adversary of the other player
                    $destroyedCharacterId = $event->characterId;

                    // Determine the winning player (the one whose adversary was destroyed)
                    if ($challengerId == $destroyedCharacterId) {
                        $winningPlayerId = $defender->ControllerId;
                    } elseif ($defenderId == $destroyedCharacterId) {
                        $winningPlayerId = $challenger->ControllerId;
                    } else {
                        return;
                    }

                    $bazaar = $event->theah->getCityLocation(Game::LOCATION_CITY_BAZAAR);
                    if ($bazaar !== null && $bazaar->Renown > 0)
                    {
                        $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s adversary was destroyed during a duel at The Grand Bazaar. They collect a Renown from The Grand Bazaar.'), [
                            "scheme_inject_code" => $this->getInjectCode(),
                            "player_name" => $event->theah->game->getPlayerNameById($winningPlayerId),
                        ]);

                        $removeEvent = EventFactory::createRenownRemovedFromLocationEvent($winningPlayerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
                        $event->theah->queueEvent($removeEvent);

                        $gainEvent = EventFactory::createPlayerGainsReknownEvent($winningPlayerId, 1);
                        $event->theah->queueEvent($gainEvent);
                    }
                }
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02052)
        {
            $locations = $game->theah->getCityLocations();
            $availableLocations = [];
            foreach ($locations as $location) {
                if ($location->Name != Game::LOCATION_CITY_BAZAAR && $location->Renown > 0) {
                    $availableLocations[] = $location->Name;
                }
            }
            $args["locationIds"] = $availableLocations;
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02052)
        {
            $location = $ids[0];

            $loc = $game->theah->getCityLocation($location);
            if ($loc == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            if ($loc->Name == Game::LOCATION_CITY_BAZAAR)
            {
                throw new UserException($game->translate("Cannot move Renown from The Grand Bazaar to The Grand Bazaar"));
            }

            if ($loc->Renown <= 0)
            {
                throw new UserException($game->translate("No Renown at that location"));
            }

            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($removeEvent);

            $addEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode(), $isMove = true);
            $game->theah->eventCheck($addEvent);

            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: A Renown has been moved from ${location} to The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "location" => $location,
                "i18n" => ["location"],
            ]);

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02052)
        {
            $locations = $game->theah->getCityLocations();
            foreach ($locations as $location) {
                if ($location->Name != Game::LOCATION_CITY_BAZAAR && $location->Renown > 0) {
                    throw new UserException($game->translate("You must move a Renown to The Grand Bazaar"));
                }
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: No Renown available to move to The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $game->gamestate->nextState();
        }
    }
}
