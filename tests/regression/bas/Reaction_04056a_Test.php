<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04056a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04056a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04056a';
    }

    public function tests(): array
    {
        return [
            // WHY: Any controlled character at either end of the move, never Home.
            // The offer is stacked so it runs before the add/remove. Cancel pays first.
            // The triggered reaction deletes that batch. Decline does not spend.
            'cancels renown moving to or from your performer' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04056(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_04056a $reaction */
                $reaction = $risk->getReactions()[0];

                $moving = new EventRenownMovingBetweenLocations();
                $moving->fromLocation = Game::LOCATION_CITY_FORUM;
                $moving->toLocation = Game::LOCATION_CITY_DOCKS;
                $moving->amount = 2;
                $moving->batchId = 9;
                $world->fireOn($risk, $moving);
                Assert::same($performer->Id, $reaction->performerId, 'destination performer');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'stacked offer');

                $reaction->performReaction($world->game, 0, '', 'decline');
                Assert::false($reaction->Used, 'decline');
                Assert::same(0, $reaction->batchId, 'cleared');

                $world->fireOn($risk, $moving);
                $reaction->performReaction($world->game, 0, '', 'cancel');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedEvents[0]->payStateType ?? null, 'pay is stacked first');
                Assert::instanceOf(EventEnteringPayState::class, $world->theah->queuedEvents[0], 'pay event');

                $added = new EventRenownAddedToLocation();
                $added->batchId = 9;
                $added->location = Game::LOCATION_CITY_DOCKS;
                $removed = new EventRenownRemovedFromLocation();
                $removed->batchId = 9;
                $removed->location = Game::LOCATION_CITY_FORUM;
                $other = new EventRenownAddedToLocation();
                $other->batchId = 4;
                $world->theah->queueEvent($added);
                $world->theah->queueEvent($removed);
                $world->theah->queueEvent($other);

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $world->fireOn($risk, $triggered);
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'removed deleted');
                $left = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $left, 'other batch stays');
                Assert::same(4, $left[0]->batchId, 'different batch');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $moving->amount = 0;
                $world->fireOn($risk, $moving);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'nothing moved');

                $moving->amount = 1;
                $moving->fromLocation = Game::LOCATION_PLAYER_HOME;
                $moving->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($risk, $moving);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'home is not a performer location');

                $risk->Location = Game::LOCATION_DUELING_LINE;
                $moving->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($risk, $moving);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'left the hand');
            },
        ];
    }
}
