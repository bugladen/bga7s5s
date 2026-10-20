<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

class Card_02044_Test extends TestCase
{
    public function name(): string
    {
        return '_02044 Solomonia Saboruvya';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura diplomat with no Combat' => function () {
                $solomonia = new _02044();
                Assert::instanceOf(Character::class, $solomonia, 'Character');
                Assert::same('The Iron Will', $solomonia->Title, 'title');
                Assert::same(3, $solomonia->Resolve, 'Resolve');
                Assert::same(0, $solomonia->Combat, 'Combat');
                Assert::same(3, $solomonia->Finesse, 'Finesse');
                Assert::same(2, $solomonia->Influence, 'Influence');
                Assert::true($solomonia->hasFaction('Ussura'), 'Ussura');
                Assert::true($solomonia->hasTrait('Aristocrat'), 'Aristocrat');
                Assert::true($solomonia->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($solomonia->hasTrait('Noble'), 'Noble');
                Assert::true($solomonia->hasTrait('Ussura'), 'trait');
            },

            // WHY: The block is the Forums being uncontrolled, not Solomonia's own location.
            'cannot be challenged while the Forums are uncontrolled' => function () {
                $world = new TestWorld();
                $solomonia = $world->placeCharacter(new _02044(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventChallengeIssued();
                $event->defenderId = $solomonia->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $solomonia->eventCheck($event);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'can be challenged once the Forums are controlled' => function () {
                $world = new TestWorld();
                $solomonia = $world->placeCharacter(new _02044(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);

                $event = new EventChallengeIssued();
                $event->defenderId = $solomonia->Id;
                $event->theah = $world->theah;
                $solomonia->eventCheck($event);

                Assert::true(true, 'allowed');
            },

            'a challenge against someone else is not blocked' => function () {
                $world = new TestWorld();
                $solomonia = $world->placeCharacter(new _02044(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventChallengeIssued();
                $event->defenderId = $other->Id;
                $event->theah = $world->theah;
                $solomonia->eventCheck($event);

                Assert::true(true, 'allowed');
            },

            // WHY: setGlobalFlag ORs the bit. The bonus is Influence pressure at a location adjacent
            // to the Forums, and only while Solomonia herself is at the Forums.
            'Influence pressure adjacent to the Forums gains her flag' => function () {
                $world = new TestWorld();
                $solomonia = $world->placeCharacter(new _02044(), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::CAPTAINS_COAT_PRESSURE_TYPE);

                $event = new EventPressureOccuring();
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->pressureTypes = [Game::STAT_INFLUENCE];
                $event->theah = $world->theah;
                $solomonia->handleEvent($event);

                Assert::same(
                    Game::CAPTAINS_COAT_PRESSURE_TYPE | Game::SOLOMONIA_PRESSURE_TYPE,
                    $world->game->globals->get(Game::PRESSURE_TYPE),
                    'or'
                );
                Assert::same($solomonia->Id, $world->game->globals->get(Game::SOLOMONIA_ID), 'id');
            },

            'bazaar is adjacent; the Forums themselves, home, and Ole\'s Inn are not' => function () {
                $world = new TestWorld();
                $solomonia = $world->placeCharacter(new _02044(), Game::LOCATION_CITY_FORUM, 1);

                foreach ([Game::LOCATION_CITY_FORUM, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_OLES_INN] as $location) {
                    $event = new EventPressureOccuring();
                    $event->location = $location;
                    $event->pressureTypes = [Game::STAT_INFLUENCE];
                    $event->theah = $world->theah;
                    $solomonia->handleEvent($event);
                }
                Assert::same(0, (int) $world->game->globals->get(Game::PRESSURE_TYPE, 0), 'not those');

                $bazaar = new EventPressureOccuring();
                $bazaar->location = Game::LOCATION_CITY_BAZAAR;
                $bazaar->pressureTypes = [Game::STAT_INFLUENCE];
                $bazaar->theah = $world->theah;
                $solomonia->handleEvent($bazaar);
                Assert::same(Game::SOLOMONIA_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'bazaar');
            },

            'no bonus off the Forums, for Combat pressure, or while she is uncontrolled' => function () {
                $world = new TestWorld();
                $solomonia = $world->placeCharacter(new _02044(), Game::LOCATION_CITY_DOCKS, 1);

                $off = new EventPressureOccuring();
                $off->location = Game::LOCATION_CITY_BAZAAR;
                $off->pressureTypes = [Game::STAT_INFLUENCE];
                $off->theah = $world->theah;
                $solomonia->handleEvent($off);

                $solomonia->Location = Game::LOCATION_CITY_FORUM;
                $combat = new EventPressureOccuring();
                $combat->location = Game::LOCATION_CITY_DOCKS;
                $combat->pressureTypes = [Game::STAT_COMBAT];
                $combat->theah = $world->theah;
                $solomonia->handleEvent($combat);

                $solomonia->ControllerId = 0;
                $uncontrolled = new EventPressureOccuring();
                $uncontrolled->location = Game::LOCATION_CITY_DOCKS;
                $uncontrolled->pressureTypes = [Game::STAT_INFLUENCE];
                $uncontrolled->theah = $world->theah;
                $solomonia->handleEvent($uncontrolled);

                Assert::same(0, (int) $world->game->globals->get(Game::PRESSURE_TYPE, 0), 'none');
                Assert::same(null, $world->game->globals->get(Game::SOLOMONIA_ID), 'no id');
            },
        ];
    }
}
