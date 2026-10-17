<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02053;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02053_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02053';
    }

    /**
     * @return array{0:_02053,1:Reaction_02053,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _02053(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Night'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_02053 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $performer];
    }

    private function dusk(TestWorld $world): EventDuskPhaseBegin
    {
        $event = new EventDuskPhaseBegin();
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02053(), 'CardReaction');
            },

            'dusk begin offers every city character, including an engaged one' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $performer] = $this->scene($world);
                $engaged = $world->placeCharacter(new GenericCharacter('Engaged'), Game::LOCATION_CITY_FORUM, 1);
                $engaged->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                $reaction->handleEvent($this->dusk($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['performer-' . $performer->Id, 'performer-' . $engaged->Id, 'decline'], $ids, 'city only');
            },

            'does not offer away from home, with nobody in the city, or when used' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $performer] = $this->scene($world);
                $scheme->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($this->dusk($world));
                Assert::count(0, $world->theah->queuedEvents, 'not home');

                $scheme->Location = Game::LOCATION_PLAYER_HOME;
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->dusk($world));
                Assert::count(0, $world->theah->queuedEvents, 'no city character');

                $performer->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->Used = true;
                $reaction->handleEvent($this->dusk($world));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'choosing a performer marks them and opens one chooser per non-empty discard pile' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $performer] = $this->scene($world);
                $world->placeCard(new _02052(), 'Discard-1', 1);
                $world->placeCard(new _02052(), Game::LOCATION_CITY_DISCARD, 0);
                $reaction->handleEvent($this->dusk($world));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'performer-' . $performer->Id);

                Assert::true($performer->hasCondition(Game::UNDER_COVER_OF_THE_NIGHT), 'stays');
                Assert::true(in_array($performer->Id, $world->game->updatedCardObjectIds, true), 'written');
                Assert::true($reaction->Used, 'used immediately');
                $choosers = array_values(array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($event) => $event->transition === '02053'
                ));
                Assert::count(2, $choosers, 'player one and the city; player two is empty');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activated');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                unset($scheme);
            },

            // WHY: An unknown performer id still marks the reaction used. It does not add the condition.
            'an unknown performer still spends the reaction' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'performer-999');
                Assert::true($reaction->Used, 'used');
                Assert::false($performer->hasCondition(Game::UNDER_COVER_OF_THE_NIGHT), 'nobody marked');
                Assert::count(0, array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($event) => $event->transition === '02053'
                ), 'no piles');
            },

            'decline does not mark a performer or spend the reaction' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                Assert::false($reaction->Used, 'unused');
                Assert::false($performer->hasCondition(Game::UNDER_COVER_OF_THE_NIGHT), 'free to go home');
                Assert::count(0, $world->theah->queuedOfType(EventReactionActivated::class), 'not activated');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
