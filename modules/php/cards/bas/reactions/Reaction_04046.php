<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04046 extends RiskReaction
{
    // WHY: Public so participant id survives pay / serialize round-trips (Pattern D.2).
    public int $participantId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Add a threat to your Duelist participant");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may play this Risk to add a threat to your Duelist participant: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Bravado'), 'use');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelEndOfRound && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }
            if (! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            if (! $event->theah->game->globals->get(Game::IN_DUEL, false))
            {
                return;
            }

            // WHY: "your adversary's round" — actor is the opponent, not you.
            if ($event->playerId == $owner->ControllerId)
            {
                return;
            }

            $participantId = $event->theah->getDuelOpponentId($event->actorId);
            $participant = $event->theah->getCharacterById($participantId);
            if ($participant === null || $participant->ControllerId != $owner->ControllerId)
            {
                return;
            }

            // Duelist Reaction + "your Duelist participant" — same trait gate.
            if (! $participant->hasTrait('Duelist'))
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($participant))
            {
                return;
            }

            $this->participantId = $participant->Id;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $participant = $event->theah->getCharacterById($this->participantId);
            if ($owner === null || $participant === null)
            {
                $this->participantId = 0;
                return;
            }

            $challengerId = $event->theah->getDuelChallengerId();
            if ($challengerId === null)
            {
                $this->participantId = 0;
                return;
            }

            // WHY: End-of-round threat must use PENDING_* globals, not createThreatModifiedEvent.
            // Resolve Threat already ran; mutating ending_* would rewrite the finished round UI
            // and mis-attribute wounds. PENDING keeps the duel alive (stDuelNextPlayer) and
            // lands as starting threat on the next round (stDuelNewRound) — same as Maneuver_02039.
            if ($participant->Id == $challengerId)
            {
                $pending = $game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0);
                $game->globals->set(Game::PENDING_CHALLENGER_THREAT, $pending + 1);
            }
            else
            {
                $pending = $game->globals->get(Game::PENDING_DEFENDER_THREAT, 0);
                $game->globals->set(Game::PENDING_DEFENDER_THREAT, $pending + 1);
            }

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction to add a threat to ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $participant->getInjectCode(),
            ]);

            $this->setUsed($event->theah, true);
            $this->participantId = 0;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'use')
        {
            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($payEvent);

            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($payTransition);
        }
        else
        {
            $this->participantId = 0;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
