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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04032_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04032';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde city action. The opponent may reveal and discard to stop the
            // move. Pass opens the adjacent-location move, engage false, for both cards.
            // Discard is an effect. An empty hand cannot reveal.
            'moves both unless the opponent discards a revealed card' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $giacinto = $world->placeCharacter(new _04032(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $hand = $world->placeCard(new _04027(), Game::LOCATION_HAND, 2);
                /** @var Action_04032 $action */
                $action = $giacinto->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04032, '', $foe->Id);
                $targetMsgs = array_filter(
                    $world->game->notify->messages,
                    fn($n) => ($n['type'] ?? '') === 'message'
                        && str_contains((string) ($n['message'] ?? ''), 'targeting')
                );
                Assert::true(count($targetMsgs) >= 1, 'chat names target on pick');
                $targetMsg = array_values($targetMsgs)[0];
                Assert::same($foe->getInjectCode(), $targetMsg['args']['character_inject_code'], 'target in log');
                Assert::same($giacinto->getInjectCode(), $targetMsg['args']['card_inject_code'], 'card in log');

                // Pick queues ONLY the targeting hook; target + opponent prompt wait for survival.
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'hook target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'hook ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no prompt beside pick');
                Assert::true($world->game->globals->get(Game::CHOSEN_TARGET) !== $foe->Id, 'target not stored at pick');

                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target stored on survive');
                Assert::same('04032_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'their response');
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'opponent acts');
                Assert::same($hook->batchId, $world->theah->queuedOfType(EventTransition::class)[0]->batchId, 'prompt shares batch');

                $world->game->currentPlayerId = 2;
                $action->actFromActionRevealHand($world->game, States::HIGH_DRAMA_PLAYER_TURN_04032_2);
                $revealed = json_decode((string) $world->game->globals->get(Game::REVEALED_CARDS), true);
                Assert::same([$hand->Id], $revealed, 'hand revealed');
                Assert::same('04032_4', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'show Giacinto');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04032_5, '', $hand->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::same($hand->Id, $discard->cardId, 'discarded');
                Assert::true($discard->asEffect, 'effect discard');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved without moving');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::same(null, $world->game->globals->get(Game::REVEALED_CARDS, null), 'cleared');

                $world->theah->takeQueuedEvents();
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04032_2);
                Assert::same('04032_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'location pick');
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04032_3, '', [Game::LOCATION_CITY_FORUM]);
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same($giacinto->Id, $moves[0]->cardId, 'Giacinto');
                Assert::same($foe->Id, $moves[1]->cardId, 'target');
                Assert::false($moves[0]->engage, 'not engaged');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'forum');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04032_3, '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bazaar is not adjacent');

                $giacinto->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            // WHY: Cloud Bottle / UL cancel the hook — no prompt and no stale CHOSEN_TARGET.
            'canceled CharacterTargeted queues nothing and stores no target' => function () {
                $world = new TestWorld();
                $giacinto = $world->placeCharacter(new _04032(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04032 $action */
                $action = $giacinto->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::true($world->game->globals->get(Game::CHOSEN_TARGET) !== $foe->Id, 'no stale target');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $giacinto = $world->placeCharacter(new _04032(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04032 $action */
                $action = $giacinto->getActions()[0];
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
