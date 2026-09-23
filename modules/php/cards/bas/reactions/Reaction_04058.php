<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04058 extends RiskReaction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;

    // WHY public: skipNextEvent must survive serialize across Decline re-release.
    public bool $skipNextEvent = false;

    // WHY public: Cesca (Reaction_01008) beginCopy must survive serialize across target/pay.
    // When set, Decline / invalid-target must NOT re-release onto the performer — there is no
    // intercepted opponent wound to restore; release would wrongly wound Cesca's ally.
    public bool $isCescaCopy = false;

    public ?int $performerId = null;
    public ?int $chosenTargetId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Target Opposing Character Instead");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may wound a target opposing character instead: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $performer = $this->performerId !== null ? $theah->getCharacterById($this->performerId) : null;
        if ($performer === null)
        {
            // WHY: Always offer Decline — a failed Cesca beginCopy seed (performerId null)
            // previously returned zero buttons and soft-locked playerReaction.
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
            return $array;
        }

        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
            return $array;
        }

        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $owner->ControllerId);
        foreach ($opposing as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "redirect-{$character->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId, ?int $targetCharacterId): bool
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return false;
        }

        if (! $this->isOpponentAbility($theah, $sourceId, $abilityId, $owner->ControllerId))
        {
            return false;
        }

        $performer = $theah->getCharacterById($targetCharacterId);
        if ($performer === null || $performer->ControllerId != $owner->ControllerId)
        {
            return false;
        }

        // En Garde Sorcerer — performer must be en garde and a Sorcerer.
        if ($performer->Engaged || ! $performer->hasTrait('Sorcerer'))
        {
            return false;
        }

        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $owner->ControllerId);
        if (count($opposing) === 0)
        {
            return false;
        }

        return true;
    }

    private function isOpponentAbility(Theah $theah, int $sourceId, string $abilityId, int $ownerPlayerId): bool
    {
        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            return $source->ControllerId != $ownerPlayerId && $source->ControllerId != 0;
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action && $action instanceof ICardAbility)
        {
            $owningCard = $action->getOwningCard($theah);
            return $owningCard !== null
                && $owningCard->ControllerId != $ownerPlayerId
                && $owningCard->ControllerId != 0;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterBeingWounded && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null || ! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            // WHY: Temp hand copy from Cesca must not intercept unrelated wounds while waiting on target/pay.
            if ($this->isCescaCopy)
            {
                return;
            }

            if ($this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId, $event->characterId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                $this->characterWoundedEvent = clone $event;
                unset($this->characterWoundedEvent->theah);
                $this->performerId = $event->characterId;
                $owner->IsUpdated = true;
                $event->canceled = true;

                $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($reactionTransitionEvent);
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId === 'decline')
            {
                return;
            }

            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            // Prefer sticky chosenTargetId (saved at pay) over parsing reactionId.
            $characterId = $this->chosenTargetId ?? (int) str_replace('redirect-', '', $event->reactionId);
            $character = $game->theah->getCharacterById($characterId);
            $performer = $game->theah->getCharacterById($this->performerId);

            if ($owner === null || $character === null || $performer === null)
            {
                return;
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                $game->notify->all('message', clienttranslate('${character_inject_code} is not a valid target. ${error}'), [
                    'character_inject_code' => $character->getInjectCode(),
                    'error' => $errorMessage,
                ]);
                if ($this->isCescaCopy)
                {
                    // WHY: Synthetic wound only — do not dump it onto the original performer.
                    $this->characterWoundedEvent = null;
                }
                else
                {
                    // WHY: Pay already spent the Risk; re-release original wound so the ability does not fizzle.
                    $this->releaseEvent($game, (int) $this->performerId);
                    $this->skipNextEvent = true;
                }
                $this->setUsed($game->theah, true);
                $this->resetSavedState($owner);
                return;
            }

            $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to wound ${character_inject_code} instead of ${performer_inject_code}.'), [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $character->getInjectCode(),
                'performer_inject_code' => $performer->getInjectCode(),
            ]);

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id,
                $character->Id,
                $character->Location
            );
            $event->theah->queueEvent($sorceryStartEvent);

            // WHY: No "(If they are able)" on print — redirect unconditionally (do not re-check source ability).
            $this->releaseEvent($game, $characterId);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id,
                $character->Id,
                $character->Location
            );
            $event->theah->queueEvent($sorceryPlayedEvent);

            $this->setUsed($game->theah, true);
            $this->resetSavedState($owner);
        }
    }

    private function releaseEvent(Game $game, int $characterId): void
    {
        if ($this->characterWoundedEvent)
        {
            $this->characterWoundedEvent->characterId = $characterId;
            $game->theah->queueEvent($this->characterWoundedEvent);
            $this->characterWoundedEvent = null;
        }
    }

    private function resetSavedState(?Card $owner): void
    {
        $this->performerId = null;
        $this->chosenTargetId = null;
        $this->isCescaCopy = false;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    /**
     * Cesca (Reaction_01008) copy entry: temp Risk is already in hand; skip intercept and open
     * the opposing-target chooser. Synthetic 1-wound is released onto the chosen target after pay.
     * WHY public: Reaction_01008 hosts via copyCard and must jump past EventCharacterBeingWounded.
     */
    public function beginCopy(Game $game, int $performerId): void
    {
        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            return;
        }

        // WHY addCardToWorld: createCardInLocation does not register the temp hand card.
        // Without it, sticky performerId is set on an orphan instance while getOwningCard /
        // playerReaction args load a fresh DB card with performerId=null → zero buttons.
        $game->theah->addCardToWorld($owner);

        // Prefer the reaction living on the world card (may differ from $this after DB reload).
        $copy = $owner->getReactionById($this->Id);
        if (! ($copy instanceof self))
        {
            return;
        }

        $copy->isCescaCopy = true;
        $copy->performerId = $performerId;
        $copy->chosenTargetId = null;
        $copy->skipNextEvent = false;

        // WHY synthetic: original intercepted wound already resolved before Cesca's Copy click.
        // Fresh 1-wound from this copy; releaseEvent overwrites characterId with the chosen target.
        $wound = EventFactory::createCharacterBeingWoundedEvent(
            $performerId,
            $owner->Id,
            1,
            $owner->getInjectCode(),
            $copy->Id
        );
        unset($wound->theah);
        $copy->characterWoundedEvent = $wound;

        $owner->IsUpdated = true;
        // WHY immediate persist: beginCopy runs outside runEvents' IsUpdated flush; the
        // following reaction transition may open playerReaction on a new request that
        // rebuilds from DB.
        $game->updateCardObjectInDb($owner);

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $copy->Id);
        $game->theah->queueEvent($transition);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            return [false, $game->translate('Invalid card.')];
        }

        $performer = $game->theah->getCharacterById($this->performerId);
        if ($performer === null)
        {
            return [false, $game->translate('Performer not found.')];
        }

        if (! $character->isControlled())
        {
            return [false, $game->translate('You cannot target a character that is not controlled.')];
        }

        if ($character->ControllerId == $owner->ControllerId)
        {
            return [false, $game->translate('You cannot target your own character.')];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate('Target character is not at the same location as the performer.')];
        }

        return [true, ''];
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId !== 'decline')
        {
            $characterId = (int) str_replace('redirect-', '', $reactionId);
            $character = $game->theah->getCharacterById($characterId);
            if ($character === null)
            {
                throw new UserException($game->translate('Character not found.'));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $this->chosenTargetId = $characterId;
            if ($owner === null)
            {
                throw new UserException($game->translate('Invalid card.'));
            }
            $owner->IsUpdated = true;

            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($payEvent);

            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($payTransition);
        }
        else
        {
            if ($this->isCescaCopy)
            {
                // WHY: No intercepted opponent wound — Decline just aborts the copy effect.
                $this->characterWoundedEvent = null;
            }
            else
            {
                // WHY: Intercept clones+cancels the wound. Decline must re-release onto the original
                // performer or the opponent's ability fizzles for free (Cross `_02016` fix).
                if ($this->performerId !== null)
                {
                    $this->releaseEvent($game, $this->performerId);
                }
                $this->skipNextEvent = true;
            }
            $this->resetSavedState($owner);
        }

        $game->gamestate->nextState('done');
    }
}
