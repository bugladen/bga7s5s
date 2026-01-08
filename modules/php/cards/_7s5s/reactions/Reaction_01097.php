<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01097 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Draw a Card after a Card is Discarded due to your effect");
    }

    public function getReactionDescription(Theah $theah): string
    {
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

        if ($event instanceof EventCardDiscardedFromHand && $this->isAvailable() && $event->asEffect)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($event->ownerId != $owner->ControllerId)
            {
                $source = $event->theah->getCardById($event->sourceId);
                if ($source?->ControllerId == $owner->ControllerId)
                {
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

            $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $game->theah->queueEvent($drawEvent);

            $this->setUsed($game->theah, true);

        }

        $game->gamestate->nextState("done");
    }
}
