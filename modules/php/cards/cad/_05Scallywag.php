<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Scallywag;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Scallywag extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Friends at Sea";
        $this->Image = "05Scallywag.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=24 matches CAD sequence; footer BAS-C is set designation.
        // Resolve state id cannot be 2605024 — Stolen Schematics (_05Hacked) already owns it.
        $this->CardNumber = 24;

        $this->initializeFaction("Castille");

        $this->Initiative = 21;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Daring",
            "Camaraderie",
        ];

        $this->Text = "<p>Add a Renown to [The City Forum] or [The City Docks]</p>
<hr />
<p><b>Reaction:</b> When an opponent's <b>Leader</b> is wounded as a result of your performer's challenge or during their duel • Muster a <b>Pirate</b> engaged at their location.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Scallywag(),
        ];
    }

    /**
     * Forum and Docks are always in the city (2p still uses both).
     *
     * @return string[]
     */
    public function getRenownLocationNames(Theah $theah): array
    {
        $wanted = [
            Game::LOCATION_CITY_FORUM,
            Game::LOCATION_CITY_DOCKS,
        ];

        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if (in_array($location->Name, $wanted, true))
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

            $game->notify->all("message", '${scheme_inject_code} now resolves. ${player_name} must choose The City Forum or The City Docks to place a Renown onto.', [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($event->playerId),
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "05Scallywag");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05SCALLYWAG)
        {
            $args["locationIds"] = $this->getRenownLocationNames($game->theah);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_05SCALLYWAG)
        {
            $locationName = $ids[0];
            $validLocations = $this->getRenownLocationNames($game->theah);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException("Location must be The City Forum or The City Docks.");
            }

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $locationName, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->gamestate->nextState();
        }
    }
}
