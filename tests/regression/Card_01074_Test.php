<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01074;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneRiposte;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_01074_Test extends TestCase
{
    public function name(): string
    {
        return '_01074 Mastercrafted Rapier';
    }

    private function equipping(TestWorld $world, _01074 $rapier, int $characterId): EventAttachmentEquipping
    {
        $event = new EventAttachmentEquipping();
        $event->attachmentId = $rapier->Id;
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Weapon with shared Technique_PlusOneRiposte' => function () {
                $rapier = new _01074();
                Assert::instanceOf(FactionAttachment::class, $rapier, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $rapier, 'techniques');
                Assert::same(0, $rapier->WealthCost, 'WealthCost');
                Assert::same(2, $rapier->Riposte, 'Riposte');
                Assert::same(0, $rapier->Parry, 'Parry');
                Assert::same(1, $rapier->Thrust, 'Thrust');
                Assert::true($rapier->hasTrait('Weapon'), 'Weapon');
                Assert::true($rapier->hasTrait('Sword'), 'Sword');
                Assert::true($rapier->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $rapier->getTechniques(), 'one technique');
                Assert::instanceOf(Technique_PlusOneRiposte::class, $rapier->getTechniques()[0], 'Technique_PlusOneRiposte');
            },

            // WHY: Shared Technique_PlusOneRiposte class is re-Id'd per card (setId) so Reaction/duel code
            // can tell which card's technique was activated. Ownership prefix is added on placement.
            'technique class id is Technique_01074 and owner prefix applied on placement' => function () {
                $rapier = new _01074();
                Assert::same('Technique_01074', $rapier->getTechniques()[0]->ClassId, 'ClassId');

                $world = new TestWorld();
                $placed = $world->placeCard(new _01074(), Game::LOCATION_HAND, 1);
                $technique = $placed->getTechniques()[0];
                Assert::same("{$placed->Id}_Technique_01074", $technique->Id, 'owner-prefixed Id');
                Assert::same($technique, $placed->getTechniqueById($technique->Id), 'lookup by id');
            },

            'eventCheck allows equip to a Duelist' => function () {
                $world = new TestWorld();
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $rapier = $world->placeCard(new _01074(), Game::LOCATION_HAND, 1);

                $rapier->eventCheck($this->equipping($world, $rapier, $duelist->Id));
                Assert::true(true, 'no throw');
            },

            'eventCheck refuses equip to a non-Duelist' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $rapier = $world->placeCard(new _01074(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $rapier->eventCheck($this->equipping($world, $rapier, $crew->Id));
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Duelist only');
            },

            'canAttachTo only accepts Duelists' => function () {
                $world = new TestWorld();
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $rapier = new _01074();

                Assert::true($rapier->canAttachTo($duelist), 'Duelist');
                Assert::false($rapier->canAttachTo($crew), 'non-Duelist');
            },
        ];
    }
}
