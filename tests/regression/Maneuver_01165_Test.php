<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04054b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01165_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01165';
    }

    /** @return array{0:GenericCharacter,1:GenericCharacter,2:_04054} */
    private function sabreOnAdversary(TestWorld $world): array
    {
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 2);
        $sabre->AttachedToId = $foe->Id;
        $foe->Attachments[] = $sabre->Id;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        return [$actor, $foe, $sabre];
    }

    private function engageTechnique(_04054 $sabre): Technique_04054b
    {
        foreach ($sabre->getTechniques() as $technique) {
            if ($technique instanceof Technique_04054b) {
                return $technique;
            }
        }
        throw new \RuntimeException('Technique_04054b not found');
    }

    public function tests(): array
    {
        return [
            // WHY: Technique_04054b gates isAvailableToPlayer on actor==owner. Adversary
            // sabre fails that while Trick's player is actor — must still list both techs.
            'available and lists both Sabre techniques despite actor-gate on Engage' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [, , $sabre] = $this->sabreOnAdversary($world);

                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01165, 'duelResolveManeuver_01165');
                Assert::count(2, $args['techniques'], 'both sabre techniques');

                $ids = array_column($args['techniques'], 'id');
                foreach ($sabre->getTechniques() as $technique)
                {
                    Assert::true(in_array($technique->Id, $ids, true), $technique->Id);
                }

                foreach ($args['techniques'] as $props)
                {
                    Assert::true(isset($props['id'], $props['name'], $props['Id'], $props['Name']), 'dual-case keys');
                    Assert::same($props['id'], $props['Id'], 'Id mirrors id');
                    Assert::same($props['name'], $props['Name'], 'Name mirrors name');
                    Assert::true($props['name'] !== '', 'label not empty');
                }
            },

            'unavailable when not in a duel' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                $this->sabreOnAdversary($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable when the adversary has no techniques' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'bare foe');
            },

            // WHY: copied Engage technique must not engage the Character host (effects only)
            'copied Engage Riposte does not queue CardEngaged' => function () {
                $world = new TestWorld();
                [, , $sabre] = $this->sabreOnAdversary($world);
                $engage = $this->engageTechnique($sabre);

                $event = new EventResolveTechnique();
                $event->techniqueId = $engage->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $engage->handleEvent($event);
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'normal engage cost');

                $world->theah->takeQueuedEvents();
                $engage->IsTemporaryCopy = true;
                $engage->handleEvent($event);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'copy skips engage');
            },

            // WHY: transition must drain last so other resolve events finish before state change.
            'resolve queues LOWEST_PRIORITY transition 01165' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [, , $sabre] = $this->sabreOnAdversary($world);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01165', $transitions[0]->transition, 'name');
                Assert::same(Event::LOWEST_PRIORITY, $transitions[0]->priority, 'lowest');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY (journal 2026-04-01-10 / 2026-10-07-04): copy Id is actorId_copy_ClassId,
            // IsTemporaryCopy, not main technique; activate/resolve/threat package.
            'act clones technique onto actor and queues activate/resolve/threat' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [$actor, , $sabre] = $this->sabreOnAdversary($world);
                $engage = $this->engageTechnique($sabre);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01165,
                    'duelResolveManeuver_01165',
                    [$engage->Id]
                );

                $expectedCopyId = $actor->Id . '_copy_' . $engage->ClassId;
                Assert::same($expectedCopyId, $world->game->globals->get(Game::CHOSEN_TECHNIQUE), 'chosen');
                Assert::false($world->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'not main');
                Assert::count(1, $maneuver->copiedTechniques, 'tracked');
                Assert::true($maneuver->copiedTechniques[0]->IsTemporaryCopy, 'temp copy');
                Assert::same($expectedCopyId, $maneuver->copiedTechniques[0]->Id, 'copy id');
                Assert::true($risk->IsUpdated, 'risk dirty');

                Assert::true($actor instanceof IHasTechniques, 'actor techniques');
                $onActor = $actor->getTechniqueById($expectedCopyId);
                Assert::true($onActor !== null, 'copy on actor');
                Assert::true($onActor->IsTemporaryCopy, 'temp on actor');

                $activated = $world->theah->queuedOfType(EventTechniqueActivated::class);
                Assert::count(1, $activated, 'activate');
                Assert::true($activated[0]->copied, 'copied flag');
                Assert::same($expectedCopyId, $activated[0]->techniqueId, 'activate id');
                Assert::count(1, $world->theah->queuedOfType(EventResolveTechnique::class), 'resolve');
                Assert::count(1, $world->theah->queuedOfType(EventDuelCalculateTechniqueValues::class), 'threat');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'named');
            },

            'args skip IsTemporaryCopy techniques' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [, , $sabre] = $this->sabreOnAdversary($world);
                $engage = $this->engageTechnique($sabre);
                $engage->IsTemporaryCopy = true;
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                $args = $maneuver->getArgsFromManeuver(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01165,
                    'x'
                );

                Assert::count(1, $args['techniques'], 'only non-temp');
                Assert::false(in_array($engage->Id, array_column($args['techniques'], 'id'), true), 'temp hidden');
            },

            // WHY (journal 2026-04-01-10): DuelEnd must set IsUpdated or stale copiedTechniques
            // can persist when the duel ends without a NewRound flush.
            'EventDuelEnd clears copied techniques and marks Risk updated' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [$actor, , $sabre] = $this->sabreOnAdversary($world);
                $engage = $this->engageTechnique($sabre);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01165,
                    'x',
                    [$engage->Id]
                );
                $world->theah->takeQueuedEvents();
                $risk->IsUpdated = false;

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::same([], $maneuver->copiedTechniques, 'cleared');
                Assert::true($risk->IsUpdated, 'dirty');
                Assert::true($actor->getTechniqueById($actor->Id . '_copy_' . $engage->ClassId) === null, 'removed from actor');
            },

            'EventDuelNewRound clears copies for the Risk controller' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [, , $sabre] = $this->sabreOnAdversary($world);
                $engage = $this->engageTechnique($sabre);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01165,
                    'x',
                    [$engage->Id]
                );
                $world->theah->takeQueuedEvents();

                $round = new EventDuelNewRound();
                $round->playerId = 1;
                $round->theah = $world->theah;
                $maneuver->handleEvent($round);

                Assert::same([], $maneuver->copiedTechniques, 'cleared');
            },

            'EventManeuverCanceled clears copied techniques' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [, , $sabre] = $this->sabreOnAdversary($world);
                $engage = $this->engageTechnique($sabre);
                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                $maneuver->actFromManeuverWithIds(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_01165,
                    'x',
                    [$engage->Id]
                );
                $world->theah->takeQueuedEvents();

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::same([], $maneuver->copiedTechniques, 'cleared');
            },

            'state constant registered' => function () {
                Assert::same(52501165, States::DUEL_RESOLVE_MANEUVER_01165, 'maneuver state');
            },
        ];
    }
}
