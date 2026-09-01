<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03001 extends CardReaction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound an Opposing Character after Cesca's Sorcerer Ability");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose an opposing character to wound: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $cesca = $this->getOwningCharacter($theah);
        $targets = $this->getOpposingCharactersAtLocation($theah, $cesca);

        foreach ($targets as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "wound-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $cesca = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $cesca->ControllerId)
        {
            return [false, $game->translate("You cannot wound a character that is controlled by you.")];
        }

        if ($character->Location != $cesca->Location)
        {
            return [false, $game->translate("Character is not at Cesca's location.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSorcererAbilityPlayed && $this->isAvailable())
        {
            $cesca = $this->getOwningCharacter($event->theah);

            if (! $event->theah->cardInCity($cesca))
            {
                return;
            }

            if ($event->sourceId != $cesca->Id && $event->performerId != $cesca->Id)
            {
                return;
            }

            $targets = $this->getOpposingReactionTargets($event->theah, $cesca, $event);
            if (count($targets) == 0)
            {
                return;
            }

            $transition = EventFactory::createReactionTransitionEvent($cesca->ControllerId, $cesca->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $cesca = $this->getOwningCharacter($game->theah);
            $targetId = (int)str_replace("wound-", "", $reactionId);
            $target = $game->theah->getCharacterById($targetId);

            if ($target == null)
            {
                throw new UserException($game->translate("Invalid character to wound."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $cesca->Id, 1, $cesca->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used City Reaction to wound ${character_inject_code}.'), [
                "reaction_inject_code" => $cesca->getInjectCode(),
                "player_name" => $game->getPlayerNameById($cesca->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }

    private function getOpposingCharactersAtLocation(Theah $theah, Character $cesca): array
    {
        $characters = $theah->getCharactersAtLocation($cesca->Location);
        return array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($cesca->ControllerId)));
    }

    private function getOpposingReactionTargets(Theah $theah, Character $cesca, EventSorcererAbilityPlayed $event): array
    {
        $targets = $this->getOpposingCharactersAtLocation($theah, $cesca);

        // WHY: EventCardMoving queues EventCardMoved after EventSorcererAbilityPlayed in the
        // same batch (e.g. Pull / Action_01172). The target-count guard runs too early unless
        // we also count an opposing ability target that is still queued to move to Cesca.
        if ($event->performerId != $cesca->Id || $event->targetId == 0)
        {
            return $targets;
        }

        $target = $theah->getCharacterById($event->targetId);
        if ($target == null || ! $target->isNotControlledByPlayer($cesca->ControllerId))
        {
            return $targets;
        }

        foreach ($targets as $character)
        {
            if ($character->Id == $target->Id)
            {
                return $targets;
            }
        }

        if ($theah->hasQueuedCardMoveToLocation($target->Id, $cesca->Location))
        {
            $targets[] = $target;
        }

        return $targets;
    }
}
