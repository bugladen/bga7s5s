<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03027b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03027b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03027b';
    }

    /**
     * @return array{0:_03027,1:Reaction_03027b,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $odette = $world->placeCharacter(new _03027(), Game::LOCATION_CITY_DOCKS, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
        $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist', 'Hero']), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_03027b $reaction */
        $reaction = $odette->getReactions()[1];
        return [$odette, $reaction, $challenger, $duelist];
    }

    private function issued(TestWorld $world, int $challengerId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->theah = $world->theah;
        $event->challengerId = $challengerId;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: The challenger must already be at her city location. The Duelist must be
            // hers, at an adjacent city (Home does not count), and the move does not engage.
            // A choice that fails the recheck does not spend.
            'moves an adjacent Duelist to her location' => function () {
                $world = new TestWorld();
                [$odette, $reaction, $challenger, $duelist] = $this->scene($world);
                $reaction->handleEvent($this->issued($world, $challenger->Id));
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(["move-{$duelist->Id}", 'pass'], $buttons, 'the duelist');

                $reaction->performReaction($world->game, 0, $reaction->Id, "move-{$duelist->Id}");
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($duelist->Id, $move->cardId, 'duelist');
                Assert::same($odette->Location, $move->toLocation, 'to Odette');
                Assert::false($move->engage, 'does not engage');
                Assert::true($reaction->Used, 'spent');
            },

            'pass, a civilian, and a canceled challenge do not spend' => function () {
                $world = new TestWorld();
                [$odette, $reaction, $challenger, $duelist] = $this->scene($world);
                $reaction->handleEvent($this->issued($world, $challenger->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');

                $civilian = $world->placeCharacter(new GenericCharacter('Civilian', ['Hero', 'Spy']), Game::LOCATION_CITY_FORUM, 1);
                $reaction->performReaction($world->game, 0, $reaction->Id, "move-{$civilian->Id}");
                Assert::false($reaction->Used, 'not a duelist');

                $duelist->Location = Game::LOCATION_PLAYER_HOME;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($this->issued($world, $challenger->Id));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'home is not adjacent city');

                $duelist->Location = Game::LOCATION_CITY_FORUM;
                $issued = $this->issued($world, $challenger->Id);
                $issued->canceled = true;
                $reaction->handleEvent($issued);
                Assert::count(0, $world->theah->queuedEvents, 'canceled');
                unset($odette);
            },
        ];
    }
}
