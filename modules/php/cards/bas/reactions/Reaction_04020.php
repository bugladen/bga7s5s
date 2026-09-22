<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04020 extends RiskReaction implements IAbilityThatTargetsCharacters
{
    // WHY public: multi-stage reaction may hand off to another player across serialize/DB round-trips.
    public string $stage = '';
    public string $pressureLocation = '';
    public int $performerId = 0;
    public int $targetOpponentId = 0;
    public int $targetCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure Penalty and Wound Unless Engage");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        switch ($this->stage)
        {
            case 'chooseOpponent':
                return $base . $theah->game->translate('${you} must choose a target opponent to apply -1 to their pressure total: ');
            case 'chooseCharacter':
                return $base . $theah->game->translate('${you} must choose a target character at the pressured location: ');
            case 'engageOrWound':
                $target = $this->targetCharacterId > 0 ? $theah->getCharacterById($this->targetCharacterId) : null;
                $targetName = $target ? $theah->game->translate($target->Name) : $theah->game->translate('the target character');
                return $base . sprintf($theah->game->translate('${you} may engage %s or take the wound: '), $targetName);
        }

        return $base . $theah->game->translate('${you} may play this Risk when a pressure occurs at an adjacent location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCard($theah);

        switch ($this->stage)
        {
            case 'chooseOpponent':
                foreach ($this->getOpponentIdsAtLocation($theah, $owner->ControllerId, $this->pressureLocation) as $opponentId)
                {
                    $array[] = $this->createButtonProperty(
                        $theah->game,
                        $theah->game->getPlayerNameById($opponentId),
                        "opponent-{$opponentId}"
                    );
                }
                break;

            case 'chooseCharacter':
                foreach ($this->getTargetCharacters($theah, $owner->ControllerId, $this->pressureLocation) as $character)
                {
                    $array[] = $this->createButtonProperty($theah->game, $character->Name, "character-{$character->Id}");
                }
                break;

            case 'engageOrWound':
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Engage'), 'engage');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Take the Wound'), 'wound');
                break;

            default:
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Vantage Point'), 'use');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
                break;
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring && $this->isAvailable())
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

            $performers = $this->findQualifyingPerformers($event->theah, $owner->ControllerId, $event->location);
            if (count($performers) === 0)
            {
                return;
            }

            if (count($this->getOpponentIdsAtLocation($event->theah, $owner->ControllerId, $event->location)) === 0)
            {
                return;
            }

            if (count($this->getTargetCharacters($event->theah, $owner->ControllerId, $event->location)) === 0)
            {
                return;
            }

            $this->stage = '';
            $this->pressureLocation = $event->location;
            $this->performerId = $performers[0]->Id;
            $this->targetOpponentId = 0;
            $this->targetCharacterId = 0;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $this->beginPostPayFlow($event->theah);
        }
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
                $payEvent = EventFactory::createEnteringPayStateEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    Game::PAY_STATE_IN_HAND_REACTION,
                    $this->Id
                );
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

        if ($this->stage === 'chooseOpponent')
        {
            if (! str_starts_with($reactionId, 'opponent-'))
            {
                throw new UserException($game->translate('Invalid choice.'));
            }

            $opponentId = (int) substr($reactionId, strlen('opponent-'));
            $validOpponentIds = $this->getOpponentIdsAtLocation($game->theah, $owner->ControllerId, $this->pressureLocation);
            if (! in_array($opponentId, $validOpponentIds, true))
            {
                throw new UserException($game->translate('Invalid opponent selection.'));
            }

            $this->targetOpponentId = $opponentId;
            $this->applyPressurePenalty($game->theah);
            $this->beginCharacterChoice($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === 'chooseCharacter')
        {
            if (! str_starts_with($reactionId, 'character-'))
            {
                throw new UserException($game->translate('Invalid choice.'));
            }

            $characterId = (int) substr($reactionId, strlen('character-'));
            $target = $game->theah->getCharacterById($characterId);
            if ($target === null)
            {
                throw new UserException($game->translate('Character not found.'));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $this->targetCharacterId = $target->Id;
            $this->resolveTargetCharacter($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === 'engageOrWound')
        {
            $target = $game->theah->getCharacterById($this->targetCharacterId);
            if ($target === null)
            {
                $this->finalize($game->theah);
                $game->gamestate->nextState('done');
                return;
            }

            if ($reactionId === 'engage')
            {
                $game->notify->all('message', clienttranslate('${player_name} decided to engage ${character_inject_code}.'), [
                    'player_name' => $game->getPlayerNameById($target->ControllerId),
                    'character_inject_code' => $target->getInjectCode(),
                ]);

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }
            else
            {
                $this->applyWound($game->theah, $target);
            }

            $this->finalize($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        $game->gamestate->nextState('done');
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            return [false, $game->translate('Invalid card.')];
        }

        if (! $character->isControlled())
        {
            return [false, $game->translate('You cannot target a character that is not controlled.')];
        }

        if ($character->ControllerId == $owner->ControllerId)
        {
            return [false, $game->translate('You cannot target your own character.')];
        }

        if ($character->Location != $this->pressureLocation)
        {
            return [false, $game->translate('Target is not at the pressured location.')];
        }

        return [true, ''];
    }

    private function beginPostPayFlow(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null || $this->pressureLocation === '')
        {
            return;
        }

        $game = $theah->game;
        $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} used Reaction at the adjacent pressure.'), [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
        ]);

        $opponentIds = $this->getOpponentIdsAtLocation($theah, $owner->ControllerId, $this->pressureLocation);
        if (count($opponentIds) === 1)
        {
            $this->targetOpponentId = $opponentIds[0];
            $this->applyPressurePenalty($theah);
            $this->beginCharacterChoice($theah);
            return;
        }

        $this->stage = 'chooseOpponent';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function beginCharacterChoice(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return;
        }

        $this->stage = 'chooseCharacter';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function resolveTargetCharacter(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        $target = $theah->getCharacterById($this->targetCharacterId);
        if ($owner === null || $target === null)
        {
            return;
        }

        $game = $theah->game;
        $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} targets ${target_inject_code}.'), [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
            'target_inject_code' => $target->getInjectCode(),
        ]);

        if ($target->Engaged)
        {
            $game->notify->all('message', clienttranslate('${target_inject_code} was already Engaged and must take the wound.'), [
                'target_inject_code' => $target->getInjectCode(),
            ]);
            $this->applyWound($theah, $target);
            $this->finalize($theah);
            return;
        }

        $this->stage = 'engageOrWound';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($target->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function applyPressurePenalty(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null || $this->targetOpponentId === 0)
        {
            return;
        }

        $game = $theah->game;
        $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::VANTAGE_POINT_PRESSURE_TYPE);
        $game->globals->set(Game::VANTAGE_POINT_PLAYER_ID, $this->targetOpponentId);

        $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${opponent_name} applies -1 to their total for this Pressure.'), [
            'reaction_inject_code' => $owner->getInjectCode(),
            'opponent_name' => $game->getPlayerNameById($this->targetOpponentId),
        ]);
    }

    private function applyWound(Theah $theah, Character $target): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return;
        }

        $game = $theah->game;
        $game->notify->all('message', clienttranslate('${player_name} decided to wound ${character_inject_code}.'), [
            'player_name' => $game->getPlayerNameById($target->ControllerId),
            'character_inject_code' => $target->getInjectCode(),
        ]);

        $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
            $target->Id,
            $owner->Id,
            1,
            $owner->getInjectCode(),
            $this->Id
        );
        $theah->eventCheck($woundEvent);
        $theah->queueEvent($woundEvent);

        $performer = $theah->getCharacterById($this->performerId);
        if ($performer !== null)
        {
            $rangedEvent = EventFactory::createRangedAbilityPlayedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id,
                $target->Id,
                $target->Location
            );
            $theah->queueEvent($rangedEvent);
        }
    }

    private function finalize(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        $this->setUsed($theah, true);
        $this->resetWithoutUse($owner);
    }

    private function resetWithoutUse(?Card $ownerCard): void
    {
        $this->stage = '';
        $this->pressureLocation = '';
        $this->performerId = 0;
        $this->targetOpponentId = 0;
        $this->targetCharacterId = 0;

        if ($ownerCard !== null)
        {
            $ownerCard->IsUpdated = true;
        }
    }

    /**
     * @return list<Character>
     */
    private function findQualifyingPerformers(Theah $theah, int $ownerPlayerId, string $pressureLocation): array
    {
        $adjacentLocations = $theah->getAdjacentCityLocations($pressureLocation, false);
        $performers = [];

        foreach ($adjacentLocations as $location)
        {
            foreach ($theah->getCharactersAtLocation($location) as $character)
            {
                if ($character->ControllerId != $ownerPlayerId)
                {
                    continue;
                }
                // WHY En Garde Reaction: performer must already be ready — not an Engage cost.
                if ($character->Engaged)
                {
                    continue;
                }
                if (! $this->hasRangedWeaponEquipped($character, $theah))
                {
                    continue;
                }
                $performers[] = $character;
            }
        }

        return $performers;
    }

    private function hasRangedWeaponEquipped(Character $character, Theah $theah): bool
    {
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment && $attachment->hasTrait('Weapon') && $attachment->hasTrait('Ranged'))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function getOpponentIdsAtLocation(Theah $theah, int $ownerPlayerId, string $location): array
    {
        $opponentIds = [];
        foreach ($theah->getCharactersAtLocation($location) as $character)
        {
            if (! $character->isControlled())
            {
                continue;
            }
            if ($character->ControllerId == $ownerPlayerId)
            {
                continue;
            }
            $opponentIds[$character->ControllerId] = $character->ControllerId;
        }

        return array_values($opponentIds);
    }

    /**
     * @return list<Character>
     */
    private function getTargetCharacters(Theah $theah, int $ownerPlayerId, string $location): array
    {
        return array_values(array_filter(
            $theah->getCharactersAtLocation($location),
            fn(Character $character) => $character->isControlled() && $character->ControllerId != $ownerPlayerId
        ));
    }
}
