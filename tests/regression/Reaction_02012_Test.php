<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02012;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02012_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02012';
    }

    /**
     * @return array{0:_02012,1:Reaction_02012,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $turais = $world->placeCharacter(new _02012(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_02012 $reaction */
        $reaction = $turais->getReactions()[0];
        return [$turais, $reaction, $foe];
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02012(), 'CardReaction');
            },

            // WHY: Berserker + non-lethal (Wounds+1 < ModifiedResolve) when Turais challenges.
            'offers when Turais issues a challenge and wound would not be lethal' => function () {
                $world = new TestWorld();
                [$turais, $reaction, $foe] = $this->scene($world);

                $reaction->handleEvent($this->issued($world, $turais->Id, $foe->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($turais->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            'does not offer when another character issues the challenge' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->issued($world, $other->Id, $foe->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not Turais');
            },

            'does not offer when wound would be lethal' => function () {
                $world = new TestWorld();
                [$turais, $reaction, $foe] = $this->scene($world);
                // Resolve 4 → ModifiedResolve 4; Wounds 3 → 3+1 is not < 4.
                $turais->Wounds = 3;

                $reaction->handleEvent($this->issued($world, $turais->Id, $foe->Id));
                Assert::count(0, $world->theah->queuedEvents, 'lethal');
            },

            'does not offer without Berserker trait' => function () {
                $world = new TestWorld();
                [$turais, $reaction, $foe] = $this->scene($world);
                $turais->removeTrait($world->game, 'Berserker');

                $reaction->handleEvent($this->issued($world, $turais->Id, $foe->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no Berserker');
            },

            'woundAndEnGarde queues self-wound engage and sets Used' => function () {
                $world = new TestWorld();
                [$turais, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundAndEnGarde');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($turais->Id, $wounds[0]->characterId, 'self');
                Assert::same(1, $wounds[0]->wounds, 'one');

                // WHY: Reaction_02012 calls createCardEngagedEvent → EventCardEngarded
                // (past-tense "en garde"), not createCardEngagedEvent / EventCardEngaged.
                $engages = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($turais->Id, $engages[0]->cardId, 'self');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not wound or set Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no engage');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
