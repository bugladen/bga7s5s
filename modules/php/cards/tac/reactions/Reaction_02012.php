<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02012 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Wound and En Garde after Turais Issues Challenge');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Wound and En Garde Turais: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound and En Garde'), 'woundAndEnGarde');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner->hasTrait('Berserker') && $owner->Wounds + 1 < $owner->ModifiedResolve)
            {
                $reactionTransition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundAndEnGarde')
        {
            $owner = $this->getOwningCharacter($game->theah);
            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to Wound and En Garde Turais'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $owner = $this->getOwningCharacter($game->theah);
            $event = EventFactory::createCharacterBeingWoundedEvent($owner->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardEngardedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}