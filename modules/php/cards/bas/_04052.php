<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04052 extends Scheme
{
    // WHY: "The chosen location cannot be controlled" lasts until Dusk. Must survive
    // serialization on the card (scheme stays at Home; unlike Leshiye it does not move
    // onto the city). Cleared + CanBeClaimed restored on EventCardSentToLocker.
    public string $ChosenLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Motion to Delay');
        $this->Image = '04052.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 52;

        $this->initializeFaction("Neutral");

        $this->Initiative = 90;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate("Bureaucracy"),
            clienttranslate("Authority")
        ];

        $this->Text = clienttranslate("<p>Choose a location with one or less Renown. Add a Renown to a different location.</p>
<hr />
<p>The chosen location cannot be controlled.</p>
<p><b>Forced:</b> At the end of High Drama • If an opponent controls more locations than you, draw a card.</p>");

        $this->resetCard();
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        $properties['chosenLocation'] = $this->ChosenLocation;
        return $properties;
    }

    /**
     * City locations with Renown ≤ 1 (first resolve pick).
     *
     * @return string[]
     */
    public function getLowRenownCityLocationNames(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Renown <= 1)
            {
                $names[] = $location->Name;
            }
        }

        return $names;
    }

    /**
     * City locations other than the chosen (delayed) location.
     *
     * @return string[]
     */
    public function getDifferentCityLocationNames(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name !== $this->ChosenLocation)
            {
                $names[] = $location->Name;
            }
        }

        return $names;
    }

    public function countControlledCityLocations(Theah $theah, int $playerId): int
    {
        $count = 0;
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Controller == $playerId)
            {
                $count++;
            }
        }

        return $count;
    }

    private function clearChosenLocation(Theah $theah): void
    {
        if ($this->ChosenLocation !== '')
        {
            // WHY: Unconditional restore matches Leshiye. Overlap with Indomitable Will /
            // Leshiye on the same location is rare; those cards re-apply their flags.
            $theah->setLocationCanBeClaimed($this->ChosenLocation, true);
            $this->ChosenLocation = '';
            $this->IsUpdated = true;
            $this->notifyMotionToDelayLabel($theah->game);
        }
    }

    // WHY: Client reminder overlay on the locked city location (Parley Gone Wrong /
    // Cat's Embargo label idiom). Empty locationName clears the label.
    private function notifyMotionToDelayLabel(Game $game): void
    {
        $game->notify->all("motionToDelayLabelUpdated", '', [
            'locationName' => $this->ChosenLocation,
        ]);
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // WHY: Belt-and-suspenders with CanBeClaimed — Leshiye does the same so any
        // claim path that bypasses canLocationBeClaimedBy still fails.
        if ($event instanceof EventLocationClaimed
            && $this->ChosenLocation !== ''
            && $event->location == $this->ChosenLocation)
        {
            throw new UserException($event->theah->game->translate("Motion to Delay does not allow the chosen location to be controlled."));
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a City location with one or less Renown. Then they will add a Renown to a different location. The chosen location cannot be controlled.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($event->playerId),
            ]);

            $this->clearChosenLocation($event->theah);
            $game->updateCardObjectInDb($this);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "04052");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        // WHY: Chosen schemes sit at LOCATION_PLAYER_HOME until Dusk (same gate as
        // Planning-End Forced / Burn like Mice HD-End Forced).
        if ($event instanceof EventHighDramaPhaseEnd && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $game = $event->theah->game;
            $myCount = $this->countControlledCityLocations($event->theah, $this->ControllerId);

            $opponentControlsMore = false;
            foreach ($game->loadPlayersBasicInfos() as $playerId => $_)
            {
                $playerId = (int)$playerId;
                if ($playerId == $this->ControllerId)
                {
                    continue;
                }
                if ($this->countControlledCityLocations($event->theah, $playerId) > $myCount)
                {
                    $opponentControlsMore = true;
                    break;
                }
            }

            if (! $opponentControlsMore)
            {
                return;
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: an opponent controls more locations than ${player_name}. They draw a card.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
            ]);

            $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $event->theah->queueEvent($drawEvent);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            $this->clearChosenLocation($event->theah);
            $event->theah->game->updateCardObjectInDb($this);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04052)
        {
            $args["locationIds"] = $this->getLowRenownCityLocationNames($game->theah);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04052_2)
        {
            $args["locationIds"] = $this->getDifferentCityLocationNames($game->theah);
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04052)
        {
            // WHY: Pass only when no ≤1-Renown city locs remain — same discipline as
            // Réputation Méritée `_01072` (no Renown locations).
            if (count($this->getLowRenownCityLocationNames($game->theah)) > 0)
            {
                throw new UserException($game->translate("There is a City location with one or less Renown that you must choose."));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: There are no City locations with one or less Renown.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $game->gamestate->nextState();
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04052)
        {
            $location = $ids[0];
            $valid = $this->getLowRenownCityLocationNames($game->theah);
            if (! in_array($location, $valid, true))
            {
                throw new UserException($game->translate("Location must be a City location with one or less Renown."));
            }

            $this->ChosenLocation = $location;
            $this->IsUpdated = true;
            $game->theah->setLocationCanBeClaimed($location, false);
            $game->updateCardObjectInDb($this);
            $this->notifyMotionToDelayLabel($game);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has chosen ${location}. It cannot be controlled. They must now add a Renown to a different location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "location" => $location,
                "i18n" => ["location"],
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "04052_2");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_04052_2)
        {
            $location = $ids[0];
            $valid = $this->getDifferentCityLocationNames($game->theah);
            if (! in_array($location, $valid, true))
            {
                throw new UserException($game->translate("Location must be a different City location."));
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

            $game->gamestate->nextState();
        }
    }
}
