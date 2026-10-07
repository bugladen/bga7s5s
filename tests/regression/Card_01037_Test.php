<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_01037_Test extends TestCase
{
    public function name(): string
    {
        return '_01037 Edeline Trinken';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Reaction_01037' => function () {
                $edeline = new _01037();
                Assert::instanceOf(IHasReactions::class, $edeline, 'reactions');
                Assert::same(4, $edeline->Resolve, 'Resolve');
                Assert::same(1, $edeline->Combat, 'Combat');
                Assert::same(2, $edeline->Finesse, 'Finesse');
                Assert::same(0, $edeline->Influence, 'Influence');
                Assert::true($edeline->hasTrait('Innkeeper'), 'Innkeeper');
                Assert::true($edeline->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Reaction_01037::class, $edeline->getReactions()[0], 'Reaction_01037');
            },

            // WHY: updateInfluence counts characters at toLocation then +1 for the mover
            // (mover Location not yet updated when EventCardMoved fires)
            'ally arrives at Edeline location bumps influence' => function () {
                $world = new TestWorld();
                $edeline = $world->placeCharacter(new _01037(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $event = new EventCardMoved();
                $event->cardId = $ally->Id;
                $event->fromLocation = Game::LOCATION_CITY_FORUM;
                $event->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($edeline, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence event');
                // docks currently: edeline only (ally still at forum in RAM) + adj 1 = 2
                Assert::same(2, $mods[0]->NewInfluence, 'edeline + arriving ally adj');
            },

            'blanked restores printed Influence when modified' => function () {
                $world = new TestWorld();
                $edeline = $world->placeCharacter(new _01037(), Game::LOCATION_CITY_DOCKS, 1);
                $edeline->ModifiedInfluence = 3;
                $edeline->onAbilitiesBlanked($world->theah);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::same(0, $mods[0]->NewInfluence, 'printed 0');
            },
        ];
    }
}
