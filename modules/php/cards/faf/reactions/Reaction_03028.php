<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03028 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Draw a card after a character equips at The Grand Bazaar');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may draw a card after a character equips an attachment at The Grand Bazaar: ');
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

        if (! ($event instanceof EventAttachmentEquipped)) return;
        if (! $this->isAvailable()) return;

        $terence = $this->getOwningCharacter($event->theah);
        if ($terence === null) return;
        if ($event->theah->game->characterIsInDiscardOrLocker($terence)) return;
        if (! $event->theah->cardInCity($terence)) return;
        if ($terence->Location != Game::LOCATION_CITY_BAZAAR) return;

        $attachment = $event->theah->getAttachmentById($event->attachmentId);
        if ($attachment === null || $attachment->FakeAttachment) return;

        $character = $event->theah->getCharacterById($event->characterId);
        if ($character === null) return;
        if ($character->Location != Game::LOCATION_CITY_BAZAAR) return;

        $transition = EventFactory::createReactionTransitionEvent($terence->ControllerId, $terence->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'pass')
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId == 'drawCard')
        {
            $terence = $this->getOwningCharacter($game->theah);

            $drawEvent = EventFactory::createCardDrawnEvent(
                $terence->ControllerId,
                sprintf($game->translate('%s: City Reaction: Draw a card'), $terence->getInjectCode())
            );
            $game->theah->queueEvent($drawEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
