<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02045;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

class _02045 extends Scheme implements IHasActions
{
    use ActionTrait;

    public string $ChosenLocation = '';

    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate('Path to Poluchatel');
        $this->Image = '02045.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 45;

        $this->initializeFaction('Ussura');
        $this->Initiative = 25;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate('Dar Matushki'),
            clienttranslate('Discovery'),
        ];

        $this->Text = clienttranslate("<p>Choose one of the outermost locations. Add a Renown to two other locations. Place this card on the chosen location.</p><hr><p><b>Sorcerer City Action:</b> If your performer is at the chosen location • Pressure it with [Influence]. If successful, search your deck for a <b>Dar Matushki</b> or <b>Poluchatel</b> card, reveal it, and put it into your hand. <i>(Shuffle your deck.)</i></p>");
        
        $this->resetCard();

        $this->Actions = [
            new Action_02045(),
        ];
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        $properties['chosenLocation'] = $this->ChosenLocation;
        return $properties;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must first choose an outermost city location. Then they will choose two other locations to place Renown onto.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "02045");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventSchemeMovedToCity && $event->scheme == $this)
        {
            $event->theah->game->notify->all('schemeMovedToCity',
                clienttranslate('${scheme_inject_code} moved to ${location}'), [
                    'i18n' => ['location'],
                    "scheme_inject_code" => $this->getInjectCode(),
                    "cardId" => $this->Id,
                    "location" => $event->location,
            ]);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02045_2)
        {
            $args["chosenLocation"] = $this->ChosenLocation;
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02045)
        {
            $location = $ids[0];

            if (!in_array($location, $game->theah->getOuterCityLocations()))
            {
                throw new UserException($game->translate("Location is not an outer city location."));
            }

            $this->ChosenLocation = $location;
            $game->updateCardObjectInDb($this);
            $game->theah->addCardToWorld($this);
            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_02045_2)
        {
            $playerId = $game->getActivePlayerId();
            $playerName = $game->getActivePlayerName();

            $locations = $ids;

            if (count($locations) != 2)
            {
                throw new UserException($game->translate("You must choose two locations."));
            }

            $locations = array_values(array_unique($locations));
            if (count($locations) != 2)
            {
                throw new UserException($game->translate("You must choose two different locations."));
            }

            foreach ($locations as $location)
            {
                if ($location == $this->ChosenLocation)
                {
                    throw new UserException($game->translate("You cannot place Renown on the chosen location."));
                }
            }

            foreach ($locations as $location) {
                $reknownEvent = EventFactory::createRenownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
                $game->theah->eventCheck($reknownEvent);
            }

            $schemeMoveEvent = $game->theah->createEvent(Events::SchemeMovedToCity);
            if ($schemeMoveEvent instanceof EventSchemeMovedToCity) {
                $schemeMoveEvent->scheme = $this;
                $schemeMoveEvent->location = $this->ChosenLocation;
                $schemeMoveEvent->playerId = $playerId;
            }
            $game->theah->eventCheck($schemeMoveEvent);

            $game->notify->all('message',
                clienttranslate('${player_name} has chosen ${location} as the location for ${scheme_inject_code}.'), [
                'i18n' => ['location'],
                "player_name" => $playerName,
                "location" => $this->ChosenLocation,
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            foreach ($locations as $location) {
                $reknownEvent = EventFactory::createRenownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
                $game->theah->queueEvent($reknownEvent);
            }

            $game->theah->queueEvent($schemeMoveEvent);

            $game->gamestate->nextState("locationsChosen");
        }
    }
}
