<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04003b extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Desideria; draw a card after Sorcerer ability");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may wound Desideria to draw a card: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound and Draw'), 'woundAndDraw');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventSorcererAbilityPlayed) || ! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null)
        {
            return;
        }

        if ($event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        if (! $event->theah->cardInCity($owner))
        {
            return;
        }

        // WHY: sourceId = card whose ability fired; performerId = character performing.
        // Checking both covers abilities on Desideria and sorceries she performs from hand.
        if ($event->sourceId != $owner->Id && $event->performerId != $owner->Id)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
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

        if ($reactionId == 'woundAndDraw')
        {
            $owner = $this->getOwningCharacter($game->theah);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $owner->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $game->theah->queueEvent($drawEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} wounds ${character_inject_code} to draw a card.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $owner->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
