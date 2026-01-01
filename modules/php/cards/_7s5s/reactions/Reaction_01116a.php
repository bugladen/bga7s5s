<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01116a extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "En Garde Yevgeni after a Challenge Refusal";
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to En Garde Yevgeni: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('En Garde'), 'enGarde');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        $yevgeni = $this->getOwningCharacter($event->theah);
        if ($event instanceof EventChallengeRejected && $this->IsAvailable() && $event->challengerId == $yevgeni->Id)
        {
            $transition = EventFactory::createReactionTransitionEvent($yevgeni->ControllerId, $yevgeni->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'enGarde')
        {
            $yevgeni = $this->getOwningCharacter($game->theah);
            $event = EventFactory::createCardEngardedEvent($yevgeni->ControllerId, $yevgeni->Id, $yevgeni->Id, $this->Id);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}