<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02060a extends RiskReaction
{
    private int $ChallengerId = 0;
    private int $TargetId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Both When Challenge Refused");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('Your challenge was refused. ${you} may wound your challenger and the refusing character: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Both'), 'woundBoth');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeRejected && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $challenger = $event->theah->getCharacterById($event->challengerId);
                if ($challenger && $challenger->ControllerId == $owner->ControllerId)
                {
                    $this->ChallengerId = $event->challengerId;
                    $this->TargetId = $event->targetId;
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($game->theah);
            $challenger = $game->theah->getCharacterById($this->ChallengerId);
            $target = $game->theah->getCharacterById($this->TargetId);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($this->ChallengerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($this->TargetId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction. Wounding ${challenger_inject_code} and ${target_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "challenger_inject_code" => $challenger->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }

        if ($event instanceof EventPlayerTurnEnd && ($this->ChallengerId != 0 || $this->TargetId != 0))
        {
            $owner = $this->getOwningCard($event->theah);
            $this->ChallengerId = 0;
            $this->TargetId = 0;
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundBoth')
        {
            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        $game->gamestate->nextState("done");
    }
}
