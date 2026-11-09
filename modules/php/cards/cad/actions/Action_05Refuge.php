<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Refuge extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Move Performer Home or to a City Location You Control";
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
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

        // WHY: Academic Action — Academic is a mechanical performer-trait gate (not Sorcerer).
        // RiskAction (not City) — home performers stay eligible; filter from parent, not city-only.
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Academic")
                && count($this->getValidDestinations($theah, $performer)) > 0
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

            if ($this->performerHasArtifactEquipped($theah, $performer))
            {
                $discount += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf(
                    "%s: -1 because your performer is equipped with an Artifact.",
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
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05Refuge", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REFUGE)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["performerId"] = $performer->Id;
            $args["locationIds"] = $this->getValidDestinations($game->theah, $performer);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05REFUGE)
        {
            $location = $ids[0];
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $validDestinations = $this->getValidDestinations($game->theah, $performer);
            if (! in_array($location, $validDestinations))
            {
                throw new UserException(sprintf("%s cannot move to this location.", $performer->Name));
            }

            $owner = $this->getOwningCard($game->theah);

            $moveEvent = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    /**
     * Home (if not already there) or any City location you claim-control, excluding current.
     *
     * @return list<string>
     */
    private function getValidDestinations(Theah $theah, Character $performer): array
    {
        $playerId = $performer->ControllerId;
        $destinations = [];

        if ($performer->Location != Game::LOCATION_PLAYER_HOME)
        {
            $destinations[] = Game::LOCATION_PLAYER_HOME;
        }

        foreach ($theah->getCityLocations() as $cityLocation)
        {
            $name = $cityLocation->Name;
            // WHY exclude current: "Move … to" is a no-op on the same spot.
            if ($name === $performer->Location)
            {
                continue;
            }

            // WHY: Control_ global (getControllerForLocation), not CityLocation->Controller —
            // same discipline as Action_04028 / Action_04039 claim-control filters.
            if ($theah->game->getControllerForLocation($name) == $playerId)
            {
                $destinations[] = $name;
            }
        }

        return $destinations;
    }

    private function performerHasArtifactEquipped(Theah $theah, Character $performer): bool
    {
        foreach ($performer->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById((int)$attachmentId);
            if ($attachment instanceof Attachment
                && ! $attachment->FakeAttachment
                && $attachment->hasTrait("Artifact"))
            {
                return true;
            }
        }

        return false;
    }
}
