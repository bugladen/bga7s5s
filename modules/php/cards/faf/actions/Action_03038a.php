<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03038a extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Draw a Card, Then Discard a Card");
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

        // WHY: draw-then-discard needs at least one card to discard after the draw.
        // Empty hand is fine if the deck (or discard-to-reshuffle) can supply a draw.
        return $this->playerWillHaveCardToDiscardAfterDraw($theah, $playerId);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $event->theah->queueEvent($drawEvent);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03038a", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03038a)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            $args['ids'] = array_values(array_map(fn($card) => $card->Id, $hand));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03038a)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $card = $game->theah->getCardById($id);
            if ($card === null || $card->Location != Game::LOCATION_HAND || $card->ControllerId != $owner->ControllerId)
            {
                throw new UserException($game->translate("Card must be in your hand."));
            }

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $owner->ControllerId,
                $id,
                $owner->Id,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardDiscarded");
            return;
        }
    }

    private function playerWillHaveCardToDiscardAfterDraw(Theah $theah, int $playerId): bool
    {
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) > 0)
        {
            return true;
        }

        $deck = $theah->game->getGameDeckObject();
        $factionDeck = $theah->game->getPlayerFactionDeckName($playerId);
        $discardPile = $theah->game->getPlayerDiscardDeckName($playerId);

        return $deck->countCardsInLocation($factionDeck) + $deck->countCardsInLocation($discardPile) > 0;
    }
}
