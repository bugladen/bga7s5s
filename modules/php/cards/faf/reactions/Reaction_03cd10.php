<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\TraitNames;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03cd10 extends CardReaction
{
    // '' (idle), 'letter', 'trait', 'target'
    private string $stage = '';
    private string $chosenLetter = '';
    private string $chosenTrait = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Name a Trait and target an opposing character");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        switch ($this->stage)
        {
            case 'letter':
                return $base . $theah->game->translate('${you} must choose the first letter of a Trait:');
            case 'trait':
                return $base . sprintf($theah->game->translate('${you} must choose a Trait starting with "%s":'), $this->chosenLetter);
            case 'target':
                return $base . sprintf($theah->game->translate('${you} must target an opposing character (named Trait: %s):'), $this->chosenTrait);
        }
        return $base;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        switch ($this->stage)
        {
            case 'letter':
                foreach ($this->getLettersWithTraits() as $letter)
                {
                    $array[] = $this->createButtonProperty($theah->game, $letter, "letter-{$letter}");
                }
                break;

            case 'trait':
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('< Back'), 'back');
                foreach ($this->getTraitsStartingWith($this->chosenLetter) as $idx => $trait)
                {
                    $array[] = $this->createButtonProperty($theah->game, $trait, "trait-{$idx}");
                }
                break;

            case 'target':
                $owner = $this->getOwningCharacter($theah);
                // "Opposing" = different controller AND same location as Julius.
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('< Back'), 'back');
                foreach ($theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId) as $character)
                {
                    $array[] = $this->createButtonProperty($theah->game, $character->Name, "target-{$character->Id}");
                }
                break;
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: EventCharacterRecruited fires after the hub has set ControllerId on Julius
        // (runEventHubAfterCards defaults to false). After recruitment, Julius is at his
        // city-deck location with his new controller; that controller decides whether to
        // use the reaction. Only fire when Julius himself is the recruited character —
        // recruiting some other city character doesn't trigger this Reaction.
        if ($event instanceof EventCharacterRecruited && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            // Recruitment does not change Julius's location, so $owner->Location is authoritative.
            // cardInCity($owner) guards the rare path where Julius could be recruited from
            // somewhere other than a city location — only fire when he's actually in the city.
            if ($event->characterId == $owner->Id
                && $event->theah->cardInCity($owner)
                && $this->hasOpposingCharacterAtLocation($event->theah, $owner, $owner->Location))
            {
                $this->beginReaction($event);
            }
        }

        // WHY: EventCardMoved has `runEventHubAfterCards = true`, so the hub handler that
        // writes $card->Location = $event->toLocation has NOT yet run when this code fires.
        // Read $event->toLocation, not $owner->Location, for the post-move position.
        // locationInCity($event->toLocation) is the "Julius must be in the city" gate for
        // this branch — after the move applies, his location is $event->toLocation.
        // EventCharacterRecruited does not also fire a move event (recruitment only
        // transfers control), so these two triggers don't double-fire on a single recruitment.
        if ($event instanceof EventCardMoved && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->cardId == $owner->Id
                && $event->theah->locationInCity($event->toLocation)
                && $this->hasOpposingCharacterAtLocation($event->theah, $owner, $event->toLocation))
            {
                $this->beginReaction($event);
            }
        }
    }

    private function hasOpposingCharacterAtLocation(Theah $theah, $owner, string $location): bool
    {
        // "Opposing" = different controller AND same location as Julius.
        return count($theah->getOpposingCharactersAtLocation($location, $owner->ControllerId)) > 0;
    }

    private function beginReaction(Event $event): void
    {
        $owner = $this->getOwningCharacter($event->theah);

        $this->stage = 'letter';
        $this->chosenLetter = '';
        $this->chosenTrait = '';
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

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
            if ($this->stage === 'trait')
            {
                $this->stage = 'letter';
                $this->chosenLetter = '';
            }
            else if ($this->stage === 'target')
            {
                $this->stage = 'trait';
                $this->chosenTrait = '';
            }
            $owner->IsUpdated = true;
            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'letter-'))
        {
            $this->chosenLetter = substr($reactionId, strlen('letter-'));
            $this->stage = 'trait';
            $owner->IsUpdated = true;
            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'trait-'))
        {
            $idx = (int)substr($reactionId, strlen('trait-'));
            $traits = $this->getTraitsStartingWith($this->chosenLetter);
            $this->chosenTrait = $traits[$idx] ?? '';
            $this->stage = 'target';
            $owner->IsUpdated = true;
            $this->requeue($game, $owner->ControllerId, $owner->Id);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'target-'))
        {
            $targetId = (int)substr($reactionId, strlen('target-'));
            $this->resolveEffect($game, $targetId);
            $this->resetStage();
            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
            $game->gamestate->nextState("done");
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function requeue(Game $game, int $playerId, int $sourceId): void
    {
        $transition = EventFactory::createReactionTransitionEvent($playerId, $sourceId, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resolveEffect(Game $game, int $targetId): void
    {
        $owner = $this->getOwningCharacter($game->theah);
        $target = $game->theah->getCharacterById($targetId);
        if (!$target)
        {
            return;
        }

        $game->notify->all("message",
            clienttranslate('${card_inject_code}: ${player_name} names <strong>${trait_name}</strong> and targets ${target_inject_code}.'),
            [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "trait_name" => $this->chosenTrait,
                "target_inject_code" => $target->getInjectCode(),
            ]);

        // Reveal up to 2 random cards from the target's controller's hand. If the hand
        // has fewer than 2 cards, reveal what's there.
        $deck = $game->getGameDeckObject();
        $hand = array_values($deck->getCardsInLocation(Game::LOCATION_HAND, $target->ControllerId));

        $count = min(2, count($hand));
        $matched = false;
        if ($count > 0)
        {
            $keys = (array)array_rand($hand, $count);
            foreach ($keys as $key)
            {
                $card = $game->getCardObjectFromDb($hand[$key]['id']);
                $game->theah->addCardToWorld($card);

                $game->notify->all("message",
                    clienttranslate('${card_inject_code} reveals ${picked_card} from <strong>${player_name}</strong>\'s hand.'),
                    [
                        "card_inject_code" => $owner->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($target->ControllerId),
                        "picked_card" => $card->getInjectCode(),
                        "card" => $card->getPropertyArray($game),
                    ]);

                if ($card->hasTrait($this->chosenTrait))
                {
                    $matched = true;
                }
            }
        }
        else
        {
            $game->notify->all("message",
                clienttranslate('${card_inject_code}: ${player_name}\'s hand is empty — no cards to reveal.'),
                [
                    "card_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($target->ControllerId),
                ]);
        }

        if ($matched)
        {
            $game->notify->all("message",
                clienttranslate('${card_inject_code}: A revealed card has the <strong>${trait_name}</strong> Trait — ${target_inject_code} is wounded.'),
                [
                    "card_inject_code" => $owner->getInjectCode(),
                    "trait_name" => $this->chosenTrait,
                    "target_inject_code" => $target->getInjectCode(),
                ]);

            $wound = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($wound);
        }
        else
        {
            $game->notify->all("message",
                clienttranslate('${card_inject_code}: No revealed card has the <strong>${trait_name}</strong> Trait — no wound is inflicted.'),
                [
                    "card_inject_code" => $owner->getInjectCode(),
                    "trait_name" => $this->chosenTrait,
                ]);
        }
    }

    private function resetStage(): void
    {
        $this->stage = '';
        $this->chosenLetter = '';
        $this->chosenTrait = '';
    }

    /**
     * @return string[] sorted unique first letters of every trait in TraitNames
     */
    private function getLettersWithTraits(): array
    {
        $letters = [];
        foreach ($this->getAllTraits() as $trait)
        {
            $letter = strtoupper(mb_substr($trait, 0, 1));
            $letters[$letter] = true;
        }
        $letters = array_keys($letters);
        sort($letters);
        return $letters;
    }

    /**
     * @return string[] traits in TraitNames whose first letter (case-insensitive) is $letter
     */
    private function getTraitsStartingWith(string $letter): array
    {
        $letter = strtoupper($letter);
        $matched = [];
        foreach ($this->getAllTraits() as $trait)
        {
            if (strtoupper(mb_substr($trait, 0, 1)) === $letter)
            {
                $matched[] = $trait;
            }
        }
        return array_values($matched);
    }

    private function getAllTraits(): array
    {
        $data = json_decode(TraitNames::$TraitsJson, true);
        return $data['traits'] ?? [];
    }
}
