<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01092;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01092;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01092_Test extends TestCase
{
    public function name(): string
    {
        return '_01092 Makepeace Botwighte';
    }

    /**
     * Makepeace (P1) at Docks, a P1 ally and a P2 foe at Docks, and an attachment in the equipper's hand.
     *
     * @return array{0:_01092,1:Character,2:Character,3:Attachment}
     */
    private function scene(TestWorld $world): array
    {
        $makepeace = $world->placeCharacter(new _01092(), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Attachment $hat */
        $hat = $world->placeCard(new _01073(), Game::LOCATION_HAND, 2);
        return [$makepeace, $ally, $foe, $hat];
    }

    private function discount(TestWorld $world, _01092 $makepeace, Character $performer, Attachment $attachment, array &$explanations = []): int
    {
        return $makepeace->getEquipDiscount($world->theah, $performer, $attachment, $explanations);
    }

    public function tests(): array
    {
        return [
            'constructs Castille Diplomat Scoundrel with Action_01092' => function () {
                $makepeace = new _01092();
                Assert::instanceOf(IHasActions::class, $makepeace, 'actions');
                Assert::same(4, $makepeace->Resolve, 'Resolve');
                Assert::same(2, $makepeace->Combat, 'Combat');
                Assert::same(2, $makepeace->Finesse, 'Finesse');
                Assert::same(2, $makepeace->Influence, 'Influence');
                Assert::true($makepeace->hasFaction('Castille'), 'Castille faction');
                Assert::true($makepeace->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($makepeace->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($makepeace->hasTrait('Avalon'), 'Avalon');
                Assert::instanceOf(Action_01092::class, $makepeace->getActions()[0], 'Action_01092');
            },

            // WHY: "+1 cost" is modelled as a -1 discount; Theah sums discounts, so negative == more expensive.
            'opposing equipper at her location pays +1 (discount -1) with an explanation' => function () {
                $world = new TestWorld();
                [$makepeace, , $foe, $hat] = $this->scene($world);
                $explanations = [];

                $discount = $this->discount($world, $makepeace, $foe, $hat, $explanations);

                Assert::same(-1, $discount, 'discount');
                Assert::count(1, $explanations, 'explanation');
                Assert::contains('opposing Makepeace', $explanations[0], 'explanation text');
            },

            'her own controller\'s equipper is not taxed' => function () {
                $world = new TestWorld();
                [$makepeace, $ally, , $hat] = $this->scene($world);
                $explanations = [];

                Assert::same(0, $this->discount($world, $makepeace, $ally, $hat, $explanations), 'no change');
                Assert::count(0, $explanations, 'no explanation');
            },

            'Makepeace herself is not taxed' => function () {
                $world = new TestWorld();
                [$makepeace, , , $hat] = $this->scene($world);
                Assert::same(0, $this->discount($world, $makepeace, $makepeace, $hat), 'self');
            },

            'opposing equipper at another location is not taxed' => function () {
                $world = new TestWorld();
                [$makepeace, , , $hat] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_FORUM, 2);
                Assert::same(0, $this->discount($world, $makepeace, $far, $hat), 'other location');
            },

            // WHY: cardInCity gate - Home is not a city location, so two characters at Home are not "at her location".
            'opposing equipper sharing Home with Makepeace is not taxed' => function () {
                $world = new TestWorld();
                [$makepeace, , $foe, $hat] = $this->scene($world);
                $makepeace->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                Assert::same(0, $this->discount($world, $makepeace, $foe, $hat), 'Home');
            },

            'uncontrolled equipper is not "opposing"' => function () {
                $world = new TestWorld();
                [$makepeace, , $foe, $hat] = $this->scene($world);
                $foe->ControllerId = 0;
                Assert::same(0, $this->discount($world, $makepeace, $foe, $hat), 'uncontrolled');
            },

            'Theah aggregates the tax into the equip discount for the opposing equipper' => function () {
                $world = new TestWorld();
                [, , $foe, $hat] = $this->scene($world);

                [$discount, $explanation] = $world->theah->getEquipDiscount($foe, $hat);

                Assert::same(-1, $discount, 'summed discount');
                Assert::contains('opposing Makepeace', $explanation, 'explanation surfaced to the UI');
            },

            'Theah does not tax the controller\'s own equipper' => function () {
                $world = new TestWorld();
                [, $ally, , $hat] = $this->scene($world);

                [$discount] = $world->theah->getEquipDiscount($ally, $hat);

                Assert::same(0, $discount, 'no tax');
            },

            'two Makepeaces stack the tax' => function () {
                $world = new TestWorld();
                [, , $foe, $hat] = $this->scene($world);
                $world->placeCharacter(new _01092(), Game::LOCATION_CITY_DOCKS, 1);

                [$discount] = $world->theah->getEquipDiscount($foe, $hat);

                Assert::same(-2, $discount, 'each Makepeace adds +1');
            },
        ];
    }
}
