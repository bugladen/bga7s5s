<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentMoved;

class Card_01047_Test extends TestCase
{
    public function name(): string
    {
        return "_01047 Kaspar's Panzerhand";
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with Reaction and Technique' => function () {
                $panzer = new _01047();
                Assert::instanceOf(FactionAttachment::class, $panzer, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $panzer, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $panzer, 'techniques');
                Assert::same(2, $panzer->WealthCost, 'WealthCost');
                Assert::same(1, $panzer->ResolveModifier, 'ResolveModifier');
                Assert::same(0, $panzer->Riposte, 'Riposte');
                Assert::same(4, $panzer->Parry, 'Parry');
                Assert::same(1, $panzer->Thrust, 'Thrust');
                Assert::true($panzer->OffHand, 'OffHand');
                Assert::true($panzer->hasTrait('Armor'), 'Armor');
                Assert::true($panzer->hasTrait('Unique'), 'Unique');
                Assert::false($panzer->canBeMoved(), 'cannot move');
                Assert::instanceOf(Reaction_01047::class, $panzer->getReactions()[0], 'Reaction_01047');
                Assert::instanceOf(Technique_01047::class, $panzer->getTechniques()[0], 'Technique_01047');
            },

            'eventCheck throws when AttachmentMoved targets Panzerhand' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $panzer = $world->placeCard(new _01047(), Game::LOCATION_CITY_DOCKS, 1);
                $panzer->AttachedToId = $host->Id;

                $event = new EventAttachmentMoved();
                $event->attachmentId = $panzer->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $panzer->eventCheck($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'cannot be moved');
            },
        ];
    }
}
