<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04026_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04026';
    }

    public function tests(): array
    {
        return [
            // WHY: Interrupt another player's pressure that succeeded by 1 or less, at
            // the bearer's location, while Pompon is en garde. Fail replaces the result
            // and spends the reaction. Your own success is not interrupted.
            'fails a close opposing pressure by engaging' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Envoy', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $pompon = $world->placeCard(new _04026(), Game::LOCATION_CITY_DOCKS, 1);
                $pompon->AttachedToId = $host->Id;
                /** @var Reaction_04026 $reaction */
                $reaction = $pompon->getReactions()[0];

                $existing = new EventLocationPressureResult();
                $existing->success = true;
                $world->theah->queueEvent($existing);

                $pressure = new EventLocationPressured();
                $pressure->success = true;
                $pressure->difference = 1;
                $pressure->playerId = 2;
                $pressure->performerId = 50;
                $pressure->location = Game::LOCATION_CITY_DOCKS;
                $pressure->pressureType = 'test';
                $pressure->totalsExplanation = 'close';
                $pressure->theah = $world->theah;
                $reaction->handleEvent($pressure);
                $offer = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('reaction', $offer->transition, 'offered');
                Assert::same(Event::HIGH_PRIORITY, $offer->priority, 'interrupts the result');

                $reaction->performReaction($world->game, 0, '', 'failPressure');
                Assert::true($reaction->Used, 'spent');
                Assert::same($pompon->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                $failed = $world->theah->queuedOfType(EventLocationPressureResult::class);
                Assert::count(1, $failed, 'old result deleted');
                Assert::false($failed[0]->success, 'failed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $failed[0]->location, 'same location');
                Assert::same(2, $failed[0]->playerId, 'original pressurer');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');

                $pressure->difference = 2;
                $reaction->handleEvent($pressure);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'difference above 1');

                $pressure->difference = 1;
                $pressure->playerId = 1;
                $reaction->handleEvent($pressure);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'your own pressure');

                $pressure->playerId = 2;
                $pompon->Engaged = true;
                $reaction->handleEvent($pressure);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'already engaged');
            },
        ];
    }
}
