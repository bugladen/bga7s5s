<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01178;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class Card_01178_Test extends TestCase
{
    public function name(): string
    {
        return '_01178 Carmella Vanessa Slavaggi';
    }

    /** @return _01178 */
    private function placed(TestWorld $world): _01178
    {
        /** @var _01178 $carmella */
        $carmella = $world->placeCharacter(new _01178(), Game::LOCATION_CITY_DOCKS, 1);
        return $carmella;
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Duelist Vodacce CityCharacter' => function () {
                $carmella = new _01178();
                Assert::instanceOf(CityCharacter::class, $carmella, 'CityCharacter');
                Assert::same(4, $carmella->Resolve, 'Resolve');
                Assert::same(3, $carmella->Combat, 'Combat');
                Assert::same(2, $carmella->Finesse, 'Finesse');
                Assert::same(1, $carmella->Influence, 'Influence');
                Assert::same(5, $carmella->WealthCost, 'Wealth');
                Assert::same(2, $carmella->CityCardNumber, 'city #');
                Assert::true($carmella->Negotiable, 'Negotiable');
                Assert::true($carmella->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($carmella->hasTrait('Duelist'), 'Duelist');
                Assert::true($carmella->hasTrait('Vodacce'), 'Vodacce');
                Assert::false($carmella->AbilityUsed, 'unused');
            },

            // WHY (journal 2026-04-07-10): canChallenge/canIntervene share !$Engaged || !$AbilityUsed
            // so free Carmella always can, engaged Carmella can once per Day until AbilityUsed.
            'can challenge and intervene while unengaged' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = false;
                Assert::true($carmella->canChallenge($world->theah), 'challenge');
                Assert::true($carmella->canIntervene(), 'intervene');
            },

            'can challenge and intervene while engaged if AbilityUsed is false' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = true;
                $carmella->AbilityUsed = false;
                Assert::true($carmella->canChallenge($world->theah), 'challenge');
                Assert::true($carmella->canIntervene(), 'intervene');
            },

            'cannot challenge or intervene while engaged once AbilityUsed' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = true;
                $carmella->AbilityUsed = true;
                Assert::false($carmella->canChallenge($world->theah), 'challenge');
                Assert::false($carmella->canIntervene(), 'intervene');
            },

            // WHY (journal 2026-04-07-10): AbilityUsed is shared — "challenge or intervene" is one shot.
            'issuing a challenge while engaged sets AbilityUsed and Carmella condition' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = true;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventChallengeIssued();
                $event->challengerId = $carmella->Id;
                $event->defenderId = $foe->Id;
                $event->playerId = 1;
                $world->fireOn($carmella, $event);

                Assert::true($carmella->AbilityUsed, 'used');
                Assert::true($carmella->hasCondition(Game::CARMELLA_ABILITY_USED), 'condition');
            },

            'intervening while engaged sets AbilityUsed' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = true;
                $old = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventCharacterIntervened();
                $event->playerId = 1;
                $event->oldTargetId = $old->Id;
                $event->newTargetId = $carmella->Id;
                $world->fireOn($carmella, $event);

                Assert::true($carmella->AbilityUsed, 'used');
                Assert::true($carmella->hasCondition(Game::CARMELLA_ABILITY_USED), 'condition');
            },

            // WHY (journal 2026-04-07-10): Engaged must reflect pre-intervene state. Framework
            // queues EventCharacterIntervened before EventCardEngaged so free Carmella does not
            // burn the once-per-day when she intervenes.
            'intervening while unengaged does not set AbilityUsed' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = false;
                $old = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventCharacterIntervened();
                $event->playerId = 1;
                $event->oldTargetId = $old->Id;
                $event->newTargetId = $carmella->Id;
                $world->fireOn($carmella, $event);

                Assert::false($carmella->AbilityUsed, 'not burned');
                Assert::false($carmella->hasCondition(Game::CARMELLA_ABILITY_USED), 'no condition');
            },

            'challenging while unengaged does not set AbilityUsed' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->Engaged = false;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventChallengeIssued();
                $event->challengerId = $carmella->Id;
                $event->defenderId = $foe->Id;
                $event->playerId = 1;
                $world->fireOn($carmella, $event);

                Assert::false($carmella->AbilityUsed, 'not burned');
            },

            'Dusk clears AbilityUsed and the Carmella condition' => function () {
                $world = new TestWorld();
                $carmella = $this->placed($world);
                $carmella->AbilityUsed = true;
                $carmella->addCondition(Game::CARMELLA_ABILITY_USED);

                $dusk = new EventDuskEndOfDay();
                $world->fireOn($carmella, $dusk);

                Assert::false($carmella->AbilityUsed, 'cleared');
                Assert::false($carmella->hasCondition(Game::CARMELLA_ABILITY_USED), 'condition gone');
            },
        ];
    }
}
