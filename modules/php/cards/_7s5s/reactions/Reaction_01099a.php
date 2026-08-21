<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01099a extends CardReaction
{
    private int $discardedCardId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Draw a Card after a Card is Discarded due to your effect");
        $this->discardedCardId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        if ($this->discardedCardId != 0)
        {
            $card = $theah->getCardById($this->discardedCardId);
            return parent::getReactionDescription($theah) . $theah->game->translate($card->Name) . $theah->game->translate(' was discarded: ${you} may draw a card: ');
        }
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may draw a card: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Draw Card'), 'drawCard');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventCardDiscardedFromHand || $event instanceof EventCardAddedToCityDiscardPile) 
        && $this->isAvailable() && $event->asEffect)
        {
            //If the card is a city card, check to see if it was owned by a player
            if ($event instanceof EventCardAddedToCityDiscardPile)
            {
                $card = $event->theah->getCardById($event->cardId);
                if ($card->isControlled())
                {
                    return;
                }
            }

            $owner = $this->getOwningCard($event->theah);

            if ($event->sourceId != 0)
            {
                $source = $event->theah->getCardById($event->sourceId);
                if ($source?->ControllerId == $owner->ControllerId)
                {
                    $this->discardedCardId = $event->cardId;
                    $owner->IsUpdated = true;
                    
                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'drawCard' && $this->isAvailable())
        {
            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} uses ${reaction_inject_code} to draw a card.'), [
                'player_name' => $game->getActivePlayerName(),
                'reaction_inject_code' => $owner->getInjectCode(),
            ]);

            $drawEvent = EventFactory::createCardDrawnEvent($game->getActivePlayerId(), $owner->getInjectCode());
            $game->theah->queueEvent($drawEvent);

            $this->setUsed($game->theah, true);

            //Delete any reaction transitions for this reaction so they don't trigger again
            $game->theah->deleteTransitionEvents($this->Id);
        }

        $game->gamestate->nextState("done");
    }
}
