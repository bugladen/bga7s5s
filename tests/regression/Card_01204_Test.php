<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_01204_Test extends TestCase
{
    public function name(): string
    {
        return '_01204 Syrneth Hand';
    }

    /**
     * @return array{0:_01204,1:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _01204 $hand */
        $hand = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 1);
        $hand->AttachedToId = $host->Id;
        $host->Attachments[] = $hand->Id;
        return [$hand, $host];
    }

    public function tests(): array
    {
        return [
            'constructs Unique Syrneth CityAttachment with Technique_01204' => function () {
                $hand = new _01204();
                Assert::instanceOf(CityAttachment::class, $hand, 'CityAttachment');
                Assert::instanceOf(IHasTechniques::class, $hand, 'techniques');
                Assert::same(2, $hand->WealthCost, 'Wealth');
                Assert::same(28, $hand->CityCardNumber, 'city #');
                Assert::same(1, $hand->CombatModifier, '+1 Combat');
                Assert::same(0, $hand->FinesseModifier, 'Finesse');
                Assert::same(0, $hand->InfluenceModifier, 'Influence');
                Assert::true($hand->hasTrait('Artifact'), 'Artifact');
                Assert::true($hand->hasTrait('Syrneth'), 'Syrneth');
                Assert::true($hand->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Technique_01204::class, $hand->getTechniques()[0], 'Technique');
            },

            // WHY (journal 2026-04-09-06): cannot be destroyed/moved unless the host is dying.
            'eventCheck refuses unequip while the host is not dying' => function () {
                $world = new TestWorld();
                [$hand, $host] = $this->equipped($world);

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $hand->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $hand->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'stuck on host');
            },

            'eventCheck allows unequip when the host is dying' => function () {
                $world = new TestWorld();
                [$hand, $host] = $this->equipped($world);
                $host->IsDying = true;

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $hand->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;
                $hand->eventCheck($event);
                Assert::true(true, 'dying host may unequip');
            },
        ];
    }
}
