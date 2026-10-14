<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02006;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_02006_Test extends TestCase
{
    public function name(): string
    {
        return '_02006 The Red Scepter';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce FactionAttachment with Technique_02006' => function () {
                $scepter = new _02006();
                Assert::instanceOf(FactionAttachment::class, $scepter, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $scepter, 'techniques');
                Assert::same(1, $scepter->WealthCost, 'Wealth');
                Assert::same(1, $scepter->InfluenceModifier, 'Influence');
                Assert::same(1, $scepter->Riposte, 'Riposte');
                Assert::same(1, $scepter->Parry, 'Parry');
                Assert::same(1, $scepter->Thrust, 'Thrust');
                Assert::true($scepter->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($scepter->hasTrait('Attire'), 'Attire');
                Assert::true($scepter->hasTrait('Heirloom'), 'Heirloom');
                Assert::true($scepter->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Technique_02006::class, $scepter->getTechniques()[0], 'Technique');
            },

            'owns Technique_02006 when placed' => function () {
                $world = new TestWorld();
                $scepter = $world->placeCard(new _02006(), Game::LOCATION_HAND, 1);
                Assert::count(1, $scepter->getTechniques(), 'one');
                Assert::same($scepter->Id, $scepter->getTechniques()[0]->OwnerId, 'owner');
            },

            // WHY (journal 2026-03-16-06): eventCheck on EventAttachmentEquipping — Red Hand only.
            'eventCheck refuses equip to non-Red Hand' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $scepter = $world->placeCard(new _02006(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $scepter->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $scepter->eventCheck($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'needs Red Hand');
            },

            'eventCheck allows equip to Red Hand' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $scepter = $world->placeCard(new _02006(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $scepter->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;
                $scepter->eventCheck($event);
                Assert::true(true, 'no throw');
            },

            'duel technique state constant registered' => function () {
                Assert::same(52102006, States::DUEL_CHOOSE_TECHNIQUE_02006, 'chooser');
            },
        ];
    }
}
