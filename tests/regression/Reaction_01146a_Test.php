<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01146;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01146a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01146a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01146a';
    }

    /** @return array{0:_01146,1:Reaction_01146a} */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01146(), Game::LOCATION_PLAYER_HOME, 1);
        /** @var Reaction_01146a $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction];
    }

    private function equipped(TestWorld $world, int $characterId, int $attachmentId): EventAttachmentEquipped
    {
        $event = new EventAttachmentEquipped();
        $event->characterId = $characterId;
        $event->attachmentId = $attachmentId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'buttons offer Draw card and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['drawCard', 'pass'], $ids, 'buttons');
            },

            'offers when controller\'s character equips a Weapon' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->equipped($world, $host->Id, $weapon->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            'does not offer for a non-Weapon attachment' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                // _01047 Breastplate is Armor, not Weapon
                $armor = $world->placeCard(new _01047(), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->equipped($world, $host->Id, $armor->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not weapon');
            },

            'does not offer when an opponent equips a Weapon' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $weapon = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->equipped($world, $foe->Id, $weapon->Id));

                Assert::count(0, $world->theah->queuedEvents, 'opponent');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->Used = true;

                $reaction->handleEvent($this->equipped($world, $host->Id, $weapon->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'drawCard queues EventCardDrawn, marks Used, and finishes' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the Reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'Dusk resets the Reaction' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->setUsed($world->theah, true);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::false($reaction->Used, 'reset');
            },
        ];
    }
}
