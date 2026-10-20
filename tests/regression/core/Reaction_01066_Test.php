<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01066_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01066';
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->theah = $world->theah;
        return $event;
    }

    /** @return array{0:_01066,1:GenericCharacter,2:Reaction_01066} */
    private function armed(TestWorld $world): array
    {
        $horatio = $world->placeCharacter(new _01066(), Game::LOCATION_CITY_DOCKS, 1);
        // Post-move state: hub already moved the foe to Forum before cards handle EventCardMoved.
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Reaction_01066 $reaction */
        $reaction = $horatio->getReactions()[0];
        return [$horatio, $foe, $reaction];
    }

    public function tests(): array
    {
        return [
            'offers when an opposing character leaves Horatio location for an adjacent location' => function () {
                $world = new TestWorld();
                [$horatio, $foe, $reaction] = $this->armed($world);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($horatio->Id, $transitions[0]->sourceId, 'source');
            },

            'does not offer for a non-adjacent destination' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armed($world);

                // 2p: Docks is adjacent only to Forum (and Home).
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR));

                Assert::count(0, $world->theah->queuedEvents, 'not adjacent');
            },

            'does not offer when the opponent moves Home' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armed($world);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME));

                Assert::count(0, $world->theah->queuedEvents, 'Home excluded (includeHome=false)');
            },

            // WHY: "opposing" = was at Horatio's location before the move; fromLocation must match.
            'does not offer when the mover did not start at Horatio location' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armed($world);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_BAZAAR, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'other origin');
            },

            'does not offer for own characters moving' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $reaction->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'friendly');
            },

            'does not offer when Horatio is at Home' => function () {
                $world = new TestWorld();
                [$horatio, $foe, $reaction] = $this->armed($world);
                $horatio->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'not in city');
            },

            // WHY: journal 2026-03-31 audit - handleEvent was missing isAvailable(), so a used reaction re-prompted.
            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armed($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer Follow and Pass' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->armed($world);

                $ids = array_map(fn($b) => $b['reaction'], $reaction->getReactionButtonProperties($world->theah));

                Assert::true(in_array('followCharacter', $ids, true), 'follow');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            // WHY: reaction move is not "moving as an action", so it must not auto-engage Horatio.
            'follow moves Horatio to the new location without engaging and marks Used' => function () {
                $world = new TestWorld();
                [$horatio, $foe, $reaction] = $this->armed($world);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'followCharacter');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($horatio->Id, $moves[0]->cardId, 'Horatio moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to foe location');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not move or mark Used' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armed($world);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
