<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03cd20 extends CardReaction
{
    // '' (idle), 'choose-character', 'choose-location'
    private string $stage = '';
    private int $chosenCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move a character to an adjacent City location and discard");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        switch ($this->stage)
        {
            case 'choose-character':
                return $base . $theah->game->translate('${you} must choose one of your characters to move:');
            case 'choose-location':
                return $base . $theah->game->translate('${you} must choose an adjacent City location to move them to:');
        }
        return $base;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $game = $theah->game;
        $owner = $this->getOwningCard($theah);

        switch ($this->stage)
        {
            case 'choose-character':
                foreach ($this->getEligibleCharacters($theah, $owner) as $character)
                {
                    $array[] = $this->createButtonProperty($game, $character->Name, "char-{$character->Id}");
                }
                break;

            case 'choose-location':
                $array[] = $this->createButtonProperty($game, $game->translate('< Back'), 'back');
                foreach ($theah->getAdjacentCityLocations($owner->Location, false) as $location)
                {
                    $array[] = $this->createButtonProperty($game, $location, "loc-{$location}");
                }
                break;
        }

        $array[] = $this->createButtonProperty($game, $game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPhasePlanningEnd && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null) return;
            if ($owner->Location !== Game::LOCATION_PLAYER_HOME) return;
            if (count($this->getEligibleCharacters($event->theah, $owner)) === 0) return;
            if (count($event->theah->getAdjacentCityLocations($owner->Location, false)) === 0) return;

            $this->stage = 'choose-character';
            $this->chosenCharacterId = 0;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId === 'decline')
        {
            $this->resetStage();
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'back')
        {
            if ($this->stage === 'choose-location')
            {
                $this->stage = 'choose-character';
                $this->chosenCharacterId = 0;
            }
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'char-'))
        {
            $this->chosenCharacterId = (int)substr($reactionId, strlen('char-'));
            $this->stage = 'choose-location';
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'loc-'))
        {
            $location = substr($reactionId, strlen('loc-'));
            $this->resolveMoveAndDiscard($game, $owner, $location);
            $this->resetStage();
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function requeue(Game $game, $owner): void
    {
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resetStage(): void
    {
        $this->stage = '';
        $this->chosenCharacterId = 0;
    }

    /**
     * @return Character[]
     */
    private function getEligibleCharacters(Theah $theah, $owner): array
    {
        return $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
    }

    private function resolveMoveAndDiscard(Game $game, $owner, string $location): void
    {
        $adjacent = $game->theah->getAdjacentCityLocations($owner->Location, false);
        if (!in_array($location, $adjacent, true)) return;

        $character = $game->theah->getCardById($this->chosenCharacterId);
        if (!($character instanceof Character) || $character->ControllerId !== $owner->ControllerId) return;

        $game->notify->all("message",
            clienttranslate('${card_inject_code}: ${player_name} moves ${character_inject_code} to ${location} and discards this card.'),
            [
                'i18n' => ['location'],
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
                "location" => $location,
            ]
        );

        $moveEvent = EventFactory::createCardMovingEvent(
            $character->ControllerId,
            $character->Id,
            $character->Location,
            $location,
            false,
            $owner->Id,
            $this->Id
        );
        $game->theah->queueEvent($moveEvent);

        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent(
            $owner->ControllerId,
            $owner->Id,
            $owner->Location,
            $owner->Id,
            true
        );
        $game->theah->queueEvent($discardEvent);
    }
}
