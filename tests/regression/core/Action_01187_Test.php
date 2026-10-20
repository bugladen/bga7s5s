<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01187;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01187;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** Unattached city attachment for Smuggled Item availability / city-source gates. */
final class TestCityAttachment_01187 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Test City Attachment';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->WealthCost = 1;
        $this->resetCard();
    }
}

class Action_01187_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01187';
    }

    /**
     * Smuggled Item equipped on host; optional hand attachment for availability.
     *
     * @return array{0:_01187,1:Action_01187,2:GenericCharacter}
     */
    private function scene(TestWorld $world, bool $withHandAttachment = true): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $item = $world->placeCard(new _01187(), Game::LOCATION_CITY_DOCKS, 1);
        $item->AttachedToId = $host->Id;
        $host->Attachments[] = $item->Id;

        if ($withHandAttachment) {
            $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
        }

        /** @var Action_01187 $action */
        $action = $item->getActions()[0];
        return [$item, $action, $host];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction' => function () {
                Assert::instanceOf(AttachmentAction::class, new Action_01187(), 'AttachmentAction');
            },

            'available when an attachment is in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'hand');
            },

            // WHY (journal 2026-08-29-01): city-wide unattached attachments also gate availability.
            'available when an unattached attachment is in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->placeCard(new TestCityAttachment_01187(), Game::LOCATION_CITY_FORUM, 0);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city');
            },

            'unavailable with no hand or city attachments' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'none');
            },

            'unavailable when not attached' => function () {
                $world = new TestWorld();
                $item = $world->placeCard(new _01187(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                /** @var Action_01187 $action */
                $action = $item->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            'unavailable to the non-controller' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'other player');
            },

            // WHY (journal 2026-04-09-03): passes off to equip chooser — no ActionResolved here.
            'trigger sets performer, equip type, smuggled id, and queues 01187 (no ActionResolved)' => function () {
                $world = new TestWorld();
                [$item, $action, $host] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same($host->Id, (int) $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::same(Game::SMUGGLED_ITEM_EQUIP_TYPE, (int) $world->game->globals->get(Game::EQUIP_TYPE), 'equip type');
                Assert::same($item->Id, (int) $world->game->globals->get(Game::SMUGGLED_ITEM_ATTACHMENT_ID), 'smuggled id');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01187', $transitions[0]->transition, 'name');
                Assert::same($item->Id, $transitions[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolve yet');
            },
        ];
    }
}
