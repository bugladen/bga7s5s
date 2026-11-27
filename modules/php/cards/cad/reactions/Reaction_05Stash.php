<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// City Reaction: When a pressure you initiated fails, en garde target character at the pressured location.
class Reaction_05Stash extends RiskReaction implements IAbilityThatTargetsCharacters
{
    // WHY public: multi-stage reaction survives serialize/DB round-trips (04020 / 03068).
    public string $stage = '';
    public string $pressureLocation = '';
    // WHY public: set at the target pick, consumed by the EventCharacterTargeted hook.
    public int $chosenTargetId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "En garde target at failed pressure location";
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'chooseCharacter')
        {
            return $base . '${you} must choose a target character to En Garde at the pressured location: ';
        }

        return $base . '${you} may play this Risk when a pressure you initiated fails: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->stage === 'chooseCharacter')
        {
            foreach ($this->getValidTargets($theah) as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, $character->Name, "character-{$character->Id}");
            }

            return $array;
        }

        $array[] = $this->createButtonProperty($theah->game, 'Play Extra Rations', 'use');
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    /**
     * Engarde only changes Engaged characters (Serenissima 109c) — ready must not highlight.
     *
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah): array
    {
        if ($this->pressureLocation === '')
        {
            return [];
        }

        $atLocation = $theah->getCharactersAtLocation($this->pressureLocation);
        return array_values(array_filter(
            $atLocation,
            fn(Character $character) => $character->isControlled() && $character->Engaged
        ));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        if ($this->pressureLocation === '')
        {
            return [false, 'No pressured location.'];
        }

        if (! $character->isControlled())
        {
            return [false, 'You cannot target a character that is not controlled.'];
        }

        if ($character->Location != $this->pressureLocation)
        {
            return [false, 'Target is not at the pressured location.'];
        }

        if (! $character->Engaged)
        {
            return [false, 'Character is not engaged.'];
        }

        return [true, ''];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY LocationPressureResult not Pressured: Objection (_01027) / Leather Spaulders
        // (_04026) can delete/replace the Result after Pressured fires (02004 WHY).
        if ($event instanceof EventLocationPressureResult && $this->isAvailable())
        {
            if ($event->success)
            {
                return;
            }

            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }
            if (! ($owner->Location == Game::LOCATION_HAND))
            {
                return;
            }

            // City Reaction gate — controller must have city presence (03068 / 02053).
            if (count($event->theah->getCharactersInCityByPlayerId($owner->ControllerId)) === 0)
            {
                return;
            }

            // You initiated the pressure.
            if ($event->playerId != $owner->ControllerId)
            {
                return;
            }

            $this->pressureLocation = $event->location;
            if (count($this->getValidTargets($event->theah)) === 0)
            {
                $this->pressureLocation = '';
                return;
            }

            $this->stage = '';
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $this->beginCharacterChoice($event->theah);
        }

        // WHY: Deliberately NOT behind isAvailable() — setUsed already ran at the pick, so
        // isAvailable() is false by the time the hook arrives (Reaction_05Quack seat). Matched on
        // abilityId + the stored target so another card's targeting cannot trigger it.
        if ($event instanceof EventCharacterTargeted
            && $event->abilityId == $this->Id
            && $this->chosenTargetId != 0
            && $this->chosenTargetId == $event->targetId)
        {
            $owner = $this->getOwningCard($event->theah);
            $target = $event->theah->getCharacterById($event->targetId);

            if (! $event->canceled && $owner !== null && $target !== null)
            {
                // WHY createCardEngardedEvent (not Engaged): Engarde sets Engaged=false (02051).
                // Action_01152a incorrectly uses Engaged for an En Garde effect — do not copy it.
                $engardeEvent = EventFactory::createCardEngardedEvent(
                    $owner->ControllerId,
                    $target->Id,
                    $owner->Id,
                    $this->Id
                );
                $engardeEvent->batchId = $event->batchId ?? $event->theah->game->getNextEventBatchId();
                $event->theah->queueEvent($engardeEvent);
            }

            // Reset on survive AND cancel so no sticky target survives a canceled hook.
            $this->chosenTargetId = 0;
            $this->resetWithoutUse($owner);
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

        if ($this->stage === 'chooseCharacter')
        {
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

            $game->notify->all('message', '${reaction_inject_code}: ${player_name} En gardes ${character_inject_code}.', [
                'reaction_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $target->getInjectCode(),
            ]);

            // WHY: Permission pre-check stays at the pick so a veto surfaces to the player here
            // (a throw inside the survive hook would crash event dispatch). The En Garde that is
            // actually queued is rebuilt in the EventCharacterTargeted branch of handleEvent.
            $game->theah->eventCheck(EventFactory::createCardEngardedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            ));

            // WHY: Fire targeting alone here. En Garde emits from handleEvent only when
            // EventCharacterTargeted survives (Cloud Bottle / UL cancel it). chosenTargetId /
            // pressureLocation stay alive until that hook runs; it resets them on BOTH survive and
            // cancel (Reaction_05Teapot / 04058 seat). setUsed stays here: the Risk is already paid.
            $this->chosenTargetId = $target->Id;
            $this->stage = '';

            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState('done');
            return;
        }

        $game->gamestate->nextState('done');
    }

    private function beginCharacterChoice(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return;
        }

        $game = $theah->game;
        $game->notify->all('message', '${reaction_inject_code}: ${player_name} used Reaction after a failed pressure.', [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
        ]);

        if (count($this->getValidTargets($theah)) === 0)
        {
            $this->setUsed($theah, true);
            $this->resetWithoutUse($owner);
            return;
        }

        $this->stage = 'chooseCharacter';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function resetWithoutUse(?Card $owner): void
    {
        $this->stage = '';
        $this->pressureLocation = '';
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }
}
