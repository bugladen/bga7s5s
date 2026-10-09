<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01101;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01101;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class Card_01101_Test extends TestCase
{
    public function name(): string
    {
        return '_01101 Gallegos Blade';
    }

    public function tests(): array
    {
        return [
            'is a Castille FactionAttachment Weapon' => function () {
                $blade = new _01101();
                Assert::instanceOf(FactionAttachment::class, $blade, 'FactionAttachment');
                Assert::true($blade->hasFaction('Castille'), 'Castille');
                Assert::true($blade->hasTrait('Weapon'), 'Weapon');
                Assert::true($blade->hasTrait('Melee'), 'Melee');
                Assert::true($blade->hasTrait('Sword'), 'Sword');
                Assert::true($blade->hasTrait('Aldana'), 'Aldana');
            },

            // WHY: FactionAttachment pre-commit requires Riposte set; pin full printed duel line.
            'costs 0 Wealth with dashed Riposte 0, Parry 1, Thrust 4' => function () {
                $blade = new _01101();
                Assert::same(0, $blade->WealthCost, 'wealth');
                Assert::same(0, $blade->Riposte, 'Riposte');
                Assert::true($blade->DashedRiposte, 'dashed Riposte');
                Assert::same(1, $blade->Parry, 'Parry');
                Assert::same(4, $blade->Thrust, 'Thrust');
                Assert::same(0, $blade->ResolveModifier, 'Resolve');
                Assert::same(0, $blade->CombatModifier, 'Combat');
                Assert::same(0, $blade->FinesseModifier, 'Finesse');
                Assert::same(0, $blade->InfluenceModifier, 'Influence');
            },

            'has exactly one Technique_01101 owned by the blade' => function () {
                $world = new TestWorld();
                $blade = $world->placeCard(new _01101(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $blade->getTechniques(), 'one technique');
                Assert::instanceOf(Technique_01101::class, $blade->getTechniques()[0], 'technique type');
                Assert::same($blade->Id, $blade->getTechniques()[0]->OwnerId, 'owner');
            },

            'starts unattached' => function () {
                Assert::false((new _01101())->isAttached(), 'not attached');
            },

            // WHY: Passive — when the equipped character gambles, reveal +1 (base 2 → 3).
            'attached blade adds +1 gamble reveal for the host only' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $blade = $world->placeCard(new _01101(), Game::LOCATION_CITY_DOCKS, 1);
                $blade->AttachedToId = $host->Id;
                $host->Attachments[] = $blade->Id;

                [$hostReveal, $hostWhy] = $world->theah->getNumberOfGambleCardsToReveal($host);
                Assert::same(3, $hostReveal, 'host +1');
                Assert::contains($blade->getInjectCode(), $hostWhy, 'explained');

                [$foeReveal] = $world->theah->getNumberOfGambleCardsToReveal($foe);
                Assert::same(2, $foeReveal, 'foe unchanged');
            },
        ];
    }
}
