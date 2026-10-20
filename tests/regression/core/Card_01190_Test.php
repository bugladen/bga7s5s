<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01190;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;

/** Combat +1 attachment stand-in for Sigurd combat-cap tests. */
final class TestCombatAttachment_01190 extends Attachment
{
    public function __construct(int $combatModifier = 1)
    {
        parent::__construct();
        $this->Name = 'Test Combat Attachment';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->CombatModifier = $combatModifier;
        $this->WealthCost = 1;
        $this->resetCard();
    }
}

class Card_01190_Test extends TestCase
{
    public function name(): string
    {
        return '_01190 Sigurd Ulfsen';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable CityCharacter Mercenary Pirate Vesten' => function () {
                $sigurd = new _01190();
                Assert::instanceOf(CityCharacter::class, $sigurd, 'CityCharacter');
                Assert::true($sigurd->Negotiable, 'Negotiable');
                Assert::same(5, $sigurd->Resolve, 'Resolve');
                Assert::same(2, $sigurd->Combat, 'Combat');
                Assert::same(3, $sigurd->Finesse, 'Finesse');
                Assert::same(0, $sigurd->Influence, 'Influence');
                Assert::true($sigurd->DashedInfluence, 'dashed Influence');
                Assert::same(4, $sigurd->WealthCost, 'Wealth');
                Assert::same(14, $sigurd->CityCardNumber, 'city #');
                Assert::true($sigurd->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($sigurd->hasTrait('Pirate'), 'Pirate');
                Assert::true($sigurd->hasTrait('Vesten'), 'Vesten');
            },

            'canChallenge always returns false' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($sigurd->canChallenge($world->theah), 'cannot challenge');
            },

            // WHY (journal 2026-04-09-02): Combat cannot increase — addAttachment caps at base.
            'addAttachment with +1 Combat leaves ModifiedCombat at base' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new TestCombatAttachment_01190(1), Game::LOCATION_CITY_DOCKS, 1);

                $sigurd->addAttachment($world->theah, $att);

                Assert::same(2, $sigurd->ModifiedCombat, 'capped');
                Assert::true(in_array($att->Id, $sigurd->Attachments, true), 'listed');
            },

            // WHY: parent subtracts the modifier that was never applied — recalculate from scratch.
            'removeAttachment after capped +1 does not phantom-subtract Combat' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new TestCombatAttachment_01190(1), Game::LOCATION_CITY_DOCKS, 1);
                $sigurd->addAttachment($world->theah, $att);
                Assert::same(2, $sigurd->ModifiedCombat, 'capped');

                $sigurd->removeAttachment($world->theah, $att);

                Assert::same(2, $sigurd->ModifiedCombat, 'restored base');
                Assert::false(in_array($att->Id, $sigurd->Attachments, true), 'removed');
            },

            'EventCharacterCombatModified caps Combat back to base' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                $sigurd->ModifiedCombat = 5;

                $event = new EventCharacterCombatModified();
                $event->CharacterId = $sigurd->Id;
                $event->NewCombat = 5;
                $event->theah = $world->theah;
                $sigurd->handleEvent($event);

                Assert::same(2, $sigurd->ModifiedCombat, 'capped');
                Assert::true($sigurd->IsUpdated, 'dirty');
            },

            // WHY: While en garde, enemy challenges at this location must target Sigurd.
            'eventCheck blocks enemy challenge of another character at the same location while en garde' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $sigurd->Engaged = false;

                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $ally->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $sigurd->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must target Sigurd');
            },

            'eventCheck allows the challenge when Sigurd is the defender' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $sigurd->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $sigurd->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'ok');
            },

            'eventCheck allows the challenge when Sigurd is engaged' => function () {
                $world = new TestWorld();
                $sigurd = $world->placeCharacter(new _01190(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $sigurd->Engaged = true;

                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $ally->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $sigurd->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'engaged skips gate');
            },
        ];
    }
}
