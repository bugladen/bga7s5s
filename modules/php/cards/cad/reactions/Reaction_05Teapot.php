<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Teapot extends RiskReaction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    // WHY public: the offer, the target pick and the post-pay effect run in separate
    // requests, so these must survive serialize/DB round-trips (same as Reaction_04020).
    public int $performerId = 0;
    public string $toLocation = '';
    public int $chosenTargetId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no translate (translation is done later).
        $this->Name = "Move Target Opposing Character Home";
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . '${you} may move a target opposing character Home: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleTargets($theah, $owner, $this->toLocation) as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, 'Move ' . $character->Name . ' Home', "character-{$character->Id}");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    /**
     * @return list<Character> opposing characters at the performer's new location
     */
    private function getEligibleTargets(Theah $theah, Card $owner, string $location): array
    {
        if ($location === '')
        {
            return [];
        }

        return array_values(array_filter(
            $theah->getOpposingCharactersAtLocation($location, $owner->ControllerId),
            fn(Character $character) => $character->isControlled()
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $this->isAvailable())
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

            // "Your performer" is the character named by the trigger — gate it directly.
            $performer = $event->theah->getCardById($event->cardId);
            if (! ($performer instanceof Character) || $performer->ControllerId != $owner->ControllerId)
            {
                return;
            }

            // Sorcerer heading gate (mechanical performer trait + ISorcererAbility).
            if (! $performer->hasTrait('Sorcerer'))
            {
                return;
            }

            // WHY both checks: En Garde is a precondition, not an Engage cost. EventCardMoved has
            // runEventHubAfterCards = true, so the hub has not yet applied $event->engage when
            // card handlers run — a move that engages leaves Engaged false here, but the
            // performer will not be en garde by the time the Reaction is played.
            if ($performer->Engaged || $event->engage)
            {
                return;
            }

            // "moves to a City location" — Home / discard destinations do not count.
            if (! $event->theah->locationInCity($event->toLocation))
            {
                return;
            }

            // Hide when no opposing character is there: the Reaction would be a dead play.
            if (count($this->getEligibleTargets($event->theah, $owner, $event->toLocation)) === 0)
            {
                return;
            }

            $this->toLocation = $event->toLocation;
            $this->performerId = $performer->Id;
            $this->chosenTargetId = 0;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY apply the effect here, not in performReaction: a cancel reaction can fire between
        // pay and this event. Deferring keeps the move paired with a resolved Risk play.
        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($this->performerId);
            $target = $event->theah->getCharacterById($this->chosenTargetId);

            if ($owner === null)
            {
                return;
            }

            if ($performer === null || $target === null || ! $this->isTargetStillValid($target, $owner))
            {
                $game->notify->all('message', '${reaction_inject_code}: the target is no longer valid.', [
                    'reaction_inject_code' => $owner->getInjectCode(),
                ]);
                $this->setUsed($event->theah, true);
                $this->resetState($owner);
                return;
            }

            $game->notify->all('message', '${reaction_inject_code}: ${player_name} used Reaction to move ${character_inject_code} Home.', [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $target->getInjectCode(),
            ]);

            $event->theah->queueEvent(EventFactory::createSorcererAbilityStartEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id,
                $target->Id,
                $target->Location
            ));

            // WHY engage = false: printed text is Move only (no Engage).
            // WHY sourceId/abilityId: lets "redirect a targeted ability" effects (Cross of the
            // Martyrs) recognise this as a targeted ability.
            $event->theah->queueEvent(EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $target->Id,
                $target->Location,
                Game::LOCATION_PLAYER_HOME,
                false,
                $owner->Id,
                $this->Id
            ));

            $event->theah->queueEvent(EventFactory::createSorcererAbilityPlayedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id,
                $target->Id,
                $target->Location
            ));

            $this->setUsed($event->theah, true);
            $this->resetState($owner);
        }
    }

    private function isTargetStillValid(Character $target, Card $owner): bool
    {
        return $target->isControlled()
            && $target->ControllerId != $owner->ControllerId
            && $target->Location == $this->toLocation;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            return [false, 'Invalid card.'];
        }

        if (! $character->isControlled())
        {
            return [false, 'You cannot target a character that is not controlled.'];
        }

        if ($character->ControllerId == $owner->ControllerId)
        {
            return [false, 'You cannot target your own character.'];
        }

        if ($character->Location != $this->toLocation)
        {
            return [false, 'Target character is not at the same location as the performer.'];
        }

        return [true, ''];
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

        if ($reactionId === 'pass')
        {
            // WHY no setUsed: the Risk stays in hand for a later qualifying move.
            $this->resetState($owner);
            $game->gamestate->nextState('done');
            return;
        }

        if (! str_starts_with($reactionId, 'character-'))
        {
            throw new UserException('Invalid choice.');
        }

        $target = $game->theah->getCharacterById((int) substr($reactionId, strlen('character-')));
        if ($target === null)
        {
            throw new UserException('Character not found.');
        }

        [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
        if (! $isValid)
        {
            throw new UserException($errorMessage);
        }

        $performer = $game->theah->getCharacterById($this->performerId);
        if ($performer === null || $performer->Location != $this->toLocation || $performer->Engaged)
        {
            throw new UserException('Your performer is no longer en garde at that location.');
        }

        // Probe the move so a "cannot move" effect rejects the choice before the Risk is paid for.
        $probe = EventFactory::createCardMovingEvent(
            $owner->ControllerId,
            $target->Id,
            $target->Location,
            Game::LOCATION_PLAYER_HOME,
            false,
            $owner->Id,
            $this->Id
        );
        $game->theah->eventCheck($probe);

        $this->chosenTargetId = $target->Id;
        $owner->IsUpdated = true;

        $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
        $game->theah->queueEvent($payEvent);

        $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($payTransition);

        $game->gamestate->nextState('done');
    }

    private function resetState(?Card $owner): void
    {
        $this->performerId = 0;
        $this->toLocation = '';
        $this->chosenTargetId = 0;

        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }
}
