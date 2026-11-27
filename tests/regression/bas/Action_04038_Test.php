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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;

class Action_04038_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04038';
    }

    public function tests(): array
    {
        return [
            // WHY: Academic city action. The performer does not need to be en garde.
            // A target that is both engaged and wounded gets both effects. A healthy
            // en garde character is not a target.
            'en gardes and heals a character at the performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04038(), Game::LOCATION_HAND, 1);
                $academic = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $academic->Engaged = true;
                $target = $world->placeCharacter(new GenericCharacter('Hurt'), Game::LOCATION_CITY_DOCKS, 2);
                $target->Engaged = true;
                $target->Wounds = 1;
                $healthy = $world->placeCharacter(new GenericCharacter('Fine'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04038 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $academic->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged academic still qualifies');
                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04038, '')['ids'];
                Assert::true(in_array($target->Id, $ids, true), 'hurt target');
                Assert::true(in_array($academic->Id, $ids, true), 'the performer is engaged, so he qualifies');
                Assert::false(in_array($healthy->Id, $ids, true), 'healthy en garde is skipped');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04038, '', $target->Id);

                // Pick queues ONLY CharacterTargeted (Cloud Bottle / UL can cancel it).
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($target->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(1, $world->theah->queuedEvents, 'nothing else at pick');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'to EVENTS');

                // Canceled hook: nothing. Foreign ability: ignored.
                $world->theah->queuedEvents = [];
                $hook = new EventCharacterTargeted();
                $hook->abilityId = $action->Id;
                $hook->targetId = $target->Id;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'cancel queues nothing');
                $hook->canceled = false;
                $hook->abilityId = 'other';
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'foreign ignored');

                // Survive: effects share the batch; ActionResolved follows.
                $hook->abilityId = $action->Id;
                $hook->batchId = 77;
                $action->handleEvent($hook);
                Assert::same($target->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'en garde');
                Assert::same(77, $world->theah->queuedOfType(EventCardEngarded::class)[0]->batchId, 'en garde batch');
                Assert::same(77, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->batchId, 'heal batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same($target->Id, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->characterId, 'heal');
                Assert::same(1, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->wounds, 'one wound');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04038, '', $healthy->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'healthy');
            },
        ];
    }
}
