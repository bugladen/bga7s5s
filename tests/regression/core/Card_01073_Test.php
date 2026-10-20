<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_01073_Test extends TestCase
{
    public function name(): string
    {
        return '_01073 Cavalier Hat';
    }

    private function equipping(TestWorld $world, _01073 $hat, int $characterId): EventAttachmentEquipping
    {
        $event = new EventAttachmentEquipping();
        $event->attachmentId = $hat->Id;
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne FactionAttachment with Action_01073' => function () {
                $hat = new _01073();
                Assert::instanceOf(FactionAttachment::class, $hat, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $hat, 'actions');
                Assert::same(2, $hat->WealthCost, 'WealthCost');
                Assert::same(1, $hat->FinesseModifier, '+1 Finesse');
                Assert::same(2, $hat->Riposte, 'Riposte');
                Assert::same(0, $hat->Parry, 'Parry');
                Assert::true($hat->DashedParry, 'dashed Parry');
                Assert::same(2, $hat->Thrust, 'Thrust');
                Assert::true($hat->hasTrait('Attire'), 'Attire');
                Assert::true($hat->hasTrait('Hat'), 'Hat');
                Assert::true($hat->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_01073::class, $hat->getActions()[0], 'Action_01073');
            },

            'eventCheck allows equip to a Duelist' => function () {
                $world = new TestWorld();
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $hat = $world->placeCard(new _01073(), Game::LOCATION_HAND, 1);

                $hat->eventCheck($this->equipping($world, $hat, $duelist->Id));
                Assert::true(true, 'no throw');
            },

            'eventCheck refuses equip to a non-Duelist' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $hat = $world->placeCard(new _01073(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $hat->eventCheck($this->equipping($world, $hat, $crew->Id));
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Duelist only');
            },

            'eventCheck ignores equip events for other attachments' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $hat = $world->placeCard(new _01073(), Game::LOCATION_HAND, 1);
                $other = $world->placeCard(new _01073(), Game::LOCATION_HAND, 1);

                $hat->eventCheck($this->equipping($world, $other, $crew->Id));
                Assert::true(true, 'no throw');
            },

            'canAttachTo only accepts Duelists' => function () {
                $world = new TestWorld();
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $hat = new _01073();

                Assert::true($hat->canAttachTo($duelist), 'Duelist');
                Assert::false($hat->canAttachTo($crew), 'non-Duelist');
            },
        ];
    }
}
