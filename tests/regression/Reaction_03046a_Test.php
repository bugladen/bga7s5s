<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03046a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03046a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03046a';
    }

    public function tests(): array
    {
        return [
            // WHY: The intervening player owns the Risk, and the intervener is the Duelist.
            // Engarde only queues when they are actually engaged. An unengaged intervener
            // still spends the Risk. Pass forgets them.
            'en gardes your intervening Duelist' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03046(), Game::LOCATION_HAND, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $duelist->Engaged = true;
                /** @var Reaction_03046a $reaction */
                $reaction = $risk->getReactions()[0];
                $intervened = new EventCharacterIntervened();
                $intervened->theah = $world->theah;
                $intervened->playerId = 1;
                $intervened->newTargetId = $duelist->Id;
                $reaction->handleEvent($intervened);
                Assert::same(['use', 'pass'], array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'), 'play or pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');
                Assert::false($reaction->Used, 'not until it resolves');

                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::same($duelist->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'en garde');
                Assert::true($reaction->Used, 'spent');

                $reaction->Used = false;
                $duelist->Engaged = false;
                $reaction->handleEvent($intervened);
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($triggered);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'already ready');
                Assert::true($reaction->Used, 'still spent');
            },

            'pass, a non-Duelist, and someone else\'s intervene do not en garde' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03046(), Game::LOCATION_HAND, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03046a $reaction */
                $reaction = $risk->getReactions()[0];
                $intervened = new EventCharacterIntervened();
                $intervened->theah = $world->theah;
                $intervened->playerId = 1;
                $intervened->newTargetId = $hero->Id;
                $reaction->handleEvent($intervened);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a duelist');

                $hero->addTrait($world->game, 'Duelist', true);
                $intervened->playerId = 2;
                $reaction->handleEvent($intervened);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not your intervene');

                $intervened->playerId = 1;
                $reaction->handleEvent($intervened);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'pass does not pay');
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::false($reaction->Used, 'forgotten performer is not spent');

                $risk->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($intervened);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not in hand');
            },
        ];
    }
}
