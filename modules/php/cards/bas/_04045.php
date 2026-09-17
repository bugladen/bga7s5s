<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04045 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Stand Your Ground');
        $this->Image = '04045.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 45;

        $this->initializeFaction("Ussura");

        $this->Initiative = 81;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate("Challenge"),
            clienttranslate("Relentless")
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The City Docks] <i>or</i> [The Grand Bazaar].</p>
<hr />
<p><b>En Garde Duelist Action:</b> Your performer issues an unrefusable [Combat] challenge to target opposing character. When accepted, your participant gains a threat. If your adversary is destroyed during the duel, gain a Renown. <i>(Intervening accepts the challenge)</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04045(),
        ];
    }

    /**
     * Docks and Bazaar are always in the city (2p still uses both).
     *
     * @return string[]
     */
    public function getRenownLocationNames(Theah $theah): array
    {
        $wanted = [
            Game::LOCATION_CITY_DOCKS,
            Game::LOCATION_CITY_BAZAAR,
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

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose The City Docks or The Grand Bazaar to place a Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($event->playerId),
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04045");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04045)
        {
            $args["locationIds"] = $this->getRenownLocationNames($game->theah);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04045)
        {
            $locationName = $ids[0];
            $validLocations = $this->getRenownLocationNames($game->theah);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException($game->translate("Location must be The City Docks or The Grand Bazaar."));
            }

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $locationName, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->gamestate->nextState();
        }
    }
}
