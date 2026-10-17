<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03019_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03019';
    }

    /**
     * @return array{0:_03019,1:Reaction_03019,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $hound = $world->placeCard(new _03019(), Game::LOCATION_CITY_DOCKS, 1);
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $hound->AttachedToId = $host->Id;
        /** @var Reaction_03019 $reaction */
        $reaction = $hound->getReactions()[0];
        return [$hound, $reaction, $host, $foe];
    }

    private function left(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->initiatingPlayerId = 2;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is an attachment reaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_03019(), 'attachment reaction');
            },

            // WHY: The opposing character must leave the host's city location for an
            // adjacent city location. Home is not adjacent for this reaction.
            // Hunt engages the attachment, moves the host unengaged, and engages the quarry.
            'hunts an opponent who moves to an adjacent city location' => function () {
                $world = new TestWorld();
                [$hound, $reaction, $host, $foe] = $this->scene($world);
                $reaction->handleEvent($this->left($world, $foe->Id, $host->Location, Game::LOCATION_CITY_FORUM));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['hunt', 'pass'], $buttons, 'buttons');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'hunt');
                $engaged = $world->theah->queuedOfType(EventCardEngaged::class);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($hound->Id, $engaged[0]->cardId, 'attachment engages');
                Assert::same($host->Id, $move->cardId, 'host moves');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'to the quarry');
                Assert::false($move->engage, 'move does not engage the host');
                Assert::same($foe->Id, $engaged[1]->cardId, 'quarry engages');
                Assert::true($reaction->Used, 'spent');
            },

            'an engaged attachment, a move home, or your own character does not offer' => function () {
                $world = new TestWorld();
                [$hound, $reaction, $host, $foe] = $this->scene($world);
                $hound->Engaged = true;
                $reaction->handleEvent($this->left($world, $foe->Id, $host->Location, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedEvents, 'attachment already engaged');
                $hound->Engaged = false;

                $reaction->handleEvent($this->left($world, $foe->Id, $host->Location, Game::LOCATION_PLAYER_HOME));
                Assert::count(0, $world->theah->queuedEvents, 'home is not a city destination');

                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->handleEvent($this->left($world, $ally->Id, $host->Location, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedEvents, 'not opposing');

                $reaction->handleEvent($this->left($world, $foe->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR));
                Assert::count(0, $world->theah->queuedEvents, 'did not leave the host');
            },

            'pass does not spend' => function () {
                $world = new TestWorld();
                [, $reaction, $host, $foe] = $this->scene($world);
                $reaction->handleEvent($this->left($world, $foe->Id, $host->Location, Game::LOCATION_CITY_FORUM));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
            },
        ];
    }
}
