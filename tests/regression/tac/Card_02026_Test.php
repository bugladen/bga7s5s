<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02026a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02026b;

class Card_02026_Test extends TestCase
{
    public function name(): string
    {
        return '_02026 Croc de Lion';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Offhand FactionAttachment with both Techniques' => function () {
                $croc = new _02026();
                Assert::instanceOf(FactionAttachment::class, $croc, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $croc, 'techniques');
                Assert::same(1, $croc->WealthCost, 'Wealth');
                Assert::same(1, $croc->FinesseModifier, '+1 Finesse');
                Assert::same(1, $croc->Riposte, 'Riposte');
                Assert::same(1, $croc->Parry, 'Parry');
                Assert::same(2, $croc->Thrust, 'Thrust');
                Assert::true($croc->OffHand, 'OffHand');
                Assert::true($croc->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($croc->hasTrait('Weapon'), 'Weapon');
                Assert::true($croc->hasTrait('Melee'), 'Melee');
                Assert::true($croc->hasTrait('Dagger'), 'Dagger');
                Assert::true($croc->hasTrait('Unique'), 'Unique');
                Assert::count(2, $croc->getTechniques(), 'two techniques');
                Assert::instanceOf(Technique_02026a::class, $croc->getTechniques()[0], 'a');
                Assert::instanceOf(Technique_02026b::class, $croc->getTechniques()[1], 'b');
            },

            'owns techniques when placed' => function () {
                $world = new TestWorld();
                $croc = $world->placeCard(new _02026(), Game::LOCATION_HAND, 1);
                Assert::same($croc->Id, $croc->getTechniques()[0]->OwnerId, 'a owner');
                Assert::same($croc->Id, $croc->getTechniques()[1]->OwnerId, 'b owner');
            },

            'state constants registered' => function () {
                Assert::same(521020261, States::DUEL_CHOOSE_TECHNIQUE_02026a, 'duel a');
                Assert::same(521020262, States::DUEL_CHOOSE_TECHNIQUE_02026b, 'duel b');
                Assert::same(455020261, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_02026a, 'challenge a');
                Assert::same(455020262, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_02026b, 'challenge b');
            },
        ];
    }
}
