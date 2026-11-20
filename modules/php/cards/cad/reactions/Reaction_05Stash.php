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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// City Reaction: When a pressure you initiated fails, en garde target character at the pressured location.
class Reaction_05Stash extends RiskReaction implements IAbilityThatTargetsCharacters
{
    // WHY public: multi-stage reaction survives serialize/DB round-trips (04020 / 03068).
    public string $stage = '';
    public string $pressureLocation = '';

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

            // WHY createCardEngardedEvent (not Engaged): Engarde sets Engaged=false (02051).
            // Action_01152a incorrectly uses Engaged for an En Garde effect — do not copy it.
            $engardeEvent = EventFactory::createCardEngardedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($engardeEvent);
            $game->theah->queueEvent($engardeEvent);

            $this->setUsed($game->theah, true);
            $this->resetWithoutUse($owner);
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
