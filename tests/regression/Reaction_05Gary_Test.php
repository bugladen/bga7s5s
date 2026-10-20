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
     * Action_01012 queues Sibella's wound, then the target's, both MEDIUM.
     * The target is already sitting in the queue when Sibella's wound is handled.
     *
     * @return array{0:_05Gary,1:_01012,2:_02011,3:Reaction_05Gary,4:EventCharacterBeingWounded}
     */
    private function sibellaThenKatain(TestWorld $world): array
    {
        $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
        $katain = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 2);
        $rena = $world->placeCharacter(new _05Gary(), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_05Gary $reaction */
        $reaction = $rena->getReactions()[0];

        $katainWound = EventFactory::createCharacterBeingWoundedEvent(
            $katain->Id,
            $sibella->Id,
            1,
            $sibella->getInjectCode(),
            'Action_01012'
        );
        $world->theah->queueEvent($katainWound);

        return [$rena, $sibella, $katain, $reaction, $katainWound];
    }

    public function tests(): array
    {
        return [
            // WHY: Playtest — Sibella (Action_01012) wounded Katain (02011) at Rena's location.
            // The offer was queued at priority 6, so Katain's MEDIUM wound resolved first
            // while Gary was already holding Sibella's wound and would not take another.
            'offer is stacked ahead of a wound already queued behind this one' => function () {
                $world = new TestWorld();
                [$rena, $sibella, $katain, $reaction, $katainWound] = $this->sibellaThenKatain($world);

                $sibellaWound = EventFactory::createCharacterBeingWoundedEvent(
                    $sibella->Id,
                    $sibella->Id,
                    1,
                    $sibella->getInjectCode()
                );
                $world->fire($sibellaWound);

                Assert::true($sibellaWound->canceled, 'Sibella wound held');
                Assert::false($katainWound->canceled, 'Katain not resolved yet');
                Assert::instanceOf(EventTransition::class, $world->theah->queuedEvents[0], 'offer is next');
                Assert::same('reaction', $world->theah->queuedEvents[0]->transition, 'reaction transition');
                Assert::same($rena->Id, $world->theah->queuedEvents[0]->sourceId, 'Rena');
                Assert::same($katainWound, $world->theah->queuedEvents[1], 'Katain still waiting');

                $description = $reaction->getReactionDescription($world->theah);
                Assert::true(str_contains($description, $sibella->Name), 'prompt names Sibella');
                Assert::false(str_contains($description, $katain->Name), 'prompt is not Katain');
            },

            'pass releases the held wound before the next victim, who can still be redirected' => function () {
                $world = new TestWorld();
                [$rena, $sibella, $katain, $reaction, $katainWound] = $this->sibellaThenKatain($world);

                $sibellaWound = EventFactory::createCharacterBeingWoundedEvent(
                    $sibella->Id,
                    $sibella->Id,
                    1,
                    $sibella->getInjectCode()
                );
                $world->fire($sibellaWound);
                $world->theah->takeQueuedEvents();
                $world->theah->queueEvent($katainWound);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                $front = $world->theah->queuedEvents[0];
                Assert::instanceOf(EventCharacterBeingWounded::class, $front, 'released wound is next');
                Assert::same($sibella->Id, $front->characterId, 'Sibella released first');
                Assert::false($front->canceled, 'clone not canceled');
                Assert::false($katainWound->canceled, 'Katain still waiting');

                $world->fire($front);
                Assert::false($front->canceled, 'skip lets Sibella through');

                $world->fire($katainWound);
                Assert::true($katainWound->canceled, 'Katain offered after the pass');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'second offer');
                Assert::same($rena->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'still Rena');
            },

            'accept moves the held wound onto Rena and leaves the later wound alone once used' => function () {
                $world = new TestWorld();
                [$rena, $sibella, , $reaction, $katainWound] = $this->sibellaThenKatain($world);

                $sibellaWound = EventFactory::createCharacterBeingWoundedEvent(
                    $sibella->Id,
                    $sibella->Id,
                    1,
                    $sibella->getInjectCode()
                );
                $world->fire($sibellaWound);
                $world->theah->takeQueuedEvents();
                $world->theah->queueEvent($katainWound);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundRena');

                $redirected = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $ontoRena = array_values(array_filter(
                    $redirected,
                    fn($event) => $event->characterId === $rena->Id
                ));
                Assert::count(1, $ontoRena, 'Rena takes the held wound');
                Assert::true($reaction->Used, 'reaction spent');

                $world->fire($katainWound);
                Assert::false($katainWound->canceled, 'used reaction does not take Katain');
            },
        ];
    }
}
