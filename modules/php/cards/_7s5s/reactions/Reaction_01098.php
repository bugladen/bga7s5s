<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01098 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gain a Reknown after Cat's Embargo card is played");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} will gain a Reknown because the Cat\'s Embargo chosen card was played: '));
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Gain Reknown'), 'gainReknown');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardDiscardedFromHand && $this->isAvailable()) 
        {
            $game = $event->theah->game;
            $card = $game->getCardObjectFromDb($event->cardId);
            if ($card->hasCondition(Game::CATS_EMBARGO_TARGET))
            {
                $owner = $this->getOwningCard($event->theah);
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventAttachmentEquipped && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            $attachment = $event->theah->getAttachmentById($event->attachmentId);
            if ($attachment->hasCondition(Game::CATS_EMBARGO_TARGET))
            {
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventDuelCalculateCombatCardStats && $this->isAvailable())
        {
            $game = $event->theah->game;
            $card = $game->getCardObjectFromDb($event->combatCardId);
            if ($card->hasCondition(Game::CATS_EMBARGO_TARGET))
            {
                $owner = $this->getOwningCard($event->theah);
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'gainReknown')
        {
            $owner = $this->getOwningCard($game->theah);
            
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} used Reaction to gain a Reknown after Cat\'s Embargo card is played'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
            ]);

            $event = EventFactory::createPlayerGainsReknownEvent($owner->ControllerId, 1);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
