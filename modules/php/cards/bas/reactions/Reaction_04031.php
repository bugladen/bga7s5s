<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04031 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Remove one threat from your participant");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may remove one threat from your participant (Andare is en garde): ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Remove Threat'), 'removeThreat');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * @return array{0: int, 1: int}|null  [challengerThreat, defenderThreat] deltas, or null if no participant / no threat
     */
    private function removeThreatDelta(Theah $theah, int $yourPlayerId): ?array
    {
        $challengerId = $theah->getDuelChallengerId();
        if ($challengerId === null)
        {
            return null;
        }

        $challenger = $theah->getCharacterById($challengerId);
        $defender = $theah->getCharacterById($theah->getDuelDefenderId());
        if ($challenger === null || $defender === null)
        {
            return null;
        }

        $challengerThreat = $theah->game->globals->get(Game::CHALLENGER_THREAT);
        $defenderThreat = $theah->game->globals->get(Game::DEFENDER_THREAT);

        if ($challenger->ControllerId == $yourPlayerId && $challengerThreat > 0)
        {
            return [-1, 0];
        }

        if ($defender->ControllerId == $yourPlayerId && $defenderThreat > 0)
        {
            return [0, -1];
        }

        return null;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventDuelNewRound) || $event->round != 1 || ! $this->isAvailable())
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $owner->isControlled())
        {
            return;
        }

        // En Garde Reaction — precondition, not an Engage cost.
        if ($owner->Engaged)
        {
            return;
        }

        $challenger = $event->theah->getCharacterById($event->challengerId);
        $defender = $event->theah->getCharacterById($event->defenderId);
        if ($challenger === null || $defender === null)
        {
            return;
        }

        if ($owner->Location != $challenger->Location && $owner->Location != $defender->Location)
        {
            return;
        }

        if ($this->removeThreatDelta($event->theah, $owner->ControllerId) === null)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'removeThreat')
        {
            $owner = $this->getOwningCharacter($game->theah);
            if ($owner === null || ! $this->isAvailable() || $owner->Engaged)
            {
                $game->gamestate->nextState("done");
                return;
            }

            $deltas = $this->removeThreatDelta($game->theah, $owner->ControllerId);
            if ($deltas === null)
            {
                $game->gamestate->nextState("done");
                return;
            }

            [$challengerThreat, $defenderThreat] = $deltas;

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction — remove one threat from your participant.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $threatEvent = EventFactory::createThreatModifiedEvent($challengerThreat, $defenderThreat);
            $game->theah->queueEvent($threatEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
