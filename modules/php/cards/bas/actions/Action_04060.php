<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04060 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Make Controlled Location Uncontrolled");
        // WHY: Printed text chooses a location only — no "your performer" effect.
        // RiskCityAction still gates on having a city character; no performer pick.
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligibleLocations($theah, $playerId)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04060", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04060)
        {
            $owner = $this->getOwningCard($game->theah);
            $args['locationIds'] = $this->getEligibleLocations($game->theah, $owner->ControllerId);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04060)
        {
            $location = $ids[0];
            $owner = $this->getOwningCard($game->theah);

            $validLocations = $this->getEligibleLocations($game->theah, $owner->ControllerId);
            if (! in_array($location, $validLocations))
            {
                throw new UserException($game->translate("That location is not a legal choice."));
            }

            // WHY: Emit-only for Indomitable Will — availability already filtered can*,
            // but board can change between grey-check and confirm (same as Action_01086).
            if ($game->theah->canLocationBecomeUncontrolledBy($owner->ControllerId, $location))
            {
                $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent(
                    $owner->ControllerId,
                    $location
                );
                $game->theah->queueEvent($uncontrolledEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot become uncontrolled.'), [
                    'i18n' => ['location'],
                    'location' => $location,
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    /**
     * Controlled City locations with no Renown or no characters that may become uncontrolled.
     *
     * @return list<string>
     */
    private function getEligibleLocations(Theah $theah, int $playerId): array
    {
        $eligible = [];

        foreach ($theah->getCityLocations() as $cityLocation)
        {
            // WHY: "a controlled City location" — any controller, not only yours
            // (Status Matters `_01086` / C.10 unclaim discipline).
            if ($cityLocation->Controller == 0)
            {
                continue;
            }

            if (! $theah->canLocationBecomeUncontrolledBy($playerId, $cityLocation->Name))
            {
                continue;
            }

            $characters = $theah->getCharactersAtLocation($cityLocation->Name);
            $noRenown = $cityLocation->Renown == 0;
            $noCharacters = count($characters) == 0;

            // WHY: Printed "no Renown or no characters" — either half qualifies.
            if ($noRenown || $noCharacters)
            {
                $eligible[] = $cityLocation->Name;
            }
        }

        return $eligible;
    }
}
