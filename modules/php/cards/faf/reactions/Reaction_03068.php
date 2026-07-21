<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03068 extends RiskReaction
{
    // WHY public: multi-stage cross-player state must survive serialize/DB round-trips
    // (same discipline as Reaction_03044 / Pattern D.1 Manipulative).
    // '' = offer to owner; 'chooseCharacter' / 'chooseLocation' = opposing player must pick.
    public string $stage = '';
    public int $opposingPlayerId = 0;
    public int $chosenCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Force Opponent to Move from Home");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        switch ($this->stage)
        {
            case 'chooseCharacter':
                return $base . $theah->game->translate('${you} must choose an en garde character at your Home to move: ');
            case 'chooseLocation':
                return $base . $theah->game->translate('${you} must choose a City location to move them to: ');
        }

        return $base . $theah->game->translate('${you} may play this Risk after an opponent passes: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        switch ($this->stage)
        {
            case 'chooseCharacter':
                foreach ($this->getEligibleCharacters($theah) as $character)
                {
                    $array[] = $this->createButtonProperty($theah->game, $character->Name, "character-{$character->Id}");
                }
                break;

            case 'chooseLocation':
                foreach ($this->getCityDestinationNames($theah) as $locationName)
                {
                    $array[] = $this->createButtonProperty(
                        $theah->game,
                        sprintf($theah->game->translate('Move to %s'), $theah->game->translate($locationName)),
                        "moveTo-{$locationName}"
                    );
                }
                break;

            default:
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Confusion'), 'use');
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
                break;
        }

        return $array;
    }

    /**
     * @return list<Character>
     */
    private function getEligibleCharacters(Theah $theah): array
    {
        if ($this->opposingPlayerId == 0)
        {
            return [];
        }

        $characters = $theah->getCharactersAtHomeByPlayerId($this->opposingPlayerId);
        return array_values(array_filter(
            $characters,
            fn(Character $character) => ! $character->Engaged
        ));
    }

    /**
     * @return list<string>
     */
    private function getCityDestinationNames(Theah $theah): array
    {
        return array_keys($theah->getCityLocations());
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventHighDramaPhasePlayerPassed && $this->isAvailable())
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

            // Opponent passed — not the Risk owner.
            if ($event->playerId == $owner->ControllerId)
            {
                return;
            }

            // City Reaction: owner must control at least one character in the city.
            $cityCharacters = $event->theah->getCharactersInCityByPlayerId($owner->ControllerId);
            if (count($cityCharacters) == 0)
            {
                return;
            }

            $this->opposingPlayerId = $event->playerId;
            $eligible = $this->getEligibleCharacters($event->theah);
            if (count($eligible) == 0)
            {
                $this->opposingPlayerId = 0;
                return;
            }

            $this->stage = '';
            $this->chosenCharacterId = 0;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $this->initiateOpponentChoice($event->theah);
        }
    }

    private function initiateOpponentChoice(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null || $this->opposingPlayerId == 0)
        {
            return;
        }

        $game = $theah->game;

        $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${player_name} used City Reaction. ${opponent_name} must move an en garde character from Home to a City location.'), [
            'reaction_inject_code' => $owner->getInjectCode(),
            'player_name' => $game->getPlayerNameById($owner->ControllerId),
            'opponent_name' => $game->getPlayerNameById($this->opposingPlayerId),
        ]);

        $eligible = $this->getEligibleCharacters($theah);
        if (count($eligible) == 0)
        {
            // WHY: Preconditions held at offer/pay time; if the board changed mid-pay, finalize without a forced move.
            $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${opponent_name} has no en garde character at Home to move.'), [
                'reaction_inject_code' => $owner->getInjectCode(),
                'opponent_name' => $game->getPlayerNameById($this->opposingPlayerId),
            ]);
            $this->finalize($theah);
            return;
        }

        $this->stage = 'chooseCharacter';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($this->opposingPlayerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function finalize(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        $this->setUsed($theah, true);
        $this->stage = '';
        $this->opposingPlayerId = 0;
        $this->chosenCharacterId = 0;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function resetWithoutUse(Card $owner): void
    {
        $this->stage = '';
        $this->opposingPlayerId = 0;
        $this->chosenCharacterId = 0;
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

        if ($this->stage === 'chooseCharacter')
        {
            if (! str_starts_with($reactionId, 'character-'))
            {
                throw new UserException($game->translate('Invalid choice.'));
            }

            $characterId = (int) substr($reactionId, strlen('character-'));
            $eligibleIds = array_map(fn(Character $c) => $c->Id, $this->getEligibleCharacters($game->theah));
            if (! in_array($characterId, $eligibleIds, true))
            {
                throw new UserException($game->translate('Invalid character selection.'));
            }

            $this->chosenCharacterId = $characterId;
            $this->stage = 'chooseLocation';
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($this->opposingPlayerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState('done');
            return;
        }

        if ($this->stage === 'chooseLocation')
        {
            if (! str_starts_with($reactionId, 'moveTo-'))
            {
                throw new UserException($game->translate('Invalid choice.'));
            }

            $locationName = substr($reactionId, strlen('moveTo-'));
            $destinations = $this->getCityDestinationNames($game->theah);
            if (! in_array($locationName, $destinations, true))
            {
                throw new UserException($game->translate('Invalid location.'));
            }

            $character = $game->theah->getCharacterById($this->chosenCharacterId);
            if ($character === null)
            {
                throw new UserException($game->translate('Character not found.'));
            }

            // Re-validate: still en garde at that player's Home.
            if ($character->ControllerId != $this->opposingPlayerId
                || $character->Location != Game::LOCATION_PLAYER_HOME
                || $character->Engaged)
            {
                throw new UserException($game->translate('Character is no longer a valid choice.'));
            }

            $game->notify->all('message', clienttranslate('${reaction_inject_code}: ${opponent_name} moves ${character_inject_code} from Home to ${location_name}.'), [
                'reaction_inject_code' => $owner->getInjectCode(),
                'opponent_name' => $game->getPlayerNameById($this->opposingPlayerId),
                'character_inject_code' => $character->getInjectCode(),
                'location_name' => $game->translate($locationName),
            ]);

            // WHY: engage=false — printed text is Move only (no Engage).
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $character->Id,
                $character->Location,
                $locationName,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $this->finalize($game->theah);
            $game->gamestate->nextState('done');
            return;
        }

        $game->gamestate->nextState('done');
    }
}
