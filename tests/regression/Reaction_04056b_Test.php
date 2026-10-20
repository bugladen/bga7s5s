<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04056b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04056b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04056b';
    }

    public function tests(): array
    {
        return [
            // WHY: En garde performer at the collected location. Plunder offers on Take,
            // because its Removed has playerId 0. An ability Collect offers on Removed
            // only when a gain is already queued, and then puts one Renown back because
            // the remove already applied. Use stacks pay. Pass does not spend.
            'collects one fewer from an en garde performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04056(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_04056b $reaction */
                $reaction = $risk->getReactions()[1];

                $take = new EventPlayerTakeReknownForControlledLocation();
                $take->playerId = 2;
                $take->location = Game::LOCATION_CITY_DOCKS;
                $take->reknown = 3;
                $world->fireOn($risk, $take);
                Assert::false($reaction->needsPutBack, 'plunder still queued');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::same(0, $reaction->originalAmount, 'cleared');
                $world->theah->takeQueuedEvents();

                $performer->Engaged = true;
                $world->fireOn($risk, $take);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged');
                $performer->Engaged = false;

                $removed = new EventRenownRemovedFromLocation();
                $removed->playerId = 0;
                $removed->location = Game::LOCATION_CITY_DOCKS;
                $removed->amount = 3;
                $world->fireOn($risk, $removed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'plunder removed is not the offer');

                $removed->playerId = 2;
                $world->fireOn($risk, $removed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'a move has no gain');

                $gain = new EventPlayerGainsReknown();
                $gain->playerId = 2;
                $gain->amount = 3;
                $world->theah->queueEvent($gain);
                $world->theah->queueEvent($removed);
                $world->fireOn($risk, $removed);
                Assert::true($reaction->needsPutBack, 'ability collect already removed');

                $reaction->performReaction($world->game, 0, '', 'use');
                Assert::instanceOf(EventEnteringPayState::class, $world->theah->queuedEvents[0], 'pay stacked first');

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $world->fireOn($risk, $triggered);
                Assert::true($reaction->Used, 'spent');
                Assert::same(2, $gain->amount, 'one fewer');
                Assert::same(1, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->amount, 'put one back');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $gain->amount = 2;
                $removed->amount = 2;
                $world->theah->queueEvent($gain);
                $world->theah->queueEvent($removed);
                $reaction->needsPutBack = false;
                $reaction->collectorPlayerId = 2;
                $reaction->location = Game::LOCATION_CITY_DOCKS;
                $reaction->originalAmount = 2;
                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $world->fireOn($risk, $triggered);
                Assert::same(1, $gain->amount, 'plunder gain reduced');
                Assert::same(1, $removed->amount, 'queued remove reduced');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no put-back');
            },
        ];
    }
}
