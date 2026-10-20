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

            'wounds a Brute twice and anyone else once' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , , $bystander] = $this->scene($world);
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_02057, 'duelResolveManeuver_02057', $bystander->Id);
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'once');
                Assert::same($bystander->Id, $wounds[0]->characterId, 'target');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
                Assert::same([null], $world->game->gamestate->transitions, 'empty transition');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                $bystander->addTrait($world->game, 'Brute');
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_02057, 'duelResolveManeuver_02057', $bystander->Id);
                Assert::count(2, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'brute again');
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
