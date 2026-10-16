<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Dandyb extends CharacterAction
{
    use DandyWealthPayment;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Pay 2 Wealth; Collect a Renown from this location";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        if ($theah->getCityLocation($owner->Location)->Renown <= 0)
        {
            return false;
        }

        return $this->playerCanPayWealth($playerId, $theah, 2);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05Dandyb", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DANDYB)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state != States::HIGH_DRAMA_PLAYER_TURN_05DANDYB)
        {
            return;
        }

        $owner = $this->getOwningCharacter($game->theah);
        $this->queueWealthPayment($game, $owner, $ids, 2);

        if ($game->theah->cardInCity($owner) && $game->theah->getCityLocation($owner->Location)->Renown > 0)
        {
            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent(
                $owner->ControllerId,
                $owner->Location,
                1,
                $owner->getInjectCode()
            );
            $game->theah->queueEvent($removeEvent);

            $gainEvent = EventFactory::createPlayerGainsReknownEvent($owner->ControllerId, 1);
            $game->theah->queueEvent($gainEvent);
        }
        else
        {
            $game->notify->all("message", '${claude_inject_code}: ${location} has no Renown left to collect.', [
                'i18n' => ['location'],
                'claude_inject_code' => $owner->getInjectCode(),
                'location' => $owner->Location,
            ]);
        }

        $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
        $game->theah->queueEvent($resolved);

        $game->gamestate->nextState();
    }
}
