<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03010 extends RiskReaction
{
    // '' (idle / offer to owner), 'choice' (opposing controller picks return-vs-wound),
    // 'pickMuster' (opposing controller picks which character to muster instead)
    private string $stage = '';
    private int $targetCharacterId = 0;
    private int $opposingPlayerId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Mustered Character");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $target = $this->targetCharacterId > 0 ? $theah->getCharacterById($this->targetCharacterId) : null;
        $targetName = $target ? $theah->game->translate($target->Name) : $theah->game->translate('the mustered character');

        switch ($this->stage)
        {
            case 'choice':
                return $base . sprintf($theah->game->translate('${you} may return %s to your Approach deck and muster a different character, or take the wound: '), $targetName);
            case 'pickMuster':
                return $base . $theah->game->translate('${you} must choose a different character from your Approach deck to muster: ');
        }
        return $base . sprintf($theah->game->translate('${you} may play this Risk to wound %s after their muster: '), $targetName);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        switch ($this->stage)
        {
            case 'choice':
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Return and Muster Different'), 'return');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Take the Wound'), 'accept');
                break;

            case 'pickMuster':
                foreach ($this->getApproachCandidates($theah) as $candidate)
                {
                    $array[] = $this->createButtonProperty($theah->game, $candidate->Name, "muster-{$candidate->Id}");
                }
                break;

            default:
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Manipulative'), 'use');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
                break;
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($this->isAvailable() &&
            (($event instanceof EventApproachCharacterPlayed) ||
             ($event instanceof EventCharacterMustered && $event->fromLocation == Game::LOCATION_APPROACH)))
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }
            if (! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            if ($event->playerId == $owner->ControllerId)
            {
                return;
            }

            $target = $event->theah->getCharacterById($event->characterId);
            if ($target === null)
            {
                return;
            }

            $performer = $this->findStregaCharacter($event->theah, $owner->ControllerId);
            if ($performer === null)
            {
                return;
            }

            $this->stage = '';
            $this->targetCharacterId = $target->Id;
            $this->opposingPlayerId = $target->ControllerId;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $this->initiateChoice($event->theah);
        }
    }

    private function findStregaCharacter(Theah $theah, int $controllerId): ?Character
    {
        foreach ($theah->getCharactersInPlayByPlayerId($controllerId) as $character)
        {
            if ($character->hasTrait('Strega'))
            {
                return $character;
            }
        }
        return null;
    }

    private function countStrega(Theah $theah, int $controllerId): int
    {
        $count = 0;
        foreach ($theah->getCharactersInPlayByPlayerId($controllerId) as $character)
        {
            if ($character->hasTrait('Strega'))
            {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return list<Character>
     */
    private function getApproachCandidates(Theah $theah): array
    {
        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_APPROACH, $this->opposingPlayerId);
        $candidates = [];
        foreach ($cards as $card)
        {
            if ($card instanceof Character && $card->Id != $this->targetCharacterId)
            {
                $candidates[] = $card;
            }
        }
        return $candidates;
    }

    private function initiateChoice(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null || $this->targetCharacterId == 0 || $this->opposingPlayerId == 0)
        {
            return;
        }

        $game = $theah->game;
        $target = $theah->getCharacterById($this->targetCharacterId);
        if ($target === null)
        {
            return;
        }

        $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} targets ${target_inject_code}.'), [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
            'target_inject_code' => $target->getInjectCode(),
        ]);

        $candidates = $this->getApproachCandidates($theah);
        if (count($candidates) == 0)
        {
            $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${opponent_name} has no other character in their Approach deck and must take the wound.'), [
                'reaction_inject_code' => $owner->getInjectCode(),
                'opponent_name' => $game->getPlayerNameById($this->opposingPlayerId),
            ]);
            $this->applyWound($theah);
            $this->finalize($theah);
            return;
        }

        $this->stage = 'choice';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($this->opposingPlayerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function applyWound(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return;
        }

        $target = $theah->getCharacterById($this->targetCharacterId);
        if ($target === null)
        {
            return;
        }

        $wounds = $this->countStrega($theah, $owner->ControllerId) >= 3 ? 2 : 1;

        $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, $wounds, $owner->getInjectCode(), $this->Id);
        $theah->eventCheck($woundEvent);
        $theah->queueEvent($woundEvent);
    }

    private function finalize(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        $this->setUsed($theah, true);
        $this->stage = '';
        $this->targetCharacterId = 0;
        $this->opposingPlayerId = 0;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function resetWithoutUse(Card $owner): void
    {
        $this->stage = '';
        $this->targetCharacterId = 0;
        $this->opposingPlayerId = 0;
        $owner->IsUpdated = true;
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === '')
        {
            if ($reactionId === 'use')
            {
                $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
                $game->theah->queueEvent($payEvent);

                $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $game->theah->queueEvent($payTransition);
            }
            else
            {
                $this->resetWithoutUse($owner);
            }

            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === 'choice')
        {
            if ($reactionId === 'return')
            {
                $candidates = $this->getApproachCandidates($game->theah);
                if (count($candidates) == 0)
                {
                    $this->applyWound($game->theah);
                    $this->finalize($game->theah);
                    $game->gamestate->nextState('done');
                    return;
                }

                $this->stage = 'pickMuster';
                $owner->IsUpdated = true;

                $transition = EventFactory::createReactionTransitionEvent($this->opposingPlayerId, $owner->Id, $this->Id);
                $game->theah->queueEvent($transition);

                $game->gamestate->nextState('done');
                return;
            }

            $this->applyWound($game->theah);
            $this->finalize($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === 'pickMuster')
        {
            if (! str_starts_with($reactionId, 'muster-'))
            {
                throw new UserException($game->translate('Invalid choice.'));
            }

            $cardId = (int) substr($reactionId, strlen('muster-'));
            $candidates = $this->getApproachCandidates($game->theah);
            $candidateIds = array_map(fn(Character $c) => $c->Id, $candidates);
            if (! in_array($cardId, $candidateIds, true))
            {
                throw new UserException($game->translate('Invalid character selection.'));
            }

            $target = $game->theah->getCharacterById($this->targetCharacterId);
            $newChar = $game->theah->getCharacterById($cardId);

            $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${opponent_name} returns ${target_inject_code} to their Approach deck and musters ${new_inject_code} instead.'), [
                'reaction_inject_code' => $owner->getInjectCode(),
                'opponent_name' => $game->getPlayerNameById($this->opposingPlayerId),
                'target_inject_code' => $target !== null ? $target->getInjectCode() : '?',
                'new_inject_code' => $newChar !== null ? $newChar->getInjectCode() : '?',
            ]);

            $returnEvent = EventFactory::createCharacterPutIntoApproachDeckEvent($this->opposingPlayerId, $this->targetCharacterId);
            $game->theah->eventCheck($returnEvent);
            $game->theah->queueEvent($returnEvent);

            $musterEvent = EventFactory::createCharacterMusteredEvent($this->opposingPlayerId, $cardId, Game::LOCATION_PLAYER_HOME);
            $game->theah->queueEvent($musterEvent);

            $this->finalize($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        $game->gamestate->nextState('done');
    }
}
