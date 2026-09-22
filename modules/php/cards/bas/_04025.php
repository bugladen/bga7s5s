<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04025 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("No Rest for the Wicked");
        $this->Image = "04025.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 25;

        $this->initializeFaction("Montaigne");

        $this->Initiative = 96;
        $this->PanacheModifier = -2;

        $this->Traits = [
            clienttranslate("Trade"),
            clienttranslate("Fortune"),
        ];

        $this->Text = clienttranslate("<p>Add a City Card to [The Grand Bazaar]. Then add a Renown to a different location.</p>
<hr />
<p><b>Merchant Reaction:</b> At the end of Planning • Look at the top three cards of your deck, and an additional card for each <b>Merchant</b> you control. Draw two of them and sink the rest.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04025(),
        ];
    }

    /**
     * City locations other than the Bazaar (fixed City Card destination).
     *
     * @return string[]
     */
    public function getOtherCityLocationNames(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name != Game::LOCATION_CITY_BAZAAR)
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

            $cityCards = $game->getCardsOnTopOfCityDeck(1);
            if (count($cityCards) > 0)
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A City Card will be added to The Grand Bazaar. ${player_name} must then choose a different location for a Renown.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);

                // WHY: Cast id — getCardsOnTopOfCityDeck returns raw deck rows (Penya / _01149).
                $cityCard = EventFactory::createCityCardAddedToLocationEvent((int) $cityCards[0]['id'], Game::LOCATION_CITY_BAZAAR);
                $event->theah->queueEvent($cityCard);
            }
            else
            {
                // WHY: Empty city deck+discard — still offer the Then Renown pick (exclude Bazaar).
                $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. There are no City Cards left to add to The Grand Bazaar. ${player_name} must choose a different location for a Renown.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($event->playerId),
                ]);
            }

            // WHY: MEDIUM_PRIORITY so the City Card add resolves before the Renown pick state.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04025");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04025)
        {
            $args["locationIds"] = $this->getOtherCityLocationNames($game->theah);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04025)
        {
            $locationName = $ids[0];
            $validLocations = $this->getOtherCityLocationNames($game->theah);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException($game->translate("Location must be a City location other than The Grand Bazaar."));
            }

            $renownEvent = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, $locationName, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);

            $game->gamestate->nextState();
        }
    }
}
