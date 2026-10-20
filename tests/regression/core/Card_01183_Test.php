<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01183;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class Card_01183_Test extends TestCase
{
    public function name(): string
    {
        return '_01183 So It Begins';
    }

    /** @return _01183 */
    private function atLocation(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): _01183
    {
        /** @var _01183 $card */
        $card = $world->placeCard(new _01183(), $location, 0);
        return $card;
    }

    private function combat(TestWorld $world, int $actorId, int $parry = 2): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        if ($parry > 0) {
            $event->addParry($parry);
        }
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Brawl Feud CityEventCard' => function () {
                $card = new _01183();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(7, $card->CityCardNumber, 'city #');
                Assert::true($card->hasTrait('Brawl'), 'Brawl');
                Assert::true($card->hasTrait('Feud'), 'Feud');
            },

            'getPressureStats appends Combat when absent at this location' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $types = [Game::STAT_INFLUENCE];

                $card->getPressureStats($world->theah, $performer, Game::LOCATION_CITY_DOCKS, $types);

                Assert::true(in_array(Game::STAT_COMBAT, $types, true), 'Combat added');
                Assert::same([Game::STAT_INFLUENCE, Game::STAT_COMBAT], $types, 'order');
            },

            // WHY: card text — "Pressures using Combat do not add Combat again."
            'getPressureStats does not double Combat when already present' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world);
                $types = [Game::STAT_COMBAT];

                $card->getPressureStats($world->theah, null, Game::LOCATION_CITY_DOCKS, $types);

                Assert::same([Game::STAT_COMBAT], $types, 'unchanged');
            },

            'getPressureStats ignores pressures at other locations' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world);
                $types = [Game::STAT_INFLUENCE];

                $card->getPressureStats($world->theah, null, Game::LOCATION_CITY_FORUM, $types);

                Assert::same([Game::STAT_INFLUENCE], $types, 'no Combat');
            },

            // WHY (production _01183.php): Maneuver column note (not Combat Card) so F5
            // keeps the -1 Parry label without a new table; skipped when Parry is dashed.
            'duel combat cards at this location lose 1 Parry' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_ID, 1);
                $world->game->globals->set(Game::DUEL_ROUND, 1);

                $event = $this->combat($world, $actor->Id, 2);
                $world->fireOn($card, $event);

                Assert::same(1, $event->parry, '-1 Parry');
                Assert::count(1, $event->explanations, 'explained');
                Assert::contains($card->getInjectCode(), $event->explanations[0], 'names card');
            },

            'duel combat cards elsewhere are unaffected' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_FORUM, 1);

                $event = $this->combat($world, $actor->Id, 2);
                $world->fireOn($card, $event);

                Assert::same(2, $event->parry, 'unchanged');
                Assert::count(0, $event->explanations, 'no explanation');
            },

            'dashed Parry still explains but skips the Maneuver column note path harmlessly' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_ID, 1);
                $world->game->globals->set(Game::DUEL_ROUND, 1);

                $event = $this->combat($world, $actor->Id, 0);
                $event->dashedParry = true;
                $prop = new \ReflectionProperty(EventDuelCalculateCombatCardStats::class, 'parry');
                $prop->setAccessible(true);
                $prop->setValue($event, 2);
                $world->fireOn($card, $event);

                // removeParry on dashed adds the dashed explanation instead of changing value.
                Assert::same(2, $event->parry, 'dashed: no numeric remove');
                Assert::true(count($event->explanations) >= 1, 'explained');
            },

            'no Parry reduction when the event card is not in the city' => function () {
                $world = new TestWorld();
                $card = $this->atLocation($world, Game::LOCATION_PLAYER_HOME);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_PLAYER_HOME, 1);

                $event = $this->combat($world, $actor->Id, 2);
                $world->fireOn($card, $event);

                Assert::same(2, $event->parry, 'not in city');
            },
        ];
    }
}
