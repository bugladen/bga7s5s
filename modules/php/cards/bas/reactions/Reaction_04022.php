<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04022 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Your participant gains a threat after the adversary announces a combat card");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $owner = $this->getOwningCard($theah);
        $alsoAdversary = $owner !== null && ! $owner->Engaged;

        if ($alsoAdversary)
        {
            return parent::getReactionDescription($theah) . $theah->game->translate('${you} may add a threat to your participant and the adversary (Axelle is en garde): ');
        }

        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may add a threat to your participant: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $alsoAdversary = $owner !== null && ! $owner->Engaged;

        $label = $alsoAdversary
            ? $theah->game->translate('Add Threat (both)')
            : $theah->game->translate('Add Threat');
        $array[] = $this->createButtonProperty($theah->game, $label, 'addThreat');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * Map "your participant" / optional adversary onto createThreatModifiedEvent(challenger, defender).
     *
     * @return array{0: int, 1: int}|null  [challengerThreat, defenderThreat] or null if no participant
     */
    private function threatDeltas(Theah $theah, int $yourPlayerId, bool $alsoAdversary): ?array
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

        $challengerThreat = 0;
        $defenderThreat = 0;

        if ($challenger->ControllerId == $yourPlayerId)
        {
            $challengerThreat = 1;
            if ($alsoAdversary)
            {
                $defenderThreat = 1;
            }
        }
        else if ($defender->ControllerId == $yourPlayerId)
        {
            $defenderThreat = 1;
            if ($alsoAdversary)
            {
                $challengerThreat = 1;
            }
        }
        else
        {
            return null;
        }

        return [$challengerThreat, $defenderThreat];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCombatCardAnnounced && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || $owner->ControllerId == 0)
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($owner))
            {
                return;
            }

            if (! $event->theah->game->globals->get(Game::IN_DUEL, false))
            {
                return;
            }

            // Opposing adversary announces — not your own combat card.
            if ($event->playerId == $owner->ControllerId)
            {
                return;
            }

            $deltas = $this->threatDeltas($event->theah, $owner->ControllerId, false);
            if ($deltas === null)
            {
                return;
            }

            $owner->IsUpdated = true;
            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'addThreat')
        {
            $owner = $this->getOwningCard($game->theah);
            if ($owner === null || ! $this->isAvailable())
            {
                $game->gamestate->nextState("done");
                return;
            }

            // WHY: En Garde is a rider on the effect ("If Axelle is en garde…"), not a cost.
            $alsoAdversary = ! $owner->Engaged;
            $deltas = $this->threatDeltas($game->theah, $owner->ControllerId, $alsoAdversary);
            if ($deltas === null)
            {
                $game->gamestate->nextState("done");
                return;
            }

            [$challengerThreat, $defenderThreat] = $deltas;

            if ($alsoAdversary)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction — your participant and the adversary each gain a threat (Axelle is en garde).'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction — your participant gains a threat.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);
            }

            $threatEvent = EventFactory::createThreatModifiedEvent($challengerThreat, $defenderThreat);
            $game->theah->queueEvent($threatEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
