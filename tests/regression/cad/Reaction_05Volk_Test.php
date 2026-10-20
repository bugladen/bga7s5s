<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Volk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Volk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Volk_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Volk';
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

    /** @return array{0:_05Volk,1:GenericCharacter,2:Reaction_05Volk} */
    private function armedAt(TestWorld $world, string $volkLocation): array
    {
        $volk = $world->placeCharacter(new _05Volk(), $volkLocation, 1);
        // Post-move board: foe already at Forum when EventCardMoved is handled.
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Reaction_05Volk $reaction */
        $reaction = $volk->getReactions()[0];
        return [$volk, $foe, $reaction];
    }

    public function tests(): array
    {
        return [
            'offers when Volk is at Home and an opponent leaves Home for the Forum' => function () {
                $world = new TestWorld();
                [$volk, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_PLAYER_HOME);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($volk->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: Playtest — Volk in city, opponent Home → other city. Not leave-from-here;
            // Volk follows any enemy play→play move (unlike Horatio).
            'offers when Volk is in the city and an opponent leaves Home for another city' => function () {
                $world = new TestWorld();
                [$volk, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_CITY_DOCKS);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($volk->Id, $transitions[0]->sourceId, 'source');
            },

            'follow from Home moves Volk to the Forum without engaging' => function () {
                $world = new TestWorld();
                [$volk, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_PLAYER_HOME);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'followCharacter');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($volk->Id, $moves[0]->cardId, 'Volk moves');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->fromLocation, 'from Home');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to Forum');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::true($reaction->Used, 'used');
            },

            'offers when an opposing character leaves Volk city location' => function () {
                $world = new TestWorld();
                [$volk, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_CITY_DOCKS);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($volk->Id, $transitions[0]->sourceId, 'source');
            },

            'does not offer when the destination is already Volk location' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_CITY_FORUM);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'already there');
            },

            'does not offer for own characters moving' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->armedAt($world, Game::LOCATION_CITY_DOCKS);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $reaction->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'friendly');
            },

            'does not offer for a non-play destination' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_CITY_DOCKS);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, 'Discard-2'));

                Assert::count(0, $world->theah->queuedEvents, 'discard');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $foe, $reaction] = $this->armedAt($world, Game::LOCATION_CITY_DOCKS);
                $reaction->Used = true;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },
        ];
    }
}
