<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Gary;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Gary;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Gary_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Gary';
    }

    /**
     * Two characters the controlling player owns, same location as Rena.
     * The second wound is already queued when the first is handled — the
     * Action_01012 batch shape, but both victims qualify.
     *
     * @return array{0:_05Gary,1:_01012,2:_02011,3:Reaction_05Gary,4:EventCharacterBeingWounded}
     */
    private function ownedThenQueued(TestWorld $world): array
    {
        $first = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
        $second = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 2);
        $rena = $world->placeCharacter(new _05Gary(), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_05Gary $reaction */
        $reaction = $rena->getReactions()[0];

        $secondWound = EventFactory::createCharacterBeingWoundedEvent(
            $second->Id,
            $first->Id,
            1,
            $first->getInjectCode(),
            'Action_01012'
        );
        $world->theah->queueEvent($secondWound);

        return [$rena, $first, $second, $reaction, $secondWound];
    }

    public function tests(): array
    {
        return [
            // WHY: Playtest batch — two MEDIUM wounds, the later one already queued.
            // The offer was priority 6, so the later wound resolved first while Gary
            // was already holding the first and would not take another.
            'offer is stacked ahead of a wound already queued behind this one' => function () {
                $world = new TestWorld();
                [$rena, $first, $second, $reaction, $secondWound] = $this->ownedThenQueued($world);

                $firstWound = EventFactory::createCharacterBeingWoundedEvent(
                    $first->Id,
                    $first->Id,
                    1,
                    $first->getInjectCode()
                );
                $world->fire($firstWound);

                Assert::true($firstWound->canceled, 'owned wound held');
                Assert::false($secondWound->canceled, 'later wound not resolved yet');
                Assert::instanceOf(EventTransition::class, $world->theah->queuedEvents[0], 'offer is next');
                Assert::same('reaction', $world->theah->queuedEvents[0]->transition, 'reaction transition');
                Assert::same($rena->Id, $world->theah->queuedEvents[0]->sourceId, 'Rena');
                Assert::same($secondWound, $world->theah->queuedEvents[1], 'later wound still waiting');

                $description = $reaction->getReactionDescription($world->theah);
                Assert::true(str_contains($description, $first->Name), 'prompt names the held victim');
                Assert::false(str_contains($description, $second->Name), 'prompt is not the later victim');
            },

            'an opponent character at the location is not offered' => function () {
                $world = new TestWorld();
                $opponent = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new _05Gary(), Game::LOCATION_CITY_DOCKS, 2);

                $wound = EventFactory::createCharacterBeingWoundedEvent(
                    $opponent->Id,
                    $opponent->Id,
                    1,
                    $opponent->getInjectCode()
                );
                $world->fire($wound);

                Assert::false($wound->canceled, 'opponent wound lands');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no offer');
            },

            'a character you control but do not own is not offered' => function () {
                $world = new TestWorld();
                $recruited = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 2);
                $recruited->OwnerId = 0;
                $world->placeCharacter(new _05Gary(), Game::LOCATION_CITY_DOCKS, 2);

                $wound = EventFactory::createCharacterBeingWoundedEvent(
                    $recruited->Id,
                    $recruited->Id,
                    1,
                    $recruited->getInjectCode()
                );
                $world->fire($wound);

                Assert::false($wound->canceled, 'city character wound lands');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no offer');
            },

            'pass releases the held wound before the next victim, who can still be redirected' => function () {
                $world = new TestWorld();
                [$rena, $first, $second, $reaction, $secondWound] = $this->ownedThenQueued($world);

                $firstWound = EventFactory::createCharacterBeingWoundedEvent(
                    $first->Id,
                    $first->Id,
                    1,
                    $first->getInjectCode()
                );
                $world->fire($firstWound);
                $world->theah->takeQueuedEvents();
                $world->theah->queueEvent($secondWound);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                $front = $world->theah->queuedEvents[0];
                Assert::instanceOf(EventCharacterBeingWounded::class, $front, 'released wound is next');
                Assert::same($first->Id, $front->characterId, 'first victim released first');
                Assert::false($front->canceled, 'clone not canceled');
                Assert::false($secondWound->canceled, 'later wound still waiting');

                $world->fire($front);
                Assert::false($front->canceled, 'skip lets the first victim through');

                $world->fire($secondWound);
                Assert::true($secondWound->canceled, 'owned later victim offered after the pass');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'second offer');
                Assert::same($rena->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'still Rena');
            },

            'accept moves the held wound onto Rena and leaves the later wound alone once used' => function () {
                $world = new TestWorld();
                [$rena, $first, , $reaction, $secondWound] = $this->ownedThenQueued($world);

                $firstWound = EventFactory::createCharacterBeingWoundedEvent(
                    $first->Id,
                    $first->Id,
                    1,
                    $first->getInjectCode()
                );
                $world->fire($firstWound);
                $world->theah->takeQueuedEvents();
                $world->theah->queueEvent($secondWound);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundRena');

                $redirected = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $ontoRena = array_values(array_filter(
                    $redirected,
                    fn($event) => $event->characterId === $rena->Id
                ));
                Assert::count(1, $ontoRena, 'Rena takes the held wound');
                Assert::true($reaction->Used, 'reaction spent');

                $world->fire($secondWound);
                Assert::false($secondWound->canceled, 'used reaction does not take the later victim');
            },
        ];
    }
}
