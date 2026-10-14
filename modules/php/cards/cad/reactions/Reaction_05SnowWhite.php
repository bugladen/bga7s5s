<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Orel;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Volk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05SnowWhite extends CardReaction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Muster Orel or Volk at Home";
    }

    public function getReactionDescription(Theah $theah): string
    {
        // WHY: single quotes — ${you} is a BGA client token, not PHP (Volk/Cooper).
        return parent::getReactionDescription($theah)
            . '${you} may Muster Orel or Volk at Home from out of play: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return $array;
        }

        // WHY: Buttons only — at most two named animals; no follow-on chooseList state.
        if ($this->isAnimalMusterable($theah, $owner->ControllerId, '05Orel'))
        {
            $array[] = $this->createButtonProperty($theah->game, 'Muster Orel', 'muster-05Orel');
        }

        if ($this->isAnimalMusterable($theah, $owner->ControllerId, '05Volk'))
        {
            $array[] = $this->createButtonProperty($theah->game, 'Muster Volk', 'muster-05Volk');
        }

        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    /**
     * @return list<Character>
     */
    private function getControlledSorcerers(Theah $theah, int $controllerId): array
    {
        return array_values(array_filter(
            $theah->getCharactersInPlayByPlayerId($controllerId),
            fn(Character $character) => $character->hasTrait("Sorcerer")
        ));
    }

    /**
     * True when the named animal is not already in play for this player (create or remuster).
     */
    private function isAnimalMusterable(Theah $theah, int $playerId, string $className): bool
    {
        return $this->findInPlayAnimal($theah, $playerId, $className) === null;
    }

    private function findInPlayAnimal(Theah $theah, int $playerId, string $className): ?Character
    {
        foreach ($theah->getCharactersInPlayByPlayerId($playerId) as $character)
        {
            if ($this->characterMatchesClass($character, $className))
            {
                return $character;
            }
        }

        return null;
    }

    /**
     * Owned instance not currently in play (locker / discard / elsewhere), or null to create.
     */
    private function findOutOfPlayAnimal(Theah $theah, int $playerId, string $className): ?Character
    {
        $lockerName = $theah->game->getPlayerLockerName($playerId);
        foreach ($theah->getCardObjectsAtLocation($lockerName) as $card)
        {
            if ($card instanceof Character
                && $card->OwnerId == $playerId
                && $this->characterMatchesClass($card, $className))
            {
                return $card;
            }
        }

        $discardName = $theah->game->getPlayerDiscardDeckName($playerId);
        foreach ($theah->getCardObjectsAtLocation($discardName) as $card)
        {
            if ($card instanceof Character
                && $card->OwnerId == $playerId
                && $this->characterMatchesClass($card, $className))
            {
                return $card;
            }
        }

        // WHY: $theah->cards is private — use getAllCards() (hand/purgatory/dueling line).
        // Locker is intentionally not in world cards; handled above via getCardObjectsAtLocation.
        foreach ($theah->getAllCards() as $card)
        {
            if (! ($card instanceof Character)
                || $card->OwnerId != $playerId
                || ! $this->characterMatchesClass($card, $className))
            {
                continue;
            }

            if (! $theah->cardInPlay($card))
            {
                return $card;
            }
        }

        return null;
    }

    private function characterMatchesClass(Character $character, string $className): bool
    {
        return ($className === '05Orel' && $character instanceof _05Orel)
            || ($className === '05Volk' && $character instanceof _05Volk);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPhasePlanningEnd && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            // Chosen schemes sit at Home until Dusk locker (_01098 / _04025).
            if ($owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            // WHY: Sorcerer Reaction with no pickable performer in the trigger —
            // Merchant Reaction_04025 shape: require ≥1 controlled traited character.
            $sorcerers = $this->getControlledSorcerers($event->theah, $owner->ControllerId);
            if (count($sorcerers) < 1)
            {
                return;
            }

            $canMusterOrel = $this->isAnimalMusterable($event->theah, $owner->ControllerId, '05Orel');
            $canMusterVolk = $this->isAnimalMusterable($event->theah, $owner->ControllerId, '05Volk');
            if (! $canMusterOrel && ! $canMusterVolk)
            {
                return;
            }

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId === 'pass')
        {
            // Pass does not consume setUsed — EventPhasePlanningEnd will not re-fire today.
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'muster-'))
        {
            $className = substr($reactionId, strlen('muster-'));
            if ($className !== '05Orel' && $className !== '05Volk')
            {
                $game->gamestate->nextState("done");
                return;
            }

            if ($owner === null || $owner->Location != Game::LOCATION_PLAYER_HOME)
            {
                $game->gamestate->nextState("done");
                return;
            }

            $playerId = $owner->ControllerId;
            $sorcerers = $this->getControlledSorcerers($game->theah, $playerId);
            if (count($sorcerers) < 1)
            {
                $game->gamestate->nextState("done");
                return;
            }

            if (! $this->isAnimalMusterable($game->theah, $playerId, $className))
            {
                $game->notify->all("message", '${scheme_inject_code}: that Animal is already in play.', [
                    "scheme_inject_code" => $owner->getInjectCode(),
                ]);
                $game->gamestate->nextState("done");
                return;
            }

            $performer = $sorcerers[0];
            $animal = $this->findOutOfPlayAnimal($game->theah, $playerId, $className);

            // WHY: Orel/Volk cannot enter Approach or faction decks — first muster creates
            // them from out of play (Lucas Damned createCardInLocation shape). Remuster
            // from Locker/Discard when a prior copy exists.
            if ($animal === null)
            {
                $animal = $game->createCardInLocation(
                    $className,
                    Game::LOCATION_PURGATORY,
                    $playerId,
                    $playerId
                );
                $game->theah->addCardToWorld($animal);
            }
            else
            {
                $game->theah->addCardToWorld($animal);

                $lockerName = $game->getPlayerLockerName($playerId);
                $discardName = $game->getPlayerDiscardDeckName($playerId);
                if ($animal->Location === $lockerName)
                {
                    $remove = EventFactory::createCardRemovedFromLockerEvent($playerId, $animal->Id);
                    $game->theah->queueEvent($remove);
                }
                else if ($animal->Location === $discardName)
                {
                    $remove = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $animal->Id);
                    $game->theah->queueEvent($remove);
                }
            }

            $sorceryStart = EventFactory::createSorcererAbilityStartEvent(
                $playerId,
                $owner->Id,
                $this->Id,
                $performer->Id
            );
            $game->theah->queueEvent($sorceryStart);

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} Musters ${character_inject_code} at Home from out of play.', [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "character_inject_code" => $animal->getInjectCode(),
            ]);

            $musterEvent = EventFactory::createCharacterMusteredEvent(
                $playerId,
                $animal->Id,
                Game::LOCATION_PLAYER_HOME
            );
            $game->theah->queueEvent($musterEvent);

            $sorceryPlayed = EventFactory::createSorcererAbilityPlayedEvent(
                $playerId,
                $owner->Id,
                $this->Id,
                $performer->Id
            );
            $game->theah->queueEvent($sorceryPlayed);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
