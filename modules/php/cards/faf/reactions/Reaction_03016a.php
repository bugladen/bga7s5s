<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03016a extends CardReaction
{
    private ?EventCardMoving $cardMovingEvent = null;
    private string $fromLocation = '';

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Keep Ise in the city during Dusk");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        return $base . sprintf($theah->game->translate('${you} may choose not to move Ise Home from %s: '), $theah->game->translate($this->fromLocation));
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Keep in city'), 'stay');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoving)) return;
        if ($event->canceled || $event->unstoppable) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;

        if ($event->cardId != $owner->Id) return;
        if ($event->toLocation != Game::LOCATION_PLAYER_HOME) return;
        // The Dusk auto-move home is emitted with sourceId=0 from stDuskPhaseCleanup.
        // Any ability-driven move-home carries a non-zero sourceId; don't trigger on those.
        if ($event->sourceId != 0) return;
        if (in_array($owner->Id, $event->cancelDeclinedByCardIds)) return;

        $turnPhase = (int) $event->theah->game->getGameStateValue(Game::TURN_PHASE);
        if ($turnPhase != Game::DUSK) return;

        $this->cardMovingEvent = clone $event;
        unset($this->cardMovingEvent->theah);
        $this->fromLocation = $event->fromLocation;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'stay')
        {
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} chooses to keep ${character_inject_code} at ${location_name} during Dusk.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $owner->getInjectCode(),
                "location_name" => $this->fromLocation,
            ]);

            $this->setUsed($game->theah, true);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        if ($reactionId == 'decline')
        {
            if ($this->cardMovingEvent !== null)
            {
                // Re-queue the move with a self-marker so we don't re-trigger on this same event.
                $this->cardMovingEvent->cancelDeclinedByCardIds[] = $owner->Id;
                $game->theah->queueEvent($this->cardMovingEvent);
            }
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
