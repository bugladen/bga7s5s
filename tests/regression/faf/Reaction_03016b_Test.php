<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03016b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03016b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03016b';
    }

    /**
     * @return array{0:_03016,1:Reaction_03016b,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $ise = $world->placeCharacter(new _03016(), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Reaction_03016b $reaction */
        $reaction = $ise->getReactions()[1];
        return [$ise, $reaction, $ally, $foe];
    }

    private function arrived(TestWorld $world, int $cardId, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->fromLocation = Game::LOCATION_CITY_FORUM;
        $event->toLocation = $to;
        $event->initiatingPlayerId = 2;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03016b(), 'card reaction');
            },

            // WHY: An opposing character must arrive at her city location, and you must
            // have someone elsewhere to move. Uncontrolled arrivals do not count.
            // Pass does not spend. The move does not engage.
            'an enemy arriving lets you pull another character to her' => function () {
                $world = new TestWorld();
                [$ise, $reaction, $ally, $foe] = $this->scene($world);
                $reaction->handleEvent($this->arrived($world, $foe->Id, $ise->Location));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(["move-{$ally->Id}", 'pass'], $buttons, 'ally or pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, "move-{$ally->Id}");
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($ally->Id, $move->cardId, 'ally');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->fromLocation, 'from');
                Assert::same($ise->Location, $move->toLocation, 'to ise');
                Assert::false($move->engage, 'unengaged');
                Assert::true($reaction->Used, 'spent');
            },

            'your own arrival, an uncontrolled arrival, or Ise at Home does not offer' => function () {
                $world = new TestWorld();
                [$ise, $reaction, $ally, $foe] = $this->scene($world);
                $reaction->handleEvent($this->arrived($world, $ally->Id, $ise->Location));
                Assert::count(0, $world->theah->queuedEvents, 'friendly');

                $foe->ControllerId = 0;
                $reaction->handleEvent($this->arrived($world, $foe->Id, $ise->Location));
                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
                $foe->ControllerId = 2;

                $ise->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->arrived($world, $foe->Id, Game::LOCATION_PLAYER_HOME));
                Assert::count(0, $world->theah->queuedEvents, 'not in the city');
            },

            'pass does not spend' => function () {
                $world = new TestWorld();
                [$ise, $reaction, , $foe] = $this->scene($world);
                $reaction->handleEvent($this->arrived($world, $foe->Id, $ise->Location));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}
