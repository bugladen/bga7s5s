<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04004 extends Scheme implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Blood Money");
        $this->Image = "04004.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 4;

        $this->initializeFaction("Vodacce");

        // WHY: Card art sun icon is 8; scaffold had 64 (TAC copy-paste).
        $this->Initiative = 8;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Assassination"),
            clienttranslate("Fortune"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The City Docks] and [The Grand Bazaar].</p>
<p>Then, move your <b>Duelist</b> to a <b>City</b> location.</p>
<hr />
<p><b>Duelist City Action:</b> Move your performer to a location with a wounded enemy.</p>
<p><b>Duelist Reaction:</b> When an opposing character is destroyed • Draw a card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04004(),
        ];

        $this->Reactions = [
            new Reaction_04004(),
        ];
    }

    /**
     * @return list<Character>
     */
    public function getEligibleDuelists(Theah $theah, int $playerId): array
    {
        return array_values(array_filter(
            $theah->getCharactersInPlayByPlayerId($playerId),
            fn(Character $character) => $character->hasTrait("Duelist")
                && count($this->getValidCityDestinations($theah, $character)) > 0
        ));
    }

    /**
     * @return list<string>
     */
    public function getValidCityDestinations(Theah $theah, Character $character): array
    {
        $destinations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name != $character->Location)
            {
                $destinations[] = $location->Name;
            }
        }

        return $destinations;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;
            $duelists = $this->getEligibleDuelists($event->theah, $this->ControllerId);

            if (count($duelists) > 0)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Docks and The Grand Bazaar. ${player_name} must then move a Duelist to a City location.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Docks and The Grand Bazaar. ${player_name} has no Duelist to move.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);

            // WHY: Only enter pick states when a legal Duelist+destination exists — "Then" is contingent.
            if (count($duelists) > 0)
            {
                $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04004");
                $transition->priority = Event::MEDIUM_PRIORITY;
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04004)
        {
            $args["ids"] = array_map(
                fn(Character $character) => $character->Id,
                $this->getEligibleDuelists($game->theah, $this->ControllerId)
            );
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04004_2)
        {
            $characterId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);
            $args["characterId"] = $characterId;
            $args["locationIds"] = $character !== null
                ? $this->getValidCityDestinations($game->theah, $character)
                : [];
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04004)
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

            if (! $character->hasTrait("Duelist"))
            {
                throw new UserException($game->translate("Character must be a Duelist."));
            }

            if (count($this->getValidCityDestinations($game->theah, $character)) == 0)
            {
                throw new UserException($game->translate("That Duelist has no valid City destination."));
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);
            $game->gamestate->nextState("duelistChosen");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04004_2)
        {
            $characterId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $locationName = $ids[0];
            $validLocations = $this->getValidCityDestinations($game->theah, $character);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException($game->translate("Location is not a valid City destination."));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} moves ${character_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
                "location_name" => $locationName,
            ]);

            // WHY: engage=false — printed text says Move only (no Engage).
            $moveEvent = EventFactory::createCardMovingEvent(
                $this->ControllerId,
                $character->Id,
                $character->Location,
                $locationName,
                false,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
