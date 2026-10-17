<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03015;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;

class Card_03015_Test extends TestCase
{
    public function name(): string
    {
        return '_03015 Joern Keitelsson';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Villain with 8 Resolve' => function () {
                $joern = new _03015();
                Assert::instanceOf(Character::class, $joern, 'character');
                Assert::same(8, $joern->Resolve, 'resolve');
                Assert::same(3, $joern->Combat, 'combat');
                Assert::same(2, $joern->Finesse, 'finesse');
                Assert::same(1, $joern->Influence, 'influence');
                Assert::true($joern->hasFaction('Eisen'), 'Eisen');
                Assert::true($joern->hasTrait('Berserker'), 'Berserker');
                Assert::true($joern->hasTrait('Villain'), 'Villain');
                Assert::same(8, $joern->ModifiedResolve, 'modified starts at printed');
            },

            // WHY: The text box is blanked as a whole. Muster wound, dusk Resolve, and
            // the refuse heal all live in handleEvent, so Fate's Silence skips them.
            'mustering wounds him, a refused challenge heals him, and blanking skips both' => function () {
                $world = new TestWorld();
                $joern = $world->placeCharacter(new _03015(), Game::LOCATION_PLAYER_HOME, 1);
                $mustered = new EventCharacterMustered();
                $mustered->theah = $world->theah;
                $mustered->characterId = $joern->Id;
                $joern->handleEvent($mustered);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($joern->Id, $wound->characterId, 'wounded');
                Assert::same(1, $wound->wounds, 'one');

                $world->theah->takeQueuedEvents();
                $played = new EventApproachCharacterPlayed();
                $played->theah = $world->theah;
                $played->characterId = $joern->Id;
                $played->playerId = 1;
                $joern->handleEvent($played);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'approach play also wounds');

                $world->theah->takeQueuedEvents();
                $refused = new EventChallengeRejected();
                $refused->theah = $world->theah;
                $refused->challengerId = $joern->Id;
                $joern->handleEvent($refused);
                Assert::same($joern->Id, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->characterId, 'heals');

                $world->theah->takeQueuedEvents();
                $refused->challengerId = 999;
                $joern->handleEvent($refused);
                Assert::count(0, $world->theah->queuedEvents, 'someone else refused');

                $joern->addCondition(Game::FATES_SILENCE_CONDITION);
                $joern->handleEvent($mustered);
                Assert::count(0, $world->theah->queuedEvents, 'blanked');
            },

            // WHY: Lowering Resolve does not itself run the destruction check, so Dusk
            // queues the destroy when wounds already meet the reduced Resolve.
            // Blanking restores the 3. Unblanking during Dusk applies it again.
            'dusk lowers Resolve by 3 and can destroy him' => function () {
                $world = new TestWorld();
                $joern = $world->placeCharacter(new _03015(), Game::LOCATION_CITY_DOCKS, 1);
                $joern->Wounds = 5;
                $begin = new EventDuskPhaseBegin();
                $begin->theah = $world->theah;
                $joern->handleEvent($begin);
                Assert::same(5, $joern->ModifiedResolve, '8 - 3');
                Assert::true($joern->IsDying, 'wounds meet resolve');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterDestroyed::class), 'destroyed');

                $joern->onAbilitiesBlanked($world->theah);
                Assert::same(8, $joern->ModifiedResolve, 'blank restores the penalty');

                $world = new TestWorld();
                $joern = $world->placeCharacter(new _03015(), Game::LOCATION_CITY_DOCKS, 0);
                $begin->theah = $world->theah;
                $joern->handleEvent($begin);
                Assert::same(8, $joern->ModifiedResolve, 'uncontrolled is not in dusk');

                $world = new TestWorld();
                $joern = $world->placeCharacter(new _03015(), Game::LOCATION_CITY_DOCKS, 1);
                $begin->theah = $world->theah;
                $joern->handleEvent($begin);
                $end = new EventDuskEndOfDay();
                $end->theah = $world->theah;
                $joern->handleEvent($end);
                Assert::same(8, $joern->ModifiedResolve, 'end of day restores');

                $world->game->setGameStateValue(Game::TURN_PHASE, Game::DUSK);
                $joern->Wounds = 6;
                $joern->onAbilitiesUnblanked($world->theah);
                Assert::same(5, $joern->ModifiedResolve, 'unblank during dusk');
                Assert::true($joern->IsDying, 'unblank can destroy');
            },
        ];
    }
}
