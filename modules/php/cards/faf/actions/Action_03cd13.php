<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsPlayers;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03cd13 extends EventCityAction implements IAbilityThatTargetsPlayers
{
    private array $playersUsed = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Claim Location if You Have Fewer Renown");
        $this->RequiresPerformerSelected = true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        $performers = array_values(array_filter($performers, fn($performer) => !$performer->Engaged));

        return $performers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        if (in_array($playerId, $this->playersUsed))
        {
            return false;
        }

        if (count($this->getEligibleTargetPlayerIds($theah, $playerId)) == 0)
        {
            return false;
        }

        return true;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            if (in_array($event->playerId, $this->playersUsed))
            {
                throw new UserException($event->theah->game->translate("You have already used this Action today."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->playersUsed = [];
            $card = $this->getOwningCard($event->theah);
            if ($card != null)
            {
                $card->IsUpdated = true;
                $this->notifyUsedList($event->theah->game, $card->Id);
            }
        }

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03cd13", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD13)
        {
            $playerId = (int)$game->getActivePlayerId();
            $eligibleIds = $this->getEligibleTargetPlayerIds($game->theah, $playerId);

            $players = $game->loadPlayersBasicInfos();
            $eligiblePlayers = [];
            foreach ($eligibleIds as $eligibleId)
            {
                if (isset($players[$eligibleId]))
                {
                    $eligiblePlayers[] = [
                        'id' => (int)$players[$eligibleId]['player_id'],
                        'name' => $players[$eligibleId]['player_name'],
                    ];
                }
            }

            $args["players"] = $eligiblePlayers;

            $owner = $this->getOwningCard($game->theah);
            $args["cardId"] = $owner->Id;

            $args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD13)
        {
            $this->handleTargetChosen($game, $id);
            return;
        }
    }

    private function handleTargetChosen(Game $game, int $targetPlayerId): void
    {
        $playerId = (int)$game->getActivePlayerId();

        $eligibleIds = $this->getEligibleTargetPlayerIds($game->theah, $playerId);
        if (!in_array($targetPlayerId, $eligibleIds))
        {
            throw new UserException($game->translate("Invalid target player"));
        }

        $owner = $this->getOwningCard($game->theah);
        $location = $owner->Location;

        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCardById($performerId);
        if ($performer == null || $performer->ControllerId != $playerId)
        {
            throw new UserException($game->translate("Invalid performer"));
        }

        $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} targets ${target_name}.'), [
            "card_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($playerId),
            "target_name" => $game->getPlayerNameById($targetPlayerId),
        ]);

        $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($engageEvent);

        $claimEvent = EventFactory::createLocationClaimedEvent($playerId, $performerId, $location);
        $game->theah->queueEvent($claimEvent);

        $this->playersUsed[] = $playerId;
        $this->notifyUsedList($game, $owner->Id);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
        $game->theah->queueEvent($actionResolvedEvent);

        $this->setUsed($game->theah, false);

        $game->gamestate->nextState("playerChosen");
    }

    private function getEligibleTargetPlayerIds(Theah $theah, int $playerId): array
    {
        $game = $theah->game;
        $myReknown = $game->getPlayerReknown($playerId);

        $players = $game->loadPlayersBasicInfos();
        $eligible = [];
        foreach ($players as $otherPlayerId => $_)
        {
            $otherPlayerId = (int)$otherPlayerId;
            if ($otherPlayerId == $playerId)
            {
                continue;
            }

            if ($game->getPlayerReknown($otherPlayerId) > $myReknown)
            {
                $eligible[] = $otherPlayerId;
            }
        }

        return $eligible;
    }

    private function notifyUsedList(Game $game, int $cardId): void
    {
        $game->notify->all("crabsInABucketUsedListUpdated", '', [
            'cardId' => $cardId,
            'usedList' => $this->getUsedListData($game),
        ]);
    }

    public function getUsedListData(Game $game): array
    {
        $list = [];
        foreach ($this->playersUsed as $playerId)
        {
            $list[] = [
                'playerId' => $playerId,
                'playerName' => $game->getPlayerNameById($playerId),
                'playerColor' => $game->getPlayerColorById($playerId),
            ];
        }
        return $list;
    }
}
