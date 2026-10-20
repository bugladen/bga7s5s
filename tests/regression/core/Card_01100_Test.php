<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01100;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01100;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class Card_01100_Test extends TestCase
{
    public function name(): string
    {
        return '_01100 The Cat\'s Glass';
    }

    public function tests(): array
    {
        return [
            'is a Castille FactionAttachment' => function () {
                $glass = new _01100();
                Assert::instanceOf(FactionAttachment::class, $glass, 'FactionAttachment');
                Assert::true($glass->hasFaction('Castille'), 'Castille');
            },

            'is a Unique Trinket' => function () {
                $glass = new _01100();
                Assert::true($glass->hasTrait('Trinket'), 'Trinket');
                Assert::true($glass->hasTrait('Unique'), 'Unique');
            },

            'grants +1 Finesse and no other stat modifier' => function () {
                $glass = new _01100();
                Assert::same(0, $glass->ResolveModifier, 'Resolve');
                Assert::same(0, $glass->CombatModifier, 'Combat');
                Assert::same(1, $glass->FinesseModifier, 'Finesse');
                Assert::same(0, $glass->InfluenceModifier, 'Influence');
            },

            // WHY: FactionAttachment cards must set Riposte (pre-commit rule); pin the full printed duel line too.
            'costs 2 Wealth with Riposte 2, dashed Parry 0 and Thrust 2' => function () {
                $glass = new _01100();
                Assert::same(2, $glass->WealthCost, 'wealth');
                Assert::same(2, $glass->Riposte, 'Riposte');
                Assert::same(0, $glass->Parry, 'Parry');
                Assert::true($glass->DashedParry, 'dashed Parry');
                Assert::same(2, $glass->Thrust, 'Thrust');
            },

            'has exactly one Reaction_01100 owned by the attachment' => function () {
                $world = new TestWorld();
                $glass = $world->placeCard(new _01100(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $glass->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01100::class, $glass->getReactions()[0], 'reaction type');
                Assert::same($glass->Id, $glass->getReactions()[0]->OwnerId, 'owner');
            },

            'starts unattached' => function () {
                $glass = new _01100();
                Assert::false($glass->isAttached(), 'not attached');
            },

            // WHY: getNumberOfGambleCardsToReveal hook - Card aggregates its Reactions, Theah sums every card on a base of 2.
            'activated glass lowers the adversary\'s gamble reveal count from 2 to 1 through Theah' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $glass = $world->placeCard(new _01100(), Game::LOCATION_CITY_DOCKS, 1);
                $glass->AttachedToId = $host->Id;
                $host->Attachments[] = $glass->Id;

                [$before] = $world->theah->getNumberOfGambleCardsToReveal($foe);
                Assert::same(2, $before, 'base reveal count');

                /** @var Reaction_01100 $reaction */
                $reaction = $glass->getReactions()[0];
                $reaction->IsActivated = true;
                $reaction->AdversaryId = $foe->Id;

                [$after, $explanations] = $world->theah->getNumberOfGambleCardsToReveal($foe);
                Assert::same(1, $after, 'one fewer card');
                Assert::contains($glass->getInjectCode(), $explanations, 'explained by the glass');
            },
        ];
    }
}
