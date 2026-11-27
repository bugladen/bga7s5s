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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04cd29;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04cd29_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04cd29';
    }

    private function targeted(TestWorld $world, int $playerId, $target, $source, Action_04cd29 $action): EventCharacterTargeted
    {
        $event = new EventCharacterTargeted();
        $event->playerId = $playerId;
        $event->targetId = $target->Id;
        $event->sourceId = $source->Id;
        $event->abilityId = $action->Id;
        $event->batchId = 77;
        $event->canceled = false;
        $event->theah = $world->theah;
        return $event;
    }

    /**
     * @return array{0:_04cd29,1:Action_04cd29}
     */
    private function ready(TestWorld $world): array
    {
        $tijani = $world->placeCharacter(new _04cd29(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_04cd29 $action */
        $action = $tijani->getActions()[0];
        return [$tijani, $action];
    }

    public function tests(): array
    {
        return [
            'tijani is a negotiable hunter with this action and technique' => function () {
                $card = new _04cd29();
                Assert::instanceOf(CityCharacter::class, $card, 'CityCharacter');
                Assert::same(29, $card->CityCardNumber, 'city number');
                Assert::true($card->Negotiable, 'negotiable');
                Assert::same(3, $card->Finesse, 'finesse');
                Assert::true($card->hasTrait('Hunter'), 'Hunter');
                Assert::true($card->hasTrait('Pirate'), 'Pirate');
                Assert::instanceOf(Action_04cd29::class, $card->getActions()[0], 'action');
                Assert::instanceOf(Technique_04cd29::class, $card->getTechniques()[0], 'technique');
                Assert::same('Technique_04cd29', $card->getTechniques()[0]->ClassId, 'scoped id');
            },

            // WHY: En Garde is not an engage cost. Targets are engaged characters at an
            // adjacent city location with strictly lower Finesse. Allies qualify.
            // Equal Finesse, Home, and an uncontrolled Tijani do not.
            'available only while he is controlled, en garde, in the city, with a legal target' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $ally->Engaged = true;
                $target->Engaged = false;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ally qualifies');
                $target->Engaged = true;

                $target->ModifiedFinesse = 3;
                $ally->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'finesse not lower');
                $target->ModifiedFinesse = 1;
                $ally->Engaged = true;

                $target->Location = Game::LOCATION_CITY_DOCKS;
                $ally->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location');
                $target->Location = Game::LOCATION_CITY_FORUM;
                $ally->Location = Game::LOCATION_CITY_FORUM;

                $tijani->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'he is engaged');
                $tijani->Engaged = false;

                $tijani->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $tijani->Location = Game::LOCATION_CITY_DOCKS;

                $tijani->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'trigger opens the target choice' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04cd29', $transition->transition, 'state');
                Assert::same($tijani->Id, $transition->sourceId, 'tijani');
            },

            'args list the engaged lower-finesse characters next door' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $legal = $world->placeCharacter(new GenericCharacter('Legal'), Game::LOCATION_CITY_FORUM, 2);
                $legal->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Guarding'), Game::LOCATION_CITY_FORUM, 2);
                $bazaar = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $bazaar->Engaged = true;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD29, '');
                Assert::same($tijani->Id, $args['performerId'], 'performer');
                Assert::same([$legal->Id], $args['ids'], 'forum only');
            },

            'an illegal target is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $target->Engaged = true;
                [$valid, $message] = $action->isValidTargetForAbility($world->game, $target);
                Assert::false($valid, 'not adjacent');
                Assert::true($message !== '', 'reason');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD29, '', 99999);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            // WHY: pick queues ONLY CharacterTargeted so Cloud Bottle / UL / Maryam can
            // cancel before the wound exists (same shape as Bleed Out / Action_01160).
            'picking a legal target queues only CharacterTargeted' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD29, '', $target->Id);
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($target->Id, $targeted[0]->targetId, 'target');
                Assert::same($tijani->Id, $targeted[0]->sourceId, 'tijani');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::same(1, $targeted[0]->playerId, 'controller');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::same('targetChosen', $world->game->gamestate->transitions[0], 'transition');
            },

            'surviving CharacterTargeted wounds the target and resolves the action' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;

                $targeted = $this->targeted($world, 1, $target, $tijani, $action);
                $action->handleEvent($targeted);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($target->Id, $wounds[0]->characterId, 'target');
                Assert::same($tijani->Id, $wounds[0]->sourceId, 'tijani');
                Assert::same($action->Id, $wounds[0]->abilityId, 'action');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same(77, $wounds[0]->batchId, 'shared batch');
                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'controller');
            },

            'canceled CharacterTargeted queues no wound and no resolve' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;

                $targeted = $this->targeted($world, 1, $target, $tijani, $action);
                $targeted->canceled = true;
                $action->handleEvent($targeted);
                Assert::count(0, $world->theah->queuedEvents, 'cancelled');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;

                $targeted = $this->targeted($world, 1, $target, $tijani, $action);
                $targeted->abilityId = 'other';
                $action->handleEvent($targeted);
                Assert::count(0, $world->theah->queuedEvents, 'other ability');
            },

            // WHY: board can change between pick and the targeting hook resolving —
            // Finesse is re-checked on the surviving path, but the action still resolves.
            'surviving CharacterTargeted re-checks Finesse before wounding' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;

                $targeted = $this->targeted($world, 1, $target, $tijani, $action);
                $target->ModifiedFinesse = $tijani->ModifiedFinesse;
                $action->handleEvent($targeted);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'still resolves');
            },

            'controller 0 resolves as the active player' => function () {
                $world = new TestWorld();
                [$tijani, $action] = $this->ready($world);
                $tijani->ControllerId = 0;
                $world->game->activePlayerId = 2;
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
                $target->Engaged = true;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD29, '', $target->Id);
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                Assert::same(2, $targeted->playerId, 'active on targeting');

                $world->theah->queuedEvents = [];
                $action->handleEvent($targeted);
                Assert::same(2, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'active');
            },
        ];
    }
}
