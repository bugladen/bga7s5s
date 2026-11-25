<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01088 extends RiskReaction implements ICancelReaction
{
    // WHY: actPayForReaction reads Game::REACTION_ID. A leftover sibling You're Embarrassing
    // Yourself prompt (starter decks run two) can Pass and overwrite that global to "pass"
    // before this copy's pay fires EventRiskReactionTriggered — which would then skip
    // challenge cancel. Same discipline as Hexenjagd (Reaction_01053).
    private string $chosenCancelReactionId = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Mercenary Challenge");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Cancel Mercenary Challenge: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Challenge'), 'cancelChallenge');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && ! $event->canceled)
        {
            $risk = $this->getOwningCard($event->theah);
            if ($risk->Location == Game::LOCATION_HAND)
            {
                $challenger = $event->theah->getCharacterById($event->challengerId);

                // WHY: Use challengerId from the event (not CHOSEN_PERFORMER) — same
                // identity as the issued challenge even if globals are mid-rewrite.
                if ($challenger !== null
                    && $challenger->hasTrait("Mercenary")
                    && $challenger->isNotControlledByPlayer($risk->ControllerId))
                {
                    $transition = EventFactory::createReactionTransitionEvent($risk->ControllerId, $risk->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $reactionId = $event->reactionId;
            if ($this->chosenCancelReactionId !== ''
                && ($reactionId === 'pass' || $reactionId === 'decline'))
            {
                $reactionId = $this->chosenCancelReactionId;
            }

            if ($reactionId == 'cancelChallenge')
            {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($game->theah);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and cancelled the Mercenary Challenge.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);
    
                $game->globals->set(Game::CHALLENGE_CANCELLED, true);
                $this->chosenCancelReactionId = '';
                // Belt-and-suspenders: performReaction already cleared siblings when cancel
                // was chosen; clear again in case a late offer stacked during pay.
                $this->clearSiblingEmbarrassingReactionTransitions($game, $owner->Id, $owner->ControllerId);
                $this->setUsed($game->theah, true);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancelChallenge')
        {
            $owner = $this->getOwningCard($game->theah);
            $this->chosenCancelReactionId = $reactionId;
            $owner->IsUpdated = true;

            // WHY: Starter decks run two. ChallengeIssued stacks a reaction transition per
            // copy; without clearing siblings here, the second copy prompts while this copy
            // is paying — Pass overwrites REACTION_ID so Triggered skips cancel cleanup
            // (Hexenjagd Reaction_01053 / Mireli Reaction_01135).
            $this->clearSiblingEmbarrassingReactionTransitions($game, $owner->Id, $owner->ControllerId);

            // WHY: stack (EnteringPay on top) — same order as Stubborn / Mireli / Night
            // of Drinking. queue left pay behind other SETUP events and delayed cancel.
            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->stackEvent($event);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->stackEvent($event);
        }

        $game->gamestate->nextState('done');
    }

    private function clearSiblingEmbarrassingReactionTransitions(Game $game, int $ownerCardId, int $controllerId): void
    {
        $handCards = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $controllerId);
        foreach ($handCards as $handCard)
        {
            if ($handCard->Id == $ownerCardId || ! ($handCard instanceof IHasReactions))
            {
                continue;
            }

            foreach ($handCard->getReactions() as $otherReaction)
            {
                if ($otherReaction instanceof Reaction_01088)
                {
                    $game->theah->deleteTransitionEvents($otherReaction->Id);
                    $game->theah->deleteTransitionEventsBySourceId($handCard->Id);
                }
            }
        }
    }
}
