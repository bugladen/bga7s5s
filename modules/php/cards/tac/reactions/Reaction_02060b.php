<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02060b extends RiskReaction
{
    private int $MyParticipantId = 0;
    private int $OpponentId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Both When Duel Ends");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('The duel is ending. ${you} may wound your participant and the opposing adversary: ');
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

        if ($event instanceof EventDuelEnd && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                if ($event->challengingPlayerId == $owner->ControllerId)
                {
                    $this->MyParticipantId = $event->challengerId;
                    $this->OpponentId = $event->defenderId;
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
                else if ($event->defendingPlayerId == $owner->ControllerId)
                {
                    $this->MyParticipantId = $event->defenderId;
                    $this->OpponentId = $event->challengerId;
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
            $myParticipant = $game->theah->getCharacterById($this->MyParticipantId);
            $opponent = $game->theah->getCharacterById($this->OpponentId);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($this->MyParticipantId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($this->OpponentId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction. Wounding ${participant_inject_code} and ${opponent_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "participant_inject_code" => $myParticipant->getInjectCode(),
                "opponent_inject_code" => $opponent->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }

        if ($event instanceof EventPlayerTurnEnd && ($this->MyParticipantId != 0 || $this->OpponentId != 0))
        {
            $owner = $this->getOwningCard($event->theah);
            $this->MyParticipantId = 0;
            $this->OpponentId = 0;
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
