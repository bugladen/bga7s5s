<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01008 extends CharacterAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Reveal Top Card of your Faction Deck");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ( ! $owner->hasTrait("Sorcerer"))
        {
            return false;
        }

        if ( ! $theah->cardInCity($owner))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $deck = $game->getGameDeckObject();
            $owner = $this->getOwningCard($game->theah);
            $location = $game->getPlayerFactionDeckName($owner->ControllerId);
            $cardInfo = $deck->getCardOnTop($location);
            $card = $game->getCardObjectFromDb($cardInfo['id']);

            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} uses Action to Reveal the Top Card of their Faction Deck. (${card_inject_code})'), [
                "player_name" => $game->getActivePlayerName(),
                "owner_inject_code" => $owner->getInjectCode(),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01008", $this->Id);
            $event->theah->queueEvent($transition);

            $this->setUsed($event->theah, true);
            $this->resetPlayerPassCount($game);    
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01008 || $state == States::HIGH_DRAMA_PLAYER_TURN_01008_4)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $deck = $game->getGameDeckObject();

            $location = $game->getPlayerFactionDeckName($owner->ControllerId);
            $cardInfo = $deck->getCardOnTop($location);
            $card = $game->getCardObjectFromDb($cardInfo['id']);

            $args['card'] = $card->getPropertyArray($game);
        }

        return $args;
    }

    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01008_3)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $deck = $game->getGameDeckObject();

            $location = $game->getPlayerFactionDeckName($owner->ControllerId);
            $cardInfo = $deck->getCardOnTop($location);
            $card = $game->getCardObjectFromDb($cardInfo['id']);

            $game->notify->all("message", clienttranslate('${player_name} has revealed ${card_inject_code}.'), [
                "player_name" => $game->getActivePlayerName(),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            if ($card->hasTrait("Sorcery"))
            {
                $event = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($event);

                $game->gamestate->nextState("cardDrawn");
                return;
            }

            $game->gamestate->nextState("choose");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01008_4)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $deck = $game->getGameDeckObject();

            $location = $game->getPlayerFactionDeckName($owner->ControllerId);
            $cardInfo = $deck->getCardOnTop($location);
            $card = $game->getCardObjectFromDb($cardInfo['id']);

            //Sink card
            $deck->insertCardOnExtremePosition($cardInfo['id'], $location, false);

            $game->notify->all("message", clienttranslate('${player_name} has chosen to sink ${card_inject_code}.'), [
                "player_name" => $game->getActivePlayerName(),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $event = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState();
        }
    }
}