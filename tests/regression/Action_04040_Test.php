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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;

class Action_04040_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04040';
    }

    public function tests(): array
    {
        return [
            // WHY: Academic city action. A fake attachment is not a real target. The
            // attachment click only queues the targeting hook. Unequip, locker, and the
            // wound emit when that hook is not canceled, so a cancel stops all three.
            'lockers a real attachment and wounds only if targeting survives' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04040(), Game::LOCATION_HAND, 1);
                $academic = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $bow->AttachedToId = $foe->Id;
                $fake = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $fake->FakeAttachment = true;
                $fake->AttachedToId = $foe->Id;
                $foe->Attachments = [$bow->Id, $fake->Id];
                /** @var Action_04040 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $academic->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'real attachment');
                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04040, '')['ids'];
                Assert::same([$foe->Id], $ids, 'the fake one does not disqualify the real one');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04040, '', $foe->Id);
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'target click waits');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterTargeted::class), 'not targeted yet');

                $listed = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04040_2, '')['attachments'];
                Assert::same([$bow->Id], array_column($listed, 'id'), 'fake hidden');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04040_2, '', $fake->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'fake attachment');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04040_2, '', $bow->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterTargeted::class), 'hook only');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'not lockered yet');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                $targeted->canceled = true;
                $world->theah->takeQueuedEvents();
                $action->handleEvent($targeted);
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'cancel stops unequip');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'and the wound');

                $targeted->canceled = false;
                $action->handleEvent($targeted);
                Assert::same($bow->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequip');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardSentToLocker::class)[0]->cardId, 'locker');
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same($targeted->batchId, $world->theah->queuedOfType(EventCardSentToLocker::class)[0]->batchId, 'shared batch');
            },
        ];
    }
}
