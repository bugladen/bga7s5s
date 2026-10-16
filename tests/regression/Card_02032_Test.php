<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseDawnBeginning;

class Card_02032_Test extends TestCase
{
    public function name(): string
    {
        return '_02032 Lucas Martinez “Damned”';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Monster with dashed Influence and a Reaction' => function () {
                $lucas = new _02032();
                Assert::instanceOf(Character::class, $lucas, 'Character');
                Assert::instanceOf(IHasReactions::class, $lucas, 'reactions');
                Assert::same(3, $lucas->Resolve, 'Resolve');
                Assert::same(3, $lucas->Combat, 'Combat');
                Assert::same(1, $lucas->Finesse, 'Finesse');
                Assert::same(0, $lucas->Influence, 'Influence');
                Assert::true($lucas->DashedInfluence, 'dashed Influence');
                Assert::true($lucas->hasFaction('Castille'), 'Castille');
                Assert::true($lucas->hasTrait('Monster'), 'Monster');
                Assert::true($lucas->hasTrait('Undead'), 'Undead');
                Assert::true($lucas->hasTrait('Pirate'), 'Pirate');
                Assert::count(1, $lucas->getReactions(), 'one');
                Assert::instanceOf(Reaction_02032::class, $lucas->getReactions()[0], 'reaction');
            },

            // WHY: Printed Forced — Damned cannot enter an Approach deck.
            'cannot enter an Approach deck' => function () {
                Assert::false((new _02032())->canEnterApproachDeck(), 'approach');
            },

            'owns the reaction when placed' => function () {
                $world = new TestWorld();
                $lucas = $world->placeCharacter(new _02032(), 'Locker-1', 1);
                Assert::same($lucas->Id, $lucas->getReactions()[0]->OwnerId, 'owner');
            },

            // WHY: Beginning of Dawn destroys Lucas, reason Dawn Phase.
            'Dawn beginning queues destroy' => function () {
                $world = new TestWorld();
                $lucas = $world->placeCharacter(new _02032(), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($lucas, new EventPhaseDawnBeginning());

                $destroyed = $world->theah->queuedOfType(EventCharacterDestroyed::class);
                Assert::count(1, $destroyed, 'destroy');
                Assert::same($lucas->Id, $destroyed[0]->characterId, 'self');
                Assert::same(1, $destroyed[0]->playerId, 'controller');
                Assert::same('Dawn Phase', $destroyed[0]->reason, 'reason');
            },
        ];
    }
}
