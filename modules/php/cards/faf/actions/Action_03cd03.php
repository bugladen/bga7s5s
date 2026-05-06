<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsPlayers;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03cd03 extends EventCityAction implements IAbilityThatTargetsPlayers
{
    const REMAINING = "chanceMeetingRemaining";

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Target a Player to Trigger Musters");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03cd03", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD03)
        {
            $players = $game->loadPlayersBasicInfos();
            $args["players"] = array_map(
                fn($player) => ['id' => (int)$player['player_id'], 'name' => $player['player_name']],
                array_values($players)
            );
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD03_2)
        {
            $playerId = (int)$game->getActivePlayerId();
            $owner = $this->getOwningCard($game->theah);

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $args["targetId"] = $targetId;
            $args["targetName"] = $game->getPlayerNameById($targetId);

            $args["cardId"] = $owner->Id;
            $args["location"] = $owner->Location;

            $musterIds = $this->getMusterableCharacterIds($game->theah, $playerId);
            $args["musterIds"] = $musterIds;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD03)
        {
            $this->handleTargetChosen($game, $id);
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03CD03_2)
        {
            $this->handleMusterChoice($game, $id);
            return;
        }
    }

    private function handleTargetChosen(Game $game, int $targetPlayerId): void
    {
        $players = $game->loadPlayersBasicInfos();
        if (!isset($players[$targetPlayerId]))
        {
            throw new UserException($game->translate("Invalid target player"));
        }

        $owner = $this->getOwningCard($game->theah);
        $location = $owner->Location;

        $game->globals->set(Game::CHOSEN_TARGET, $targetPlayerId);

        $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} has targeted ${target_name}.'), [
            "owner_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById((int)$game->getActivePlayerId()),
            "target_name" => $game->getPlayerNameById($targetPlayerId),
        ]);

        $eligible = $this->getEligibleMusterPlayerIdsInTurnOrder($game, $targetPlayerId);

        $game->globals->set(self::REMAINING, json_encode($eligible));

        if (count($eligible) == 0)
        {
            $game->notify->all("message", clienttranslate('${owner_inject_code}: No player controls fewer characters than ${target_name}. The card is discarded.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "target_name" => $game->getPlayerNameById($targetPlayerId),
            ]);

            $this->queueDiscardAndResolve($game, $owner, $location);

            $game->gamestate->nextState("playerChosen");
            return;
        }

        foreach ($eligible as $eligiblePlayerId)
        {
            $transition = EventFactory::createTransitionEvent((int)$eligiblePlayerId, $owner->Id, "03cd03_2", $this->Id);
            $game->theah->queueEvent($transition);
        }

        $game->gamestate->nextState("playerChosen");
    }

    private function handleMusterChoice(Game $game, int $cardId): void
    {
        $playerId = (int)$game->getActivePlayerId();
        $owner = $this->getOwningCard($game->theah);
        $location = $owner->Location;

        $musterIds = $this->getMusterableCharacterIds($game->theah, $playerId);

        if ($cardId == 0)
        {
            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} declines to Muster.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
            ]);
        }
        else
        {
            if (!in_array($cardId, $musterIds))
            {
                throw new UserException($game->translate("Invalid character to Muster"));
            }

            $musterCard = $game->theah->getCardById($cardId);
            if ($musterCard == null || !$musterCard instanceof Character)
            {
                throw new UserException($game->translate("Invalid character to Muster"));
            }

            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} Musters ${muster_inject_code} at ${location}.'), [
                "i18n" => ["location"],
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "muster_inject_code" => $musterCard->getInjectCode(),
                "location" => $location,
            ]);

            $musterEvent = EventFactory::createCharacterMusteredEvent($playerId, $musterCard->Id, $location);
            $game->theah->queueEvent($musterEvent);
        }

        $remaining = json_decode($game->globals->get(self::REMAINING) ?: '[]', true);
        $remaining = array_values(array_filter($remaining, fn($pid) => (int)$pid != $playerId));
        $game->globals->set(self::REMAINING, json_encode($remaining));

        if (count($remaining) == 0)
        {
            $this->queueDiscardAndResolve($game, $owner, $location);
        }

        $game->gamestate->nextState("musterResolved");
    }

    private function queueDiscardAndResolve(Game $game, Card $owner, string $location): void
    {
        $triggererId = (int)$game->globals->get(Game::CURRENT_PLAYER);

        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent(
            $triggererId,
            $owner->Id,
            $location,
            $owner->Id,
            $asEffect = true
        );
        $game->theah->queueEvent($discardEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($triggererId);
        $game->theah->queueEvent($actionResolvedEvent);

        $game->globals->delete(self::REMAINING);
        $game->globals->delete(Game::CHOSEN_TARGET);
    }

    private function getEligibleMusterPlayerIdsInTurnOrder(Game $game, int $targetPlayerId): array
    {
        $sql = "SELECT player_id FROM player ORDER BY turn_order";
        $rows = $game->getCollectionFromDB($sql);

        $targetCount = count($game->theah->getCharactersInPlayByPlayerId($targetPlayerId));

        $eligible = [];
        foreach ($rows as $playerId => $row)
        {
            $playerId = (int)$playerId;
            if ($playerId == $targetPlayerId)
            {
                continue;
            }

            $count = count($game->theah->getCharactersInPlayByPlayerId($playerId));
            if ($count < $targetCount)
            {
                $eligible[] = $playerId;
            }
        }

        return $eligible;
    }

    private function getMusterableCharacterIds(Theah $theah, int $playerId): array
    {
        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_APPROACH, $playerId);
        $cards = array_values(array_filter($cards, fn($card) => $card instanceof Character));
        return array_map(fn($card) => (int)$card->Id, $cards);
    }
}
