<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Stash;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Stash;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_05Stash_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_05Stash';
    }

    /**
     * Covert duel at uncontrolled Docks: actor (p1) vs adversary (p2) with an en garde foe present.
     *
     * @return array{
     *   world: TestWorld,
     *   risk: _05Stash,
     *   maneuver: Maneuver_05Stash,
     *   actor: GenericCharacter,
     *   adversary: GenericCharacter,
     *   otherFoe: GenericCharacter
     * }
     */
    private function duel(): array
    {
        $world = new TestWorld();
        $world->game->globals->set(Game::IN_DUEL, true);
        $risk = $world->placeCard(new _05Stash(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        // Separate en garde opposing character — engage target pool (adversary may already be Engaged in a duel).
        $otherFoe = $world->placeCharacter(new GenericCharacter('Other Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        /** @var Maneuver_05Stash $maneuver */
        $maneuver = $risk->getManeuvers()[0];

        return compact('world', 'risk', 'maneuver', 'actor', 'adversary', 'otherFoe');
    }

    private function resolve(_05Stash $risk, Maneuver_05Stash $maneuver, TestWorld $world): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->playerId = 1;
        $world->fireOn($risk, $event);
    }

    public function tests(): array
    {
        return [
            // WHY: Covert + engage stick. Controlled City / Home / no engage targets grey it.
            'is only offered in a Covert duel when an opposing en garde character can be engaged' => function () {
                ['world' => $world, 'maneuver' => $maneuver, 'adversary' => $adversary, 'otherFoe' => $otherFoe] = $this->duel();

                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'controlled');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                // Engage every opposing character — no stick left for Decline.
                $otherFoe->Engaged = true;
                $adversary->Engaged = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'no en garde opposing');
                $otherFoe->Engaged = false;
                $adversary->Engaged = false;

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            // WHY: Attachment present → adversary chooser. Decline (id 0) → owner engage state.
            'lets the adversary discard an equipped Attachment, or Decline into an engage chooser' => function () {
                ['world' => $world, 'risk' => $risk, 'maneuver' => $maneuver, 'adversary' => $adversary, 'otherFoe' => $otherFoe] = $this->duel();

                $gear = $world->placeCard(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 2);
                $gear->AttachedToId = $adversary->Id;
                $adversary->Attachments = [$gear->Id];

                $this->resolve($risk, $maneuver, $world);
                Assert::same('05Stash', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'adversary chooser');
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'adversary active');

                $world->theah->takeQueuedEvents();
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_05STASH, '', 0);
                Assert::same('05Stash_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'engage after decline');
                Assert::same(1, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'owner active');

                $world->theah->takeQueuedEvents();
                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_05STASH_2, '');
                Assert::true(in_array($otherFoe->Id, $args['ids'], true), 'other foe engageable');
                Assert::true(in_array($adversary->Id, $args['ids'], true), 'adversary also en garde');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_05STASH_2, '', $otherFoe->Id);

                // Pick queues ONLY the targeting hook; the Engage waits for it to survive.
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($otherFoe->Id, $targeted[0]->targetId, 'hook target');
                Assert::same($maneuver->Id, $targeted[0]->abilityId, 'hook ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage beside pick');

                // WHY: Cloud Bottle / UL cancel the hook — nothing engages.
                $canceled = clone $targeted[0];
                $canceled->canceled = true;
                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $canceled);
                Assert::count(0, $world->theah->queuedEvents, 'canceled queues nothing');

                $foreign = clone $targeted[0];
                $foreign->abilityId = 'someone-else';
                $world->fireOn($risk, $foreign);
                Assert::count(0, $world->theah->queuedEvents, 'foreign ability ignored');

                $hook = $targeted[0];
                $hook->canceled = false;
                $world->fireOn($risk, $hook);
                $engage = $world->theah->queuedOfType(EventCardEngaged::class)[0];
                Assert::same($otherFoe->Id, $engage->cardId, 'engaged');
                Assert::same($hook->batchId, $engage->batchId, 'engage shares batch');

                // Fresh resolve: discard path unequips + discards from play.
                $world2 = new TestWorld();
                $world2->game->globals->set(Game::IN_DUEL, true);
                $risk2 = $world2->placeCard(new _05Stash(), Game::LOCATION_DUELING_LINE, 1);
                $actor2 = $world2->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $adv2 = $world2->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
                $world2->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $world2->theah->duelActor = $actor2;
                $world2->theah->duelOpponent = $adv2;
                /** @var Maneuver_05Stash $m2 */
                $m2 = $risk2->getManeuvers()[0];
                $gear2 = $world2->placeCard(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 2);
                $gear2->AttachedToId = $adv2->Id;
                $adv2->Attachments = [$gear2->Id];

                $this->resolve($risk2, $m2, $world2);
                $world2->theah->takeQueuedEvents();
                $m2->actFromManeuverWithId($world2->game, States::DUEL_RESOLVE_MANEUVER_05STASH, '', $gear2->Id);
                Assert::same($gear2->Id, $world2->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequip');
                Assert::same($gear2->Id, $world2->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0]->cardId, 'discard');
                Assert::count(0, $world2->theah->queuedOfType(EventTransition::class), 'no engage after discard');
            },

            // WHY: No Attachment ⇒ they did not discard ⇒ straight to owner engage chooser.
            'skips the discard chooser when the adversary has no Attachment' => function () {
                ['world' => $world, 'risk' => $risk, 'maneuver' => $maneuver] = $this->duel();

                $this->resolve($risk, $maneuver, $world);
                Assert::same('05Stash_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'direct engage');
                Assert::same(1, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'owner');
            },
        ];
    }
}
