<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Dandya extends CharacterAction
{
    use DandyWealthPayment;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Pay 1 Wealth; Move Claude to any City location";
    }

    /**
     * @return string[]
     */
    private function getDestinations(Theah $theah, string $currentLocation): array
    {
        $destinations = [];
        foreach (array_keys($theah->getCityLocations()) as $locationName)
        {
            if ($locationName != $currentLocation)
            {
                $destinations[] = $locationName;
            }
        }

        return $destinations;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInPlay($owner))
        {
            return false;
        }

        if (! $this->playerCanPayOneWealth($playerId, $theah))
        {
            return false;
        }

        return count($this->getDestinations($theah, $owner->Location)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandya", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYA
            || $state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYA_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYA_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['locationIds'] = $this->getDestinations($game->theah, $owner->Location);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        $owner = $this->getOwningCharacter($game->theah);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYA)
        {
            $this->queueOneWealthPayment($game, $owner, $ids);

            // WHY: Return to the event queue so the discard resolves before the
            // location pick. The hand must already be missing the paid card.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandya_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYA_2)
        {
            $locationName = $ids[0] ?? '';
            $eligible = $this->getDestinations($game->theah, $owner->Location);
            if (! in_array($locationName, $eligible, true))
            {
                throw new UserException("Choose a City location Claude is not already at.");
            }

            // WHY: Engage is not printed. The move does not engage him.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $locationName,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($resolved);

            $game->gamestate->nextState();
        }
    }
}
