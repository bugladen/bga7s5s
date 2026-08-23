<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04024 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Diplomatic Envoy");
        $this->Image = "04024.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 24;

        $this->initializeFaction("Montaigne");

        $this->Initiative = 16;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate("Bureaucracy"),
            clienttranslate("Welcome"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The City Forum].</p>
<p>Then, move your <b>Diplomat</b> there.</p>
<hr />
<p><b>En Garde:</b> <b>Diplomats</b> at [The City Forum] cannot be issued [Combat] challenges.</p>");

        $this->resetCard();
    }

    /**
     * Owned Diplomats not already at the Forum (can complete the contingent Then move).
     *
     * @return list<Character>
     */
    public function getEligibleDiplomats(Theah $theah, int $playerId): array
    {
        return array_values(array_filter(
            $theah->getCharactersInPlayByPlayerId($playerId),
            fn(Character $character) => $character->hasTrait("Diplomat")
                && $character->Location != Game::LOCATION_CITY_FORUM
        ));
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // WHY: Chosen schemes sit at Home all day. Patricia-style en garde + location gate;
        // Térence-style Combat-stat gate — Influence/Finesse challenges remain legal.
        if (
            $event instanceof EventChallengeIssued
            && $this->Location == Game::LOCATION_PLAYER_HOME
        )
        {
            $defender = $event->theah->getCharacterById($event->defenderId);
            $challengeStat = $event->theah->game->globals->get(Game::CHALLENGE_STAT);

            if (
                $defender !== null
                && $defender->hasTrait("Diplomat")
                && $defender->Location == Game::LOCATION_CITY_FORUM
                && ! $defender->Engaged
                && $challengeStat == Game::STAT_COMBAT
            )
            {
                throw new UserException($event->theah->game->translate(
                    "Diplomatic Envoy: En Garde Diplomats at The City Forum cannot be issued Combat challenges."
                ));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;
            $diplomats = $this->getEligibleDiplomats($event->theah, $this->ControllerId);

            if (count($diplomats) > 0)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Forum. ${player_name} must then move a Diplomat there.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Forum. ${player_name} has no Diplomat to move there.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }

            $forum = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                Game::LOCATION_CITY_FORUM,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($forum);

            // WHY: Only enter pick state when a legal Diplomat exists — "Then" is contingent.
            if (count($diplomats) > 0)
            {
                $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04024");
                $transition->priority = Event::MEDIUM_PRIORITY;
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04024)
        {
            $args["ids"] = array_map(
                fn(Character $character) => $character->Id,
                $this->getEligibleDiplomats($game->theah, $this->ControllerId)
            );
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04024)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($character->ControllerId != $this->ControllerId)
            {
                throw new UserException($game->translate("You do not control that character."));
            }

            if (! $character->hasTrait("Diplomat"))
            {
                throw new UserException($game->translate("Character must be a Diplomat."));
            }

            if ($character->Location == Game::LOCATION_CITY_FORUM)
            {
                throw new UserException($game->translate("That Diplomat is already at The City Forum."));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} moves ${character_inject_code} to The City Forum.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // WHY: engage=false — printed text says Move only (no Engage).
            $moveEvent = EventFactory::createCardMovingEvent(
                $this->ControllerId,
                $character->Id,
                $character->Location,
                Game::LOCATION_CITY_FORUM,
                false,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState("diplomatChosen");
        }
    }
}
