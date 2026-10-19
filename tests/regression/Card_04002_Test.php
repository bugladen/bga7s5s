<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04002;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class Card_04002_Test extends TestCase
{
    public function name(): string
    {
        return '_04002 Danilo Danini';
    }

    public function tests(): array
    {
        return [
            'is a Vodacce diplomat with a city action' => function () {
                $card = new _04002();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($card->hasTrait('Red Hand'), 'Red Hand');
                Assert::count(1, $card->getActions(), 'action');
            },

            // WHY: Aura is ±1 on the Thugs currently eligible (Home or Danilo's location).
            // Finesse is queued. Resolve is mutated immediately because there is no resolve event.
            // A resolve drop that crosses wounds destroys the Thug.
            'buffs your thugs at home or his location and strips them when they leave' => function () {
                $world = new TestWorld();
                $danilo = $world->placeCharacter(new _04002(), Game::LOCATION_CITY_DOCKS, 1);
                $home = $world->placeCharacter(new GenericCharacter('Home Thug', ['Thug']), Game::LOCATION_PLAYER_HOME, 1);
                $here = $world->placeCharacter(new GenericCharacter('Here Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
                $away = $world->placeCharacter(new GenericCharacter('Away Thug', ['Thug']), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 2);
                $spark = $world->placeCharacter(new GenericCharacter('Spark'), Game::LOCATION_CITY_DOCKS, 1);

                $recruited = new EventCharacterRecruited();
                $recruited->playerId = 1;
                $recruited->characterId = $spark->Id;
                $world->fireOn($danilo, $recruited);

                Assert::equals([$home->Id, $here->Id], $danilo->BuffedThugIds, 'home and here');
                Assert::false(in_array($away->Id, $danilo->BuffedThugIds, true), 'elsewhere');
                Assert::false(in_array($foe->Id, $danilo->BuffedThugIds, true), 'opponent');
                Assert::same(1, $home->ModifiedFinesse, 'finesse waits for the hub');
                $finesse = $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0];
                Assert::same($home->Id, $finesse->CharacterId, 'home thug');
                Assert::same(2, $finesse->NewFinesse, 'plus one');
                Assert::same(4, $home->ModifiedResolve, 'resolve mutated now');

                $world->theah->takeQueuedEvents();
                $here->Wounds = 4;
                $leave = new EventCardMoved();
                $leave->cardId = $here->Id;
                $leave->fromLocation = Game::LOCATION_CITY_DOCKS;
                $leave->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($danilo, $leave);
                Assert::false(in_array($here->Id, $danilo->BuffedThugIds, true), 'stripped');
                Assert::same(3, $here->ModifiedResolve, 'resolve removed');
                Assert::true($here->IsDying, 'wounds crossed resolve');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterDestroyed::class), 'destroyed');
            },

            // WHY: handleEvent returns immediately when ControllerId is 0, so the approach
            // override never runs for an uncontrolled Danilo. Blanking still clears stamps.
            'an uncontrolled approach is ignored, and blanking clears the aura' => function () {
                $world = new TestWorld();
                $danilo = $world->placeCharacter(new _04002(), Game::LOCATION_PLAYER_HOME, 0);
                $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_PLAYER_HOME, 1);
                $approach = new EventApproachCharacterPlayed();
                $approach->characterId = $danilo->Id;
                $approach->playerId = 1;
                $world->fireOn($danilo, $approach);
                Assert::same([], $danilo->BuffedThugIds, 'early return');

                $world->theah->takeQueuedEvents();
                $danilo->ControllerId = 1;
                $spark = $world->placeCharacter(new GenericCharacter('Spark', ['Thug']), Game::LOCATION_PLAYER_HOME, 1);
                $recruited = new EventCharacterRecruited();
                $recruited->playerId = 1;
                $recruited->characterId = $spark->Id;
                $world->fireOn($danilo, $recruited);
                Assert::true(in_array($spark->Id, $danilo->BuffedThugIds, true), 'buffed once controlled');
                Assert::same(4, $spark->ModifiedResolve, 'resolve up');

                $danilo->onAbilitiesBlanked($world->theah);
                Assert::same([], $danilo->BuffedThugIds, 'cleared');
                Assert::same(3, $spark->ModifiedResolve, 'resolve restored');
            },
        ];
    }
}
