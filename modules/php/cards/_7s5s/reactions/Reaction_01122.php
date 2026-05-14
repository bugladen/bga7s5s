<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01122 extends CardReaction implements ICancelReaction
{
    private int $SourceId = 0;
    private ?int $BatchId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel a Sorcery or Sorcerer Ability Targeting Torsten Vakt");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel a Sorcery or Sorcerer Ability Targeting Torsten Vakt: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Sorcery or Sorcerer Ability'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSorcererAbilityStart && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Id == $event->targetId)
            {
                $this->SourceId = $event->sourceId;
                $this->BatchId = $event->batchId;
                $owner->IsUpdated = true;
                $reactionTransition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->stackEvent($reactionTransition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancel')
        {
            $owner = $this->getOwningCard($game->theah);
            $game->theah->deleteEventsTargetingCard($owner->Id);
            $game->theah->deleteTransitionEventsBySourceId($this->SourceId);

            if ($this->BatchId)
            {
                $game->theah->deleteEventBatch($this->BatchId);
            }

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to cancel the Sorcery or Sorcerer Ability targeting him.'), [
                "player_name" => $game->getActivePlayerName(),
                "reaction_inject_code" => $owner->getInjectCode(),
            ]);

            $this->SourceId = 0;
            $this->BatchId = null;

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}