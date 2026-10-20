<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05BoyBand extends RiskReaction
{
    // WHY public: the offer, the swap-partner pick and the post-pay effect run in separate
    // requests, so these must survive serialize/DB round-trips (same as Reaction_05Teapot).
    public int $participantId = 0;
    public int $chosenSwapId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no translate (translation is done later).
        $this->Name = "Swap Participant and Add Threat";
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . '${you} may swap your participant with your Thug or Duelist at this location, then add a threat to both participants: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $participant = $theah->getCharacterById($this->participantId);
        if ($participant !== null)
        {
            foreach ($this->getEligibleSwapCharacters($theah, $participant) as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, 'Swap ' . $participant->Name . ' with ' . $character->Name, "character-{$character->Id}");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    /**
     * "Your Thug or Duelist at this location" — other characters the owner controls at the
     * participant's location. Home is never a duel location, so no Home special-casing.
     *
     * @return list<Character>
     */
    private function getEligibleSwapCharacters(Theah $theah, Character $participant): array
    {
        $characters = $theah->getCharactersAtLocationByPlayerId($participant->Location, $participant->ControllerId);

        return array_values(array_filter(
            $characters,
            fn(Character $character) => $character->Id != $participant->Id
                && ($character->hasTrait('Thug') || $character->hasTrait('Duelist'))
        ));
    }

    /**
     * Your duel participant: the challenger/defender controlled by the Risk's owner.
     * The adversary is the other side, so the controller id is unambiguous.
     */
    private function getOwnParticipant(Theah $theah, Card $owner): ?Character
    {
        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();

        foreach ([$challengerId, $defenderId] as $id)
        {
            if ($id === null || $id == 0)
            {
                continue;
            }
            $character = $theah->getCharacterById($id);
            if ($character !== null && $character->ControllerId == $owner->ControllerId)
            {
                return $character;
            }
        }

        return null;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCombatCardAnnounced && $this->isAvailable())
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

            // "the adversary announces" — not your own combat card.
            if ($event->playerId == $owner->ControllerId)
            {
                return;
            }

            $participant = $this->getOwnParticipant($event->theah, $owner);
            if ($participant === null)
            {
                return;
            }

            // Hide when no swap partner exists (dead play) or the participant cannot be
            // swapped out (Harpoon) — same gate swapParticipantsInDuel enforces.
            if ($participant->hasCondition(Game::HARPOON_CONDITION))
            {
                return;
            }
            if (count($this->getEligibleSwapCharacters($event->theah, $participant)) === 0)
            {
                return;
            }

            $this->participantId = $participant->Id;
            $this->chosenSwapId = 0;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY apply the effect here, not in performReaction: a cancel reaction can fire between
        // pay and this event. Deferring keeps the swap paired with a resolved Risk play.
        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            $participant = $event->theah->getCharacterById($this->participantId);
            $partner = $event->theah->getCharacterById($this->chosenSwapId);

            if ($participant === null || $partner === null || ! $this->isSwapStillValid($event->theah, $participant, $partner))
            {
                $game->notify->all('message', '${reaction_inject_code}: the swap is no longer valid.', [
                    'reaction_inject_code' => $owner->getInjectCode(),
                ]);
                $this->setUsed($event->theah, true);
                $this->resetState($owner);
                return;
            }

            $game->notify->all('message', '${reaction_inject_code}: ${player_name} used Reaction to swap ${participant_inject_code} with ${partner_inject_code} and add a threat to both participants.', [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'participant_inject_code' => $participant->getInjectCode(),
                'partner_inject_code' => $partner->getInjectCode(),
            ]);

            $duelId = $game->globals->get(Game::DUEL_ID);
            $round = $game->globals->get(Game::DUEL_ROUND);
            $event->theah->swapParticipantsInDuel($duelId, $round, $participant->Id, $partner->Id);

            // WHY ThreatModified (not PENDING threat): this is mid-round, and threat is tracked
            // per side (challenger/defender), so it applies to whoever now holds each side.
            // "Then" — queued after the swap so the swap's own events resolve first.
            $event->theah->queueEvent(EventFactory::createThreatModifiedEvent(1, 1));

            $this->setUsed($event->theah, true);
            $this->resetState($owner);
        }
    }

    private function isSwapStillValid(Theah $theah, Character $participant, Character $partner): bool
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return false;
        }

        $current = $this->getOwnParticipant($theah, $owner);
        if ($current === null || $current->Id != $participant->Id)
        {
            return false;
        }

        foreach ($this->getEligibleSwapCharacters($theah, $participant) as $character)
        {
            if ($character->Id == $partner->Id)
            {
                return true;
            }
        }

        return false;
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState('done');
            return;
        }

        if ($reactionId === 'pass')
        {
            // WHY no setUsed: the Risk stays in hand for a later combat card announcement.
            $this->resetState($owner);
            $game->gamestate->nextState('done');
            return;
        }

        if (! str_starts_with($reactionId, 'character-'))
        {
            throw new UserException('Invalid choice.');
        }

        $partner = $game->theah->getCharacterById((int) substr($reactionId, strlen('character-')));
        if ($partner === null)
        {
            throw new UserException('Character not found.');
        }

        $participant = $game->theah->getCharacterById($this->participantId);
        if ($participant === null)
        {
            throw new UserException('Your participant is no longer in the duel.');
        }

        // WHY fail before pay: a Harpooned participant cannot be swapped out; the player must
        // not pay for the Risk and then have the swap throw mid-event.
        if ($participant->hasCondition(Game::HARPOON_CONDITION))
        {
            throw new UserException(sprintf('%s is Harpooned and cannot be swapped for the remainder of the duel.', $participant->Name));
        }

        if (! $this->isSwapStillValid($game->theah, $participant, $partner))
        {
            throw new UserException('You may only swap with another Thug or Duelist you control at this location.');
        }

        $this->chosenSwapId = $partner->Id;
        $owner->IsUpdated = true;

        $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
        $game->theah->queueEvent($payEvent);

        $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($payTransition);

        $game->gamestate->nextState('done');
    }

    private function resetState(?Card $owner): void
    {
        $this->participantId = 0;
        $this->chosenSwapId = 0;

        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }
}
