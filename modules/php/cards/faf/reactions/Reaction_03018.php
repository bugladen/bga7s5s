<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03018 extends CardReaction
{
    private int $targetId = 0;
    private string $targetName = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound the refusing character when your Zealot or Hunter's challenge is refused");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $name = $this->targetName !== '' ? $this->targetName : $theah->game->translate('the refusing character');
        return $base . sprintf($theah->game->translate('${you} may wound %s: '), $name);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound'), 'wound');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeRejected && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null)
            {
                return;
            }

            $challenger = $event->theah->getCharacterById($event->challengerId);
            if ($challenger == null)
            {
                return;
            }

            if ($challenger->ControllerId != $owner->ControllerId)
            {
                return;
            }

            if (! $challenger->hasTrait("Zealot") && ! $challenger->hasTrait("Hunter"))
            {
                return;
            }

            $target = $event->theah->getCharacterById($event->targetId);
            if ($target == null)
            {
                return;
            }

            $this->targetId = $target->Id;
            $this->targetName = $target->Name;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId == 'wound' && $this->targetId > 0)
        {
            $target = $game->theah->getCharacterById($this->targetId);
            if ($target != null)
            {
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction to wound ${target_inject_code}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "target_inject_code" => $target->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $this->setUsed($game->theah, true);
            }
        }

        $this->targetId = 0;
        $this->targetName = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
