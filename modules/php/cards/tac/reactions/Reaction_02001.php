<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02001 extends CardReaction implements ISorcererAbility, IAbilityThatTargetsCharacters, IAbilityThatTargetsCards
{
    public int $CharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Non-Sorcerer Intervening or Refusing Challenge");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Wound Non-Sorcerer Intervening or Refusing Challenge: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Non-Sorcerer'), 'woundNonSorcerer');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterIntervened && $this->isAvailable())
        {
            $andriana = $this->getOwningCharacter($event->theah);
            if ($andriana->ControllerId != $event->playerId)
            {
                $character = $event->theah->getCharacterById($event->newTargetId);
                if (! $character->hasTrait("Sorcerer"))
                {
                    $this->CharacterId = $character->Id;
                    $andriana->IsUpdated = true;

                    $reactionEvent = EventFactory::createReactionTransitionEvent($andriana->ControllerId, $andriana->Id, $this->Id);
                    $event->theah->queueEvent($reactionEvent);
                }

            }
        }

        if ($event instanceof EventChallengeRejected && $this->isAvailable())
        {
            $andriana = $this->getOwningCharacter($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            if ($andriana->ControllerId != $character->ControllerId && ! $character->hasTrait("Sorcerer"))
            {
                $this->CharacterId = $character->Id;
                $andriana->IsUpdated = true;

                $reactionEvent = EventFactory::createReactionTransitionEvent($andriana->ControllerId, $andriana->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundNonSorcerer')
        {
            $andriana = $this->getOwningCharacter($game->theah);
            $character = $game->theah->getCharacterById($this->CharacterId);
            $event = EventFactory::createCharacterBeingWoundedEvent($character->Id, $andriana->Id, 1, $andriana->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${andriana_inject_code}: ${player_name} used Reaction to wound ${character_inject_code}'), [
                "andriana_inject_code" => $andriana->getInjectCode(),
                "player_name" => $game->getActivePlayerName(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
            $this->CharacterId = 0;
            $andriana->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}