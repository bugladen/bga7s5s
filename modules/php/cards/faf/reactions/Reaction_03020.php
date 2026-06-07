<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03020 extends RiskReaction implements ICancelReaction
{
    private int $batchId = 0;
    private string $fromLocation = '';
    private string $toLocation = '';
    private int $amount = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Renown Movement From Leader's Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf(
            $theah->game->translate('${you} may cancel the movement of %d Renown from %s to %s: '),
            $this->amount,
            $theah->game->translate($this->fromLocation),
            $theah->game->translate($this->toLocation)
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Movement'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRenownMovingBetweenLocations && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null) return;
            if (! ($owner->Location == Game::LOCATION_HAND)) return;

            $leader = $event->theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader === null) return;
            if ($leader->Location != $event->fromLocation) return;

            $this->batchId = $event->batchId ?? 0;
            $this->fromLocation = $event->fromLocation;
            $this->toLocation = $event->toLocation;
            $this->amount = $event->amount;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null) return;

            // Delete the queued EventRenownAddedToLocation and EventRenownRemovedFromLocation events
            // sharing this batch. WHY: EventRenownMovingBetweenLocations is informational only — the
            // actual Renown changes happen via the add/remove events queued alongside it. We defer
            // the deletion to here (post-pay) so that a framework cancel-reaction firing during the
            // Risk's pay step would leave the Renown movement intact rather than dropping it without
            // the Risk actually being spent.
            if ($this->batchId > 0)
            {
                $event->theah->deleteRenownRemovedFromLocationEventsByBatchId($this->batchId);
                $event->theah->deleteRenownAddedToLocationEventsByBatchId($this->batchId);
            }

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} cancelled the movement of ${amount} Renown from ${from_location}.'), [
                "i18n" => ["from_location"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "amount" => $this->amount,
                "from_location" => $this->fromLocation,
            ]);

            $this->setUsed($event->theah, true);
            $this->resetSavedState();
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $this->resetSavedState();
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'cancel')
        {
            // Stack (not queue) so the pay flow runs ahead of the EventRenownRemovedFromLocation /
            // EventRenownAddedToLocation events still pending from the original Moving batch. We
            // need to be paid for first; the EventRiskReactionTriggered handler then deletes those
            // pending Renown events before they can fire.
            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->stackEvent($payTransition);

            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->stackEvent($payEvent);
        }
        else
        {
            $this->resetSavedState();
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }

    private function resetSavedState(): void
    {
        $this->batchId = 0;
        $this->fromLocation = '';
        $this->toLocation = '';
        $this->amount = 0;
    }
}
