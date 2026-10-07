<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01039_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01039';
    }

    public function tests(): array
    {
        return [
            'offers after Philip equips non-fake attachment' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Reaction_01039 $reaction */
                $reaction = $philip->getReactions()[0];
                $event = new EventAttachmentEquipped();
                $event->characterId = $philip->Id;
                $event->attachmentId = $att->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'does not offer for FakeAttachment' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $att->FakeAttachment = true;

                /** @var Reaction_01039 $reaction */
                $reaction = $philip->getReactions()[0];
                $event = new EventAttachmentEquipped();
                $event->characterId = $philip->Id;
                $event->attachmentId = $att->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'fake');
            },

            'performReaction moves Philip to chosen location' => function () {
                $world = new TestWorld();
                $philip = $world->placeCharacter(new _01039(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_01039 $reaction */
                $reaction = $philip->getReactions()[0];

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    'moveTo-' . Game::LOCATION_CITY_FORUM
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($philip->Id, $moves[0]->cardId, 'philip');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'forum');
                Assert::true($reaction->Used, 'used');
            },
        ];
    }
}
