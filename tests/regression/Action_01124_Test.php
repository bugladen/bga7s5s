<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01124_Test extends TestCase
{
    private const DISCARD = 'Discard-1';

    public function name(): string
    {
        return 'Action_01124';
    }

    /**
     * Ved'ma in city with a playable Sorcery (_01076) in discard.
     *
     * @return array{0:_01124,1:Action_01124,2:_01076}
     */
    private function scene(TestWorld $world): array
    {
        $vedma = $world->placeCharacter(new _01124(), Game::LOCATION_CITY_DOCKS, 1);
        $sorcery = $world->placeCard(new _01076(), self::DISCARD, 1);
        /** @var Action_01124 $action */
        $action = $vedma->getActions()[0];
        return [$vedma, $action, $sorcery];
    }

    public function tests(): array
    {
        return [
            'is a Sorcerer ability that targets cards' => function () {
                $action = new Action_01124();
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $action, 'targets cards');
            },

            'available with Sorcerer trait and a Sorcery in discard' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Ved\'ma is engaged' => function () {
                $world = new TestWorld();
                [$vedma, $action] = $this->scene($world);
                $vedma->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable without a Sorcery in discard' => function () {
                $world = new TestWorld();
                [$vedma, $action, $sorcery] = $this->scene($world);
                $sorcery->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty discard');
            },

            'unavailable without Sorcerer trait' => function () {
                $world = new TestWorld();
                [$vedma, $action] = $this->scene($world);
                $vedma->ModifiedTraits = array_values(array_filter(
                    $vedma->ModifiedTraits,
                    fn($t) => $t !== 'Sorcerer'
                ));
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'lost Sorcerer');
            },

            'trigger engages Ved\'ma then queues the 01124 chooser' => function () {
                $world = new TestWorld();
                [$vedma, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage cost');
                Assert::same($vedma->Id, $engages[0]->cardId, 'Ved\'ma');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('01124', $transitions[0]->transition, 'name');
                Assert::same($vedma->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: cancel after engage refunds En Garde + extra action and un-Uses the Action.
            'pass from chooser en gardes, refunds Used, grants an extra action, and resolves' => function () {
                $world = new TestWorld();
                [$vedma, $action] = $this->scene($world);
                $action->Used = true;

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01124);

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($vedma->Id, $engardes[0]->cardId, 'Ved\'ma');
                Assert::false($action->Used, 'refunded');
                Assert::same(1, $world->game->globals->get(Game::EXTRA_ACTIONS), 'extra action');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'args list Sorcery discard cards and their available actions' => function () {
                $world = new TestWorld();
                [$vedma, $action, $sorcery] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01124,
                    'highDramaPlayerTurn_01124'
                );

                Assert::same($vedma->Id, $args['performerId'], 'performer');
                Assert::count(1, $args['cards'], 'one Sorcery');
                Assert::same($sorcery->Id, $args['cards'][0]['id'], 'discard Sorcery');
                Assert::true(count($args['actions']) >= 1, 'at least one action');
            },
        ];
    }
}
