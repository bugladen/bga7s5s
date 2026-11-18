<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Dandyv2b extends CharacterAction
{
    use DandyWealthPayment;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Pay 2 Wealth; Move Claude to any City location";
    }

    /**
     * Any City location other than Claude's current spot. Printed "any City"
     * — no ally-at-destination gate (unlike original Action_05Dandya).
     *
     * @return string[]
     */
    private function getDestinations(Theah $theah, string $currentLocation): array
    {
        $destinations = [];
        foreach (array_keys($theah->getCityLocations()) as $locationName)
        {
            if ($locationName == $currentLocation)
            {
                continue;
            }

            $destinations[] = $locationName;
        }

        return $destinations;
    }

    // WHY: Shackles/Harpoon stamp "cannot move" on the character. Claude's Action
    // still pays first then picks a location — without this gate the pay step
    // succeeds and location selection soft-locks on Character::eventCheck.
    // Mirror Action_05Dandya / Technique_01036.
    private function ownerCannotMove(Character $owner): bool
    {
        return $owner->hasCondition(Game::SHACKLES_CONDITION)
            || $owner->hasCondition(Game::HARPOON_CONDITION);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        // WHY: Printed as Action (not City Action) — legal from Home or City.
        if ($this->ownerCannotMove($owner))
        {
            return false;
        }

        if (! $this->playerCanPayWealth($playerId, $theah, 2))
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
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandyv2b", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B
            || $state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B_2)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B)
        {
            // WHY: Refuse before the discard so a stale client cannot soft-lock
            // after wealth leaves the hand (see Shackles journal soft-fail note).
            if ($owner->hasCondition(Game::SHACKLES_CONDITION))
            {
                throw new UserException(sprintf(
                    $game->translate("%s is Shackled and cannot move."),
                    $owner->Name
                ));
            }
            if ($owner->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf(
                    $game->translate("%s is Harpooned and cannot move for the remainder of the duel."),
                    $owner->Name
                ));
            }
            if (count($this->getDestinations($game->theah, $owner->Location)) === 0)
            {
                throw new UserException("There is no City location to move to.");
            }

            $this->queueWealthPayment($game, $owner, $ids, 2);

            // WHY: Return to the event queue so the discard resolves before the
            // location pick. The hand must already be missing the paid cards.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandyv2b_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B_2)
        {
            // WHY: Tables that paid before availability was gated are stuck here.
            // Completing without the move (wealth already spent) unsticks them;
            // Character::eventCheck would otherwise throw forever on every pick.
            if ($this->ownerCannotMove($owner))
            {
                $reason = $owner->hasCondition(Game::HARPOON_CONDITION)
                    ? $game->translate("%s is Harpooned and cannot move for the remainder of the duel. The Action ends without moving.")
                    : $game->translate("%s is Shackled and cannot move. The Action ends without moving.");
                $game->notify->all("message", sprintf($reason, $owner->Name), []);

                $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($resolved);
                $game->gamestate->nextState();
                return;
            }

            $locationName = $ids[0] ?? '';
            $eligible = $this->getDestinations($game->theah, $owner->Location);
            if (! in_array($locationName, $eligible, true))
            {
                throw new UserException("Choose a City location.");
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
