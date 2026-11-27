<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02057;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_02057_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02057';
    }

    /**
     * @return array{0:_02057,1:Maneuver_02057,2:GenericCharacter,3:GenericCharacter,4:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02057(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02057 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $adversary, $bystander];
    }

    private function targeted(TestWorld $world, Maneuver_02057 $maneuver, GenericCharacter $target, _02057 $risk, int $batchId): EventCharacterTargeted
    {
        $event = new EventCharacterTargeted();
        $event->abilityId = $maneuver->Id;
        $event->targetId = $target->Id;
        $event->sourceId = $risk->Id;
        $event->batchId = $batchId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'targets characters' => function () {
                $maneuver = new Maneuver_02057();
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $maneuver, 'targets');
                Assert::same(52502057, States::DUEL_RESOLVE_MANEUVER_02057, 'state');
            },

            'available in a duel when someone at the location is not a participant' => function () {
                $world = new TestWorld();
                [, $maneuver, , , $bystander] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'bystander');
                $bystander->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'only the participants remain');
                $bystander->Location = Game::LOCATION_CITY_DOCKS;
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duel');
            },

            'resolve opens the wound chooser' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->scene($world);
                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('02057', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'maneuver');
            },

            'args omit both participants' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary, $bystander] = $this->scene($world);
                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_02057, 'duelResolveManeuver_02057');
                Assert::same([$bystander->Id], $args['characterIds'], 'bystander');
                Assert::false(in_array($actor->Id, $args['characterIds'], true), 'actor');
                Assert::false(in_array($adversary->Id, $args['characterIds'], true), 'adversary');
            },

            // WHY: Availability hides uncontrolled characters. The click check does not.
            'an uncontrolled bystander is a legal target and is omitted from the scan' => function () {
                $world = new TestWorld();
                [, $maneuver, , , $bystander] = $this->scene($world);
                $bystander->ControllerId = 0;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'scan skips them');
                [$valid] = $maneuver->isValidTargetForAbility($world->game, $bystander);
                Assert::true($valid, 'click accepts them');
            },

            'rejects a participant or a character at another location' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary, $bystander] = $this->scene($world);
                [$actorOk] = $maneuver->isValidTargetForAbility($world->game, $actor);
                [$adversaryOk] = $maneuver->isValidTargetForAbility($world->game, $adversary);
                Assert::false($actorOk, 'actor');
                Assert::false($adversaryOk, 'adversary');
                $bystander->Location = Game::LOCATION_CITY_FORUM;
                [$away] = $maneuver->isValidTargetForAbility($world->game, $bystander);
                Assert::false($away, 'location');
            },

            // WHY: pick queues ONLY CharacterTargeted so Cloud Bottle / UL / Maryam can cancel
            // before any wound is emitted.
            'pick queues only EventCharacterTargeted' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , , $bystander] = $this->scene($world);
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_02057, 'duelResolveManeuver_02057', $bystander->Id);

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($bystander->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
                Assert::same([null], $world->game->gamestate->transitions, 'empty transition');
            },

            'surviving CharacterTargeted wounds a Brute twice and anyone else once, sharing the batch' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , , $bystander] = $this->scene($world);
                $maneuver->handleEvent($this->targeted($world, $maneuver, $bystander, $risk, 41));
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'once');
                Assert::same($bystander->Id, $wounds[0]->characterId, 'target');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
                Assert::same(41, $wounds[0]->batchId, 'shares batch');

                $world->theah->takeQueuedEvents();
                $bystander->addTrait($world->game, 'Brute');
                $maneuver->handleEvent($this->targeted($world, $maneuver, $bystander, $risk, 42));
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'brute again');
                Assert::same(42, $wounds[0]->batchId, 'first shares batch');
                Assert::same(42, $wounds[1]->batchId, 'second shares batch');
            },

            'canceled or foreign CharacterTargeted queues nothing' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , , $bystander] = $this->scene($world);

                $canceled = $this->targeted($world, $maneuver, $bystander, $risk, 5);
                $canceled->canceled = true;
                $maneuver->handleEvent($canceled);
                Assert::count(0, $world->theah->queuedEvents, 'canceled');

                $other = $this->targeted($world, $maneuver, $bystander, $risk, 6);
                $other->abilityId = 'other';
                $maneuver->handleEvent($other);
                Assert::count(0, $world->theah->queuedEvents, 'other ability');
            },

            'rejects a missing character or a participant' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->scene($world);
                foreach ([999, $actor->Id] as $id) {
                    $threw = false;
                    try {
                        $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_02057, 'duelResolveManeuver_02057', $id);
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, (string) $id);
                }
                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
