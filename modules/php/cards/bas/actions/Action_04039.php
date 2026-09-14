<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04039 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage and Lose Control of a City Location; Claim Performer's Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // WHY: "lose control of a City location" is a cost — grey when you control none
        // that can become uncontrolled (Indomitable Will / empty claim board).
        if (count($this->getLocationsPlayerCanLoseControl($theah, $playerId)) == 0)
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: Engage cost → !Engaged. Sole payoff is Claim → gate on claimability
        // (Action_03053 / Action_01103a). Location-to-lose pool is independent of
        // performer (any controlled City location), checked in isAvailableToPlayer.
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => ! $performer->Engaged
                && $theah->canLocationBeClaimedBy($playerId, $performer->Location)
        ));
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        // WHY: Action-only Risk — combat-card pay has no Maneuver discount channel.
        // Do not invent a Maneuver solely to carry getManeuverFromCombatCardDiscount.
        if ($action->Id == $this->Id)
        {
            if ($performer === null)
            {
                return $discount;
            }

            if ($performer->hasTrait("Zealot")
                || $performer->hasTrait("Academic")
                || $performer->hasTrait("Bard"))
            {
                $discount += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf(
                    $theah->game->translate("%s: -1 because your performer is a Zealot, Academic, or Bard."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04039", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04039)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["performerId"] = $performer->Id;
            $args["locationIds"] = $this->getLocationsPlayerCanLoseControl($game->theah, $performer->ControllerId);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04039)
        {
            $location = $ids[0];
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($game->theah);

            if ($performer === null || $performer->ControllerId != $owner->ControllerId)
            {
                throw new UserException($game->translate("Invalid performer"));
            }

            if ($performer->Engaged)
            {
                throw new UserException($game->translate("Performer is already engaged."));
            }

            $validLocations = $this->getLocationsPlayerCanLoseControl($game->theah, $performer->ControllerId);
            if (! in_array($location, $validLocations))
            {
                throw new UserException($game->translate("You cannot lose control of that location."));
            }

            // WHY: Engage + lose-control costs resolve together with the claim on confirm
            // (Action_04cd04 shape). Delaying engage until confirm avoids zombie leaving
            // the performer engaged with no payoff (contrast Action_03034 engage-at-announce).
            $engageEvent = EventFactory::createCardEngagedEvent(
                $performer->ControllerId,
                $performer->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            if ($game->theah->canLocationBecomeUncontrolledBy($performer->ControllerId, $location))
            {
                $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent(
                    $performer->ControllerId,
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

            if ($game->theah->canLocationBeClaimedBy($performer->ControllerId, $performer->Location))
            {
                $claimEvent = EventFactory::createLocationClaimedEvent(
                    $performer->ControllerId,
                    $performer->Id,
                    $performer->Location
                );
                $game->theah->queueEvent($claimEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                    'i18n' => ['location'],
                    'location' => $performer->Location,
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    /**
     * City locations the player currently controls that may become uncontrolled.
     *
     * @return list<string>
     */
    private function getLocationsPlayerCanLoseControl(Theah $theah, int $playerId): array
    {
        $eligible = [];

        foreach ($theah->getCityLocations() as $cityLocation)
        {
            if ($cityLocation->Controller != $playerId)
            {
                continue;
            }

            if (! $theah->canLocationBecomeUncontrolledBy($playerId, $cityLocation->Name))
            {
                continue;
            }

            $eligible[] = $cityLocation->Name;
        }

        return $eligible;
    }
}
