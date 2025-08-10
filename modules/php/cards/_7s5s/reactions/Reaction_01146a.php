<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01146a extends CardReaction
{

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Draw Card After Equipping Weapon");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to draw a card after equipping a weapon: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Draw card'), 'drawCard');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventAttachmentEquipped)
        {
            $scheme = $this->getOwningCard($event->theah);
            $attachment = $event->theah->getAttachmentById($event->attachmentId);
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->ControllerId == $scheme->ControllerId && $attachment->hasTrait("Weapon"))
            {
                $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'drawCard')
        {
            $scheme = $this->getOwningCard($game->theah);
            $card = $game->playerDrawCard($scheme->ControllerId);
            $addEvent = EventFactory::createCardDrawnEvent($scheme->ControllerId, $card, sprintf($game->translate("%s: Reaction: Draw card after equipping weapon"), $scheme->getInjectCode()));
            $game->theah->queueEvent($addEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}