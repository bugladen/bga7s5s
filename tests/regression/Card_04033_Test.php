<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class Card_04033_Test extends TestCase
{
    public function name(): string
    {
        return '_04033 Iago Carlos de Soldano';
    }

    public function tests(): array
    {
        return [
            'is a razor academic duelist' => function () {
                $card = new _04033();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(3, $card->Finesse, 'finesse');
                Assert::true($card->hasTrait('Razor'), 'Razor');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::false($card->DuelTraitsApplied, 'not yet');
                Assert::count(1, $card->getTechniques(), 'technique');
            },

            // WHY: Pirate and Scoundrel exist only while he is a duel seat. Swap in
            // grants them; swap out and duel end remove only what this card granted.
            // Controller 0 never grants them.
            'gains pirate and scoundrel only while participating' => function () {
                $world = new TestWorld();
                $iago = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);

                $started = new EventDuelStarted();
                $started->challengerId = $other->Id;
                $started->defenderId = $other->Id;
                $world->fireOn($iago, $started);
                Assert::false($iago->hasTrait('Pirate'), 'spectator');

                $started->defenderId = $iago->Id;
                $world->fireOn($iago, $started);
                Assert::true($iago->hasTrait('Pirate'), 'pirate');
                Assert::true($iago->hasTrait('Scoundrel'), 'scoundrel');
                Assert::true($iago->DuelTraitsApplied, 'flag');

                $world->fireOn($iago, $started);
                Assert::same(1, count(array_filter($iago->ModifiedTraits, fn($trait) => $trait === 'Pirate')), 'once');

                $swap = new EventDefenderSwapped();
                $swap->oldDefenderId = $iago->Id;
                $swap->newDefenderId = $other->Id;
                $world->fireOn($iago, $swap);
                Assert::false($iago->hasTrait('Pirate'), 'left the seat');
                Assert::false($iago->DuelTraitsApplied, 'flag cleared');

                $back = new EventChallengerSwapped();
                $back->oldChallengerId = $other->Id;
                $back->newChallengerId = $iago->Id;
                $world->fireOn($iago, $back);
                Assert::true($iago->hasTrait('Scoundrel'), 'challenger seat');

                $end = new EventDuelEnd();
                $world->fireOn($iago, $end);
                Assert::false($iago->hasTrait('Scoundrel'), 'duel end');

                $iago->ControllerId = 0;
                $world->fireOn($iago, $started);
                Assert::false($iago->DuelTraitsApplied, 'uncontrolled');
            },
        ];
    }
}
