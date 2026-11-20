<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Rum;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Rum extends Scheme implements IHasActions
{
    use ActionTrait;

    // WHY: "the chosen location" for the Pirate City Action lasts until Dusk.
    // Must survive stRunEvents rebuilds — flush with updateCardObjectInDb on pick.
    public string $ChosenLocation = '';

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "X Marks the Spot";
        $this->Image = "05Rum.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=24 matches CAD sequence; footer BAS-C is set designation.
        // Resolve state ids cannot be 2605024 / 2605240 — Hacked / Scallywag already own those.
        $this->CardNumber = 24;

        $this->initializeFaction("Castille");

        $this->Initiative = 6;
        $this->PanacheModifier = 1;

        // WHY: Art traits are Daring / Camaraderie (scaffold had Fortune / Discovery).
        $this->Traits = [
            "Daring",
            "Camaraderie",
        ];

        $this->Text = "<p>Choose a location. Place this card at that location and add a renown to a different location.</p>
<hr />
<p><b>Pirate City Action:</b> If your performer is at the chosen location • Pressure it with [Finesse]. 
If successful, search your deck for a <b>Unique</b> Attachment and equip it to your performer paying all costs.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05Rum(),
        ];
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        $properties['chosenLocation'] = $this->ChosenLocation;
        return $properties;
    }

    /**
     * @return string[]
     */
    public function getCityLocationNames(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            $names[] = $location->Name;
        }
        return $names;
    }

    /**
     * @return string[]
     */
    public function getRenownLocationNames(Theah $theah): array
    {
        return array_values(array_filter(
            $this->getCityLocationNames($theah),
            fn(string $name) => $name !== $this->ChosenLocation
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", '${scheme_inject_code} now resolves. ${player_name} must choose a City location to place this card onto. Then they will add a Renown to a different location.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($event->playerId),
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "05Rum");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventSchemeMovedToCity && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all('schemeMovedToCity',
                '${scheme_inject_code} moved to ${location}', [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "cardId" => $this->Id,
                    "location" => $event->location,
            ]);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM)
        {
            $args["locationIds"] = $this->getCityLocationNames($game->theah);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM_2)
        {
            $args["chosenLocation"] = $this->ChosenLocation;
            $args["locationIds"] = $this->getRenownLocationNames($game->theah);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM)
        {
            $location = $ids[0] ?? '';
            $validLocations = $this->getCityLocationNames($game->theah);
            if (! in_array($location, $validLocations, true))
            {
                throw new UserException("Location must be a City location.");
            }

            $this->ChosenLocation = $location;
            $game->updateCardObjectInDb($this);
            $game->theah->addCardToWorld($this);
            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM_2)
        {
            $playerId = $game->getActivePlayerId();
            $playerName = $game->getPlayerNameById($playerId);
            $location = $ids[0] ?? '';

            $validLocations = $this->getRenownLocationNames($game->theah);
            if (! in_array($location, $validLocations, true))
            {
                throw new UserException("You must choose a different City location for Renown.");
            }

            if ($location === $this->ChosenLocation)
            {
                throw new UserException("You cannot place Renown on the chosen location.");
            }

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);

            $schemeMoveEvent = $game->theah->createEvent(Events::SchemeMovedToCity);
            if ($schemeMoveEvent instanceof EventSchemeMovedToCity)
            {
                $schemeMoveEvent->scheme = $this;
                $schemeMoveEvent->location = $this->ChosenLocation;
                $schemeMoveEvent->playerId = $playerId;
            }
            $game->theah->eventCheck($schemeMoveEvent);

            $game->notify->all('message',
                '${player_name} has chosen ${location} as the location for ${scheme_inject_code}.', [
                "player_name" => $playerName,
                "location" => $this->ChosenLocation,
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $game->theah->queueEvent($renownEvent);
            $game->theah->queueEvent($schemeMoveEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
