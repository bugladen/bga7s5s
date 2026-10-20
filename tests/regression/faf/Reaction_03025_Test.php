<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03025_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03025';
    }

    /**
     * @return array{0:_03025,1:Reaction_03025,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $angeline = $world->placeCharacter(new _03025(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        $foe->Engaged = true;
        /** @var Reaction_03025 $reaction */
        $reaction = $angeline->getReactions()[0];
        return [$angeline, $reaction, $foe];
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->initiatingPlayerId = 1;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a continuous card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03025(), 'card reaction');
            },

            // WHY: EventCardMoved runs before Location is written, so the offer looks at
            // toLocation. The wound button later compares owner->Location, which tests
            // must set to the destination. It does not set Used.
            'wounds an engaged opponent at the city location she moves to' => function () {
                $world = new TestWorld();
                [$angeline, $reaction, $foe] = $this->scene($world);
                $reaction->handleEvent($this->moved($world, $angeline->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered from the destination');

                $angeline->Location = Game::LOCATION_CITY_FORUM;
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(["wound-{$foe->Id}", 'pass'], $buttons, 'engaged foe');

                $reaction->performReaction($world->game, 0, $reaction->Id, "wound-{$foe->Id}");
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($foe->Id, $wound->characterId, 'foe');
                Assert::same($angeline->Id, $wound->sourceId, 'angeline');
                Assert::false($reaction->Used, 'continuous');
            },

            'an unengaged neighbor, a move home, or pass does not wound' => function () {
                $world = new TestWorld();
                [$angeline, $reaction, $foe] = $this->scene($world);
                $foe->Engaged = false;
                $reaction->handleEvent($this->moved($world, $angeline->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedEvents, 'not engaged');

                $foe->Engaged = true;
                $reaction->handleEvent($this->moved($world, $angeline->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME));
                Assert::count(0, $world->theah->queuedEvents, 'home is not a city location');

                $reaction->handleEvent($this->moved($world, $angeline->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                $angeline->Location = Game::LOCATION_CITY_FORUM;
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'pass');
                Assert::false($reaction->Used, 'not spent');
            },
        ];
    }
}
