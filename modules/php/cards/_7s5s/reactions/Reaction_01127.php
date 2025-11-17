<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01127 extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Unequip and Play as a Combat Card");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $owner = $this->getOwningCard($theah);
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Unequip and Play as a Combat Card: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Unequip and Play as a Combat Card'), 'unequipAndPlayAsCombatCard');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelNewRound && $this->isAvailable() && $this->ownerIsAttached($event->theah))
        {
            $owner = $this->getOwningCard($event->theah);
            $character = $this->getOwningCharacter($event->theah);
            if ($event->actorId == $character->Id)
            {
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'unequipAndPlayAsCombatCard')
        {
            $owner = $this->getOwningCard($game->theah);
            $character = $this->getOwningCharacter($game->theah);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($character->ControllerId, $character->Id, $owner->Id);
            $game->theah->queueEvent($unequipEvent);

            $removedFromPlayEvent = EventFactory::createCardRemovedFromPlayEvent($character->ControllerId, $owner->Id, Game::LOCATION_DUELING_LINE);
            $game->theah->queueEvent($removedFromPlayEvent);

            $game->globals->set(Game::CHOSEN_CARD, $owner->Id);

            $challengerId = $game->theah->getDuelChallengerId();
            $defenderId = $game->theah->getDuelDefenderId();
            $challengerThreatIsLethal = $character->Id == $challengerId ? null : true;
            $defenderThreatIsLethal = $character->Id == $defenderId ? null : true;
        
            $lethalEvent = EventFactory::createThreatModifiedEvent(0, 0, $challengerThreatIsLethal, $defenderThreatIsLethal);
            $game->theah->queueEvent($lethalEvent);
            
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01127", $this->Id);
            $game->theah->queueEvent($transition);

        }


        $game->gamestate->nextState("done");
    }
}