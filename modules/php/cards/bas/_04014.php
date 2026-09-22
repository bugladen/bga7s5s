<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04014 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Forged for Battle");
        $this->Image = "04014.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 14;

        $this->initializeFaction("Eisen");

        $this->Initiative = 45;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Zeal"),
            clienttranslate("Prepared")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The City Docks] and another location.</p>
<hr />
<p>When your character issues a challenge or intervenes, you may engage a <b>Weapon</b> or <b>Armor</b> equipped to them. If you do, they gain +1[Finesse] for the duration of the action.
<br><i>(Can be used any number of times per day, and once per challenge or intervention.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04014(),
        ];
    }

    /**
     * City locations other than the Docks (the fixed Renown destination).
     *
     * @return string[]
     */
    public function getOtherCityLocationNames(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name != Game::LOCATION_CITY_DOCKS)
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

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Docks. ${player_name} must choose another location for a second Renown.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($event->playerId),
            ]);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);

            // WHY: MEDIUM_PRIORITY so the Docks Renown resolves before the pick state.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04014");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04014)
        {
            $args["locationIds"] = $this->getOtherCityLocationNames($game->theah);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04014)
        {
            $locationName = $ids[0];
            $validLocations = $this->getOtherCityLocationNames($game->theah);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException($game->translate("Location must be a City location other than The City Docks."));
            }

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $locationName, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->gamestate->nextState();
        }
    }
}
