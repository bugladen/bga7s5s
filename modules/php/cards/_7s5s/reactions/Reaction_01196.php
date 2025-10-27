<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01196 extends CardReaction

{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Discard a Card Instead of Engaging');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may discard a card instead of engaging: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        $deck = $theah->game->getGameDeckObject();
        $hand = $deck->getPlayerHand($theah->game->getActivePlayerId());
        foreach ($hand as $cardId => $handCard)
        {
            $card = $theah->game->getCardObjectFromDb($cardId);
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate($card->Name), "discardCard-$cardId");
        }
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $this->isAvailable())
        {
            $angeline = $this->getOwningCard($event->theah);
            if ($angeline->isControlled() && $event->cardId == $angeline->Id && $event->engage && ! $angeline->Engaged)
            {
                $transition = EventFactory::createReactionTransitionEvent($angeline->ControllerId, $angeline->Id, $this->Id);
                $event->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'pass')
        {
            $game->gamestate->nextState("done");
            return;
        }

        // Check to make sure $reactionId has the discardCard- prefix
        if (strpos($reactionId, 'discardCard-') !== 0)
        {
            throw new \BgaUserException(sprintf($game->translate('Invalid reactionId: %d'), $reactionId));
        }

        $cardId = str_replace('discardCard-', '', $reactionId);

        $card = $game->getCardObjectFromDb($cardId);

        if ($card == null)
        {
            throw new \BgaUserException(sprintf($game->translate('Card not found: %d'), $cardId));
        }

        $game->notify->all("message", clienttranslate('${player_name} uses Angeline Dèmone\'s Reaction.'), 
        [
            'player_name' => $game->getPlayerNameById($game->getActivePlayerId()),
        ]);

        $angeline = $this->getOwningCharacter($game->theah);
        
        $card = $game->getCardObjectFromDb($cardId);
        $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $angeline->Id);
        $game->theah->eventCheck($discardEvent);
        
        $engardeEvent = EventFactory::createCardEngardedEvent($angeline->ControllerId, $angeline->Id, $angeline->Id);
        $game->theah->eventCheck($engardeEvent);

        $game->theah->queueEvent($discardEvent);
        $game->theah->queueEvent($engardeEvent);

        $game->gamestate->nextState("done");        
    }
}
