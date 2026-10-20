<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01074;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01071;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01071_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01071';
    }

    /** @return array{0:_01071,1:GenericCharacter,2:GenericCharacter} scheme, challenger Musketeer, defender */
    private function armDuel(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
        /** @var GenericCharacter $musketeer */
        $musketeer = $world->placeCharacter(new GenericCharacter('Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
        /** @var GenericCharacter $foe */
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $musketeer->addCondition(Game::DUEL_CHALLENGER);
        $foe->addCondition(Game::DUEL_DEFENDER);

        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::CHALLENGE_TYPE, Game::EPEE_SANGLANTE_CHALLENGE_TYPE);
        $world->theah->duelActor = $musketeer;
        $world->theah->duelOpponent = $foe;
        $world->game->setPlayerReknown(1, 5);
        $world->game->setPlayerReknown(2, 5);

        return [$scheme, $musketeer, $foe];
    }

    private function wound(TestWorld $world, int $characterId, int $sourceId, string $abilityId = ''): EventCharacterWounded
    {
        $event = new EventCharacterWounded();
        $event->characterId = $characterId;
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->wounds = 1;
        $event->reason = 'test';
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // --- availability / performers / targets ---

            'available with a Musketeer in the city' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Musketeer (non-Musketeer in city does not count)' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no musketeer');
            },

            'unavailable when scheme is not at Player Home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_CITY_DISCARD, 1);
                $world->placeCharacter(new GenericCharacter('Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not at home');
            },

            'getPerformersForAction returns only Musketeers' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $musketeer = $world->placeCharacter(new GenericCharacter('Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($musketeer->Id, $ids, true), 'musketeer');
                Assert::false(in_array($plain->Id, $ids, true), 'plain excluded');
            },

            'isValidTargetForAbility rejects ally and off-location foe, accepts local foe' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $musketeer = $world->placeCharacter(new GenericCharacter('Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $musketeer->Id);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                Assert::false($action->isValidTargetForAbility($world->game, $ally)[0], 'ally');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'local foe');
            },

            'trigger sets Combat challenge of type EPEE_SANGLANTE and queues transition 01071' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::EPEE_SANGLANTE_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'challenge type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat stat');
                Assert::same('01071', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // --- first-wound Renown steal ---

            'first wound by challenger against adversary steals 1 Renown' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));

                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                $loses = $world->theah->queuedOfType(EventPlayerLosesReknown::class);
                Assert::count(1, $gains, 'gain');
                Assert::count(1, $loses, 'lose');
                Assert::same(1, $gains[0]->playerId, 'thief gains');
                Assert::same(2, $loses[0]->playerId, 'victim loses');
                Assert::true($action->firstWoundOccured, 'flag set');
            },

            // WHY: "first participant to wound their adversary" — either side can be first.
            'first wound by defender against challenger steals from the challenger' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                $world->theah->duelOpponent = $musketeer; // stub returns the aggressor's adversary
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $musketeer->Id, $foe->Id));

                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'gain');
                Assert::same(2, $gains[0]->playerId, 'defender controller gains');
                Assert::same(1, $world->theah->queuedOfType(EventPlayerLosesReknown::class)[0]->playerId, 'challenger loses');
            },

            'second wound in the same duel does not steal again' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));
                $world->theah->takeQueuedEvents();
                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));

                Assert::count(0, $world->theah->queuedEvents, 'no second steal');
            },

            'duel end resets the first-wound flag' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];
                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));
                Assert::true($action->firstWoundOccured, 'precondition');

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $action->handleEvent($end);

                Assert::false($action->firstWoundOccured, 'reset');
            },

            'no Renown to steal still consumes the first wound' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                $world->game->setPlayerReknown(2, 0);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'nothing to gain');
                Assert::true($action->firstWoundOccured, 'flag consumed');
            },

            // WHY: self-wounds / non-adversary wounds must neither steal nor burn the first-wound flag.
            'self-inflicted wound does not steal or consume the flag' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer] = $this->armDuel($world);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $musketeer->Id, $musketeer->Id));

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no steal');
                Assert::false($action->firstWoundOccured, 'flag unset');
            },

            'wound by non-participant source does not steal or consume the flag' => function () {
                $world = new TestWorld();
                [$scheme, , $foe] = $this->armDuel($world);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $bystander->Id));

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no steal');
                Assert::false($action->firstWoundOccured, 'flag unset');
            },

            'attachment technique source resolves to its owning participant via abilityId' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                $rapier = $world->placeCard(new _01074(), Game::LOCATION_CITY_DOCKS, 1);
                $rapier->AttachedToId = $musketeer->Id;
                $musketeer->Attachments[] = $rapier->Id;
                $techniqueId = $rapier->getTechniques()[0]->Id;
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $rapier->Id, $techniqueId));

                Assert::count(1, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'steal');
                Assert::true($action->firstWoundOccured, 'flag set');
            },

            'non-character source with controller maps to that controller\'s duel participant' => function () {
                $world = new TestWorld();
                [$scheme, , $foe] = $this->armDuel($world);
                // Risk-style source: not a Character, no ability id, controlled by player 1.
                $risk = $world->placeCard(new _01074(), Game::LOCATION_HAND, 1);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $risk->Id));

                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'steal');
                Assert::same(1, $gains[0]->playerId, 'challenger controller gains');
            },

            'no steal outside an Épée Sanglante challenge' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));

                Assert::count(0, $world->theah->queuedEvents, 'normal challenge');
                Assert::false($action->firstWoundOccured, 'flag unset');
            },

            'no steal when not in a duel' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not in duel');
            },

            'no steal when scheme is no longer at Player Home' => function () {
                $world = new TestWorld();
                [$scheme, $musketeer, $foe] = $this->armDuel($world);
                $scheme->Location = Game::LOCATION_CITY_DISCARD;
                /** @var Action_01071 $action */
                $action = $scheme->getActions()[0];

                $action->handleEvent($this->wound($world, $foe->Id, $musketeer->Id));

                Assert::count(0, $world->theah->queuedEvents, 'scheme gone');
            },
        ];
    }
}
