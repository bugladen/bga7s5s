<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03018_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03018';
    }

    /**
     * @return array{0:_03018,1:Reaction_03018,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = ['Zealot']): array
    {
        $scheme = $world->placeCard(new _03018(), Game::LOCATION_PLAYER_HOME, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger', $traits), Game::LOCATION_CITY_DOCKS, 1);
        $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03018 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $challenger, $target];
    }

    private function refused(TestWorld $world, int $challengerId, int $targetId): EventChallengeRejected
    {
        $event = new EventChallengeRejected();
        $event->theah = $world->theah;
        $event->challengerId = $challengerId;
        $event->targetId = $targetId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03018(), 'card reaction');
            },

            'a refused Zealot or Hunter challenge wounds the refusing character' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $challenger, $target] = $this->scene($world);
                $reaction->handleEvent($this->refused($world, $challenger->Id, $target->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'zealot');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['wound', 'pass'], $buttons, 'buttons');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'wound');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($target->Id, $wound->characterId, 'refuser');
                Assert::same($scheme->Id, $wound->sourceId, 'scheme');
                Assert::true($reaction->Used, 'spent');

                $world = new TestWorld();
                [, $reaction, $challenger, $target] = $this->scene($world, ['Hunter', 'Hero']);
                $reaction->handleEvent($this->refused($world, $challenger->Id, $target->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'hunter');
            },

            'a civilian challenger, an opponent challenger, or pass does nothing' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $target] = $this->scene($world, ['Hero']);
                $reaction->handleEvent($this->refused($world, $challenger->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no zealot or hunter');

                $challenger->addTrait($world->game, 'Zealot', true);
                $challenger->ControllerId = 2;
                $reaction->handleEvent($this->refused($world, $challenger->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not your challenger');

                $world = new TestWorld();
                [, $reaction, $challenger, $target] = $this->scene($world);
                $reaction->handleEvent($this->refused($world, $challenger->Id, $target->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },
        ];
    }
}
