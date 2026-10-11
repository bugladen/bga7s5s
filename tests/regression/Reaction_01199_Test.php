<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01199;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01199;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01199_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01199';
    }

    /** @return array{0:_01199,1:Reaction_01199} */
    private function scene(TestWorld $world): array
    {
        $takama = $world->placeCharacter(new _01199(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01199 $reaction */
        $reaction = $takama->getReactions()[0];
        return [$takama, $reaction];
    }

    private function duelEnd(TestWorld $world, int $challengerId, int $defenderId): EventDuelEnd
    {
        $event = new EventDuelEnd();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'offers when a wounded friendly participant is at Takama\'s location' => function () {
                $world = new TestWorld();
                [$takama, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally->Wounds = 1;

                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($takama->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            // WHY (journal 2026-04-09-01): both participants can be valid — two independent ifs.
            'offers both heal buttons when both friendlies are wounded at location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Wounds = 1;
                $b->Wounds = 2;

                $reaction->handleEvent($this->duelEnd($world, $a->Id, $b->Id));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("healWound-{$a->Id}", $ids, true), 'challenger');
                Assert::true(in_array("healWound-{$b->Id}", $ids, true), 'defender');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'does not offer when the friendly participant has no wounds' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally->Wounds = 0;

                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'healthy');
            },

            'does not offer when the friendly participant is elsewhere' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $ally->Wounds = 1;

                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'wrong location');
            },

            'does not offer for an opposing wounded participant alone' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Wounds = 2;

                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'foe only');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally->Wounds = 1;
                $reaction->Used = true;

                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'self-heal works when Takama participated wounded at her location' => function () {
                $world = new TestWorld();
                [$takama, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $takama->Wounds = 1;

                $reaction->handleEvent($this->duelEnd($world, $takama->Id, $foe->Id));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'self');
            },

            'heal queues CharacterBeingHealed for 1 and marks Used' => function () {
                $world = new TestWorld();
                [$takama, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally->Wounds = 1;
                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "healWound-{$ally->Id}");

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($ally->Id, $heals[0]->characterId, 'target');
                Assert::same($takama->Id, $heals[0]->sourceId, 'source');
                Assert::same(1, $heals[0]->wounds, 'one');
                Assert::same($reaction->Id, $heals[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY (journal 2026-04-08-09 / 01199): pass must not consume the once-per-day.
            'pass clears targets without marking Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally->Wounds = 1;
                $reaction->handleEvent($this->duelEnd($world, $ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'dusk clears stored participant ids' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $challengerProp = new \ReflectionProperty(Reaction_01199::class, 'ChallengerId');
                $challengerProp->setAccessible(true);
                $defenderProp = new \ReflectionProperty(Reaction_01199::class, 'DefenderId');
                $defenderProp->setAccessible(true);
                $challengerProp->setValue($reaction, 11);
                $defenderProp->setValue($reaction, 22);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::same(0, $challengerProp->getValue($reaction), 'challenger cleared');
                Assert::same(0, $defenderProp->getValue($reaction), 'defender cleared');
            },
        ];
    }
}
