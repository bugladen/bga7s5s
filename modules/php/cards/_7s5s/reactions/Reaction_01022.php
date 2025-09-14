<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01022 extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Challenger or Challenged");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Wound Challenger or Challenged: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Challenger'), 'woundChallenger');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Challenged'), 'woundChallenged');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && ! $this->Used && $this->ownerIsAttached($event->theah))
        {
            $owningCharacter = $this->getOwningCharacter($event->theah);
            $challenger = $event->theah->getCharacterById($event->challengerId);
            if ($owningCharacter->Location == $challenger->Location)
            {
                $owner = $this->getOwningAttachment($event->theah);
                $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundChallenger')
        {
            $challengerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $challenger = $game->theah->getCharacterById($challengerId);

            $owner = $this->getOwningAttachment($game->theah);

            $event = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCharacterWoundedEvent($challenger->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to wound ${challenger_inject_code}'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "challenger_inject_code" => $challenger->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }
        else if ($reactionId == 'woundChallenged')
        {
            $defenderId = $game->globals->get(Game::CHOSEN_TARGET);
            $defender = $game->theah->getCharacterById($defenderId);

            $owner = $this->getOwningAttachment($game->theah);

            $event = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCharacterWoundedEvent($defender->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to wound ${defender_inject_code}'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "defender_inject_code" => $defender->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}