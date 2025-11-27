<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelActionsDone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01135 extends CancelReaction

{
    private int $cancelledCombatCardId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Combat Card, Gamble Instead");
        $this->cancelledCombatCardId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may cancel the Combat Card and have player Gamble instead: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Gamble'), 'gamble');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventCombatCardAnnounced && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND && $event->playerId != $owner->ControllerId)
            {
                $this->cancelledCombatCardId = $event->cardId;
                $owner->IsUpdated = true;

                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $card = $game->getCardObjectFromDb($this->cancelledCombatCardId);

            $game->theah->deleteTransitionEvents($this->cancelledCombatCardId);

            $owner = $this->getOwningCard($game->theah);

            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->ControllerId, $this->cancelledCombatCardId, $owner->Id, $asPayment = false, $asPlayed = false, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $transitionEvent = EventFactory::createTransitionEvent($card->ControllerId, $owner->Id, "01135", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} cancels the Combat Card and has ${opponent_name} Gamble instead'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "opponent_name" => $game->getPlayerNameById($card->ControllerId),
            ]);
    }


        if ($event instanceof EventDuelActionsDone && $this->cancelledCombatCardId != 0)
        {
            $this->cancelledCombatCardId = 0;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'gamble')
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->stackEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->stackEvent($event);
        }

        $game->gamestate->nextState("done");
    }

}