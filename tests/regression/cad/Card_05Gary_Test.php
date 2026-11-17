<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Gary;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class Card_05Gary_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Gary';
    }

    public function tests(): array
    {
        return [
            // WHY: Forced text is "Draw two cards, then Flip her" at 4+ wounds.
            // Draws are queued; flip is same-Id A→B swap (Timebomb contrast is Character→Attachment).
            'four wounds queues two draws and flips to wrathful' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _05Gary(), Game::LOCATION_CITY_DOCKS, 1);
                $rena->Wounds = 3;

                $wound = new EventCharacterWounded();
                $wound->characterId = $rena->Id;
                $wound->wounds = 1;
                $wound->reason = 'test';
                $world->fireOn($rena, $wound);

                Assert::same(4, $rena->Wounds, 'crossed threshold');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(2, $draws, 'two draws');
                Assert::same(1, $draws[0]->playerId, 'controller draws');
                Assert::same(1, $draws[1]->playerId, 'controller draws again');

                $flipped = $world->theah->getCardById($rena->Id);
                Assert::instanceOf(_05GaryFlipped::class, $flipped, 'flipped');
                Assert::same(4, $flipped->Wounds, 'wounds retained');
                Assert::same($rena->Id, $flipped->Id, 'same id');
            },

            'three wounds does not flip or draw' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _05Gary(), Game::LOCATION_CITY_DOCKS, 1);
                $rena->Wounds = 2;

                $wound = new EventCharacterWounded();
                $wound->characterId = $rena->Id;
                $wound->wounds = 1;
                $wound->reason = 'test';
                $world->fireOn($rena, $wound);

                Assert::same(3, $rena->Wounds, 'below threshold');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draws');
                Assert::instanceOf(_05Gary::class, $world->theah->getCardById($rena->Id), 'still A-side');
            },
        ];
    }
}
