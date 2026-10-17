<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd10;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd10;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03cd10_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03cd10';
    }

    /**
     * @return array{0:_03cd10,1:Reaction_03cd10,2:GenericCharacter}
     */
    private function juliusWithFoe(TestWorld $world, string $juliusLocation = Game::LOCATION_CITY_DOCKS): array
    {
        $julius = $world->placeCharacter(new _03cd10(), $juliusLocation, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03cd10 $reaction */
        $reaction = $julius->getReactions()[0];
        return [$julius, $reaction, $foe];
    }

    /** @param list<array{text:string,reaction:string}> $buttons */
    private function reactionIds(array $buttons): array
    {
        return array_map(static fn(array $button): string => $button['reaction'], $buttons);
    }

    public function tests(): array
    {
        return [
            // WHY: Opposing means a controlled character with a different controller at
            // the destination. Uncontrolled neighbors do not open the reaction.
            // EventCardMoved has not written Location yet, so the gate reads toLocation.
            'recruiting Julius at a city location with an opponent offers the reaction' => function () {
                $world = new TestWorld();
                [$julius, $reaction] = $this->juliusWithFoe($world);

                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $julius->Id;
                $recruited->theah = $world->theah;
                $reaction->handleEvent($recruited);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'offered');
                Assert::same('reaction', $transition[0]->transition, 'reaction');
                Assert::same(1, $transition[0]->playerId, 'controller');
                Assert::same($julius->Id, $transition[0]->sourceId, 'julius');
                Assert::same($reaction->Id, $transition[0]->internalId, 'reaction id');
                Assert::true($julius->IsUpdated, 'stage persisted');
                Assert::contains('first letter', $reaction->getReactionDescription($world->theah), 'letter prompt');
            },

            'recruiting someone else, or Julius with no opposing character, does not offer' => function () {
                $world = new TestWorld();
                [$julius, $reaction, $foe] = $this->juliusWithFoe($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 0);

                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $other->Id;
                $recruited->theah = $world->theah;
                $reaction->handleEvent($recruited);
                Assert::count(0, $world->theah->queuedEvents, 'not julius');

                $foe->ControllerId = 1;
                $recruited->characterId = $julius->Id;
                $reaction->handleEvent($recruited);
                Assert::count(0, $world->theah->queuedEvents, 'friend and the uncontrolled character do not count');
            },

            'a move offers from the destination, not his current location' => function () {
                $world = new TestWorld();
                [$julius, $reaction] = $this->juliusWithFoe($world, Game::LOCATION_CITY_FORUM);
                $world->placeCharacter(new GenericCharacter('ForumFoe'), Game::LOCATION_CITY_FORUM, 2);

                $moved = new EventCardMoved();
                $moved->cardId = $julius->Id;
                $moved->toLocation = Game::LOCATION_CITY_DOCKS;
                $moved->theah = $world->theah;
                $reaction->handleEvent($moved);
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'docks has a foe');

                $world->theah->queuedEvents = [];
                $reaction->setUsed($world->theah, false);
                $moved->toLocation = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($moved);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'home is not a city location');
            },

            'an uncontrolled Julius moving does not offer' => function () {
                $world = new TestWorld();
                [$julius, $reaction] = $this->juliusWithFoe($world);
                $julius->ControllerId = 0;

                $moved = new EventCardMoved();
                $moved->cardId = $julius->Id;
                $moved->toLocation = Game::LOCATION_CITY_DOCKS;
                $moved->theah = $world->theah;
                $reaction->handleEvent($moved);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'a used reaction does not offer again' => function () {
                $world = new TestWorld();
                [$julius, $reaction] = $this->juliusWithFoe($world);
                $reaction->setUsed($world->theah, true);
                $world->theah->queuedEvents = [];

                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $julius->Id;
                $recruited->theah = $world->theah;
                $reaction->handleEvent($recruited);

                Assert::count(0, $world->theah->queuedEvents, 'spent');
            },

            // WHY: Letters come from TraitNames. Naming Academic and hitting a hand card
            // with that trait wounds. The reveal is the whole hand when it has 1–2 cards,
            // so this does not depend on array_rand.
            'naming a trait that a revealed card has wounds the target' => function () {
                $world = new TestWorld();
                [$julius, $reaction, $foe] = $this->juliusWithFoe($world);
                $world->placeCard(new GenericCharacter('Secret', ['Academic']), Game::LOCATION_HAND, 2);
                $world->placeCard(new GenericCharacter('Other', ['Bard']), Game::LOCATION_HAND, 2);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'letter-A');
                $traitButtons = $reaction->getReactionButtonProperties($world->theah);
                $traitId = null;
                foreach ($traitButtons as $button) {
                    if ($button['text'] === 'Academic') {
                        $traitId = $button['reaction'];
                    }
                }
                Assert::true($traitId !== null, 'Academic is offered');
                Assert::contains('"A"', $reaction->getReactionDescription($world->theah), 'letter prompt');

                $reaction->performReaction($world->game, 0, $reaction->Id, $traitId);
                $targetButtons = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('target-' . $foe->Id, $targetButtons, true), 'foe is a target');
                Assert::true(in_array('back', $targetButtons, true), 'back');
                Assert::contains('Academic', $reaction->getReactionDescription($world->theah), 'named trait');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'target-' . $foe->Id);

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wound, 'wounded');
                Assert::same($foe->Id, $wound[0]->characterId, 'foe');
                Assert::same($julius->Id, $wound[0]->sourceId, 'julius');
                Assert::same($reaction->Id, $wound[0]->abilityId, 'reaction');
                Assert::true($reaction->Used, 'spent');
                Assert::same(['done', 'done', 'done'], $world->game->gamestate->transitions, 'each step done');
            },

            'an empty hand or a miss does not wound' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->juliusWithFoe($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'letter-A');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'trait-0');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'target-' . $foe->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'empty hand');

                $world = new TestWorld();
                [, $reaction, $foe] = $this->juliusWithFoe($world);
                $world->placeCard(new GenericCharacter('Secret', ['Bard']), Game::LOCATION_HAND, 2);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'letter-A');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'trait-0');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'target-' . $foe->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no Academic');
                Assert::true($reaction->Used, 'still spent');
            },

            'a missing target spends the reaction without a wound' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->juliusWithFoe($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'letter-A');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'trait-0');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'target-99999');

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::true($reaction->Used, 'spent');
            },

            // WHY: Back is only a stage change once the offer has set stage to letter.
            // From the letter step it requeues and does not spend. Decline does.
            'decline spends it; back on the first step does not' => function () {
                $world = new TestWorld();
                [$julius, $reaction] = $this->juliusWithFoe($world);
                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $julius->Id;
                $recruited->theah = $world->theah;
                $reaction->handleEvent($recruited);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'back');
                Assert::false($reaction->Used, 'back is not a decline');
                Assert::contains('first letter', $reaction->getReactionDescription($world->theah), 'still on letters');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                Assert::true($reaction->Used, 'declined');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'back from the target step returns to the trait list' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->juliusWithFoe($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'letter-A');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'trait-0');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'back');

                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('trait-0', $ids, true), 'traits again');
                Assert::false($reaction->Used, 'not spent');
            },

            'an unrecognized id just ends the state' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->juliusWithFoe($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'nope');
                Assert::false($reaction->Used, 'not spent');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
