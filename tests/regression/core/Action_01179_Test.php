<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01179;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01179;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromCard;

class Action_01179_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01179';
    }

    /**
     * Siren in city with Renown; unengaged performer at its location.
     *
     * @return array{0:_01179,1:Action_01179,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $reknown = 2): array
    {
        /** @var _01179 $siren */
        $siren = $world->placeCard(new _01179(), Game::LOCATION_CITY_DOCKS, 0);
        $siren->Reknown = $reknown;
        $performer = $world->placeCharacter(new GenericCharacter('Singer'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Engaged = false;
        /** @var Action_01179 $action */
        $action = $siren->getActions()[0];
        return [$siren, $action, $performer];
    }

    /** @return list<int> */
    private function getPlayersUsed(Action_01179 $action): array
    {
        $prop = new \ReflectionProperty(Action_01179::class, 'playersUsed');
        $prop->setAccessible(true);
        /** @var list<int> $list */
        $list = $prop->getValue($action);
        return $list;
    }

    private function setPlayersUsed(Action_01179 $action, array $ids): void
    {
        $prop = new \ReflectionProperty(Action_01179::class, 'playersUsed');
        $prop->setAccessible(true);
        $prop->setValue($action, $ids);
    }

    public function tests(): array
    {
        return [
            'is an EventCityAction that requires a performer' => function () {
                $action = new Action_01179();
                Assert::instanceOf(EventCityAction::class, $action, 'EventCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with Renown, unused today, and an unengaged performer here' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the card has no Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty');
            },

            'unavailable after the player has used it today' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $this->setPlayersUsed($action, [1]);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            // WHY (journal 2026-05-11-01): isAvailable must call getPerformersForAction so an
            // only-engaged performer cannot open the chooser into a stuck empty state.
            'unavailable when every performer at the location is engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'all engaged');
            },

            'unavailable with no characters at the location' => function () {
                $world = new TestWorld();
                $siren = $world->placeCard(new _01179(), Game::LOCATION_CITY_DOCKS, 0);
                $siren->Reknown = 1;
                /** @var Action_01179 $action */
                $action = $siren->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody');
            },

            'performers exclude engaged characters at the location' => function () {
                $world = new TestWorld();
                [, $action, $free] = $this->scene($world);
                $engaged = $world->placeCharacter(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$free->Id], array_values($ids), 'only free here');
                Assert::false(in_array($engaged->Id, $ids, true), 'engaged out');
                Assert::false(in_array($far->Id, $ids, true), 'far out');
            },

            // WHY (journal 2026-03-28-04): per-player playersUsed — not the global Used flag —
            // so each player may activate once per Day. setUsed(false) clears central confirm.
            'trigger engages, removes Renown from card, gains Renown, resolves, marks player used' => function () {
                $world = new TestWorld();
                [$siren, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');
                Assert::same($siren->Id, $engages[0]->sourceId, 'source');

                $removed = $world->theah->queuedOfType(EventReknownRemovedFromCard::class);
                Assert::count(1, $removed, 'remove');
                Assert::same($siren->Id, $removed[0]->cardId, 'from card');
                Assert::same(1, $removed[0]->amount, 'one');

                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'gain');
                Assert::same(1, $gains[0]->playerId, 'player');
                Assert::same(1, $gains[0]->amount, 'amount');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([1], $this->getPlayersUsed($action), 'playersUsed');
                Assert::false($action->Used, 'global Used cleared');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventCardEngaged::class, $order, true)
                        < array_search(EventReknownRemovedFromCard::class, $order, true),
                    'engage before remove'
                );
                Assert::true(
                    array_search(EventReknownRemovedFromCard::class, $order, true)
                        < array_search(EventPlayerGainsReknown::class, $order, true),
                    'remove before gain'
                );
            },

            'eventCheck refuses a second use by the same player the same Day' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $this->setPlayersUsed($action, [1]);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $action->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            'Dusk clears playersUsed' => function () {
                $world = new TestWorld();
                [$siren, $action] = $this->scene($world);
                $this->setPlayersUsed($action, [1, 2]);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $action->handleEvent($dusk);

                Assert::same([], $this->getPlayersUsed($action), 'cleared');
                Assert::true($siren->IsUpdated, 'dirty');
            },

            'getUsedListData shapes UI rows' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $this->setPlayersUsed($action, [1]);

                $data = $action->getUsedListData($world->game);
                Assert::count(1, $data, 'one');
                Assert::same(1, $data[0]['playerId'], 'id');
                Assert::same('Player One', $data[0]['playerName'], 'name');
                Assert::true(isset($data[0]['playerColor']), 'color');
            },
        ];
    }
}
