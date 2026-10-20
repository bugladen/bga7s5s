<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class Action_01090_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01090';
    }

    /** @return array{0:_01090,1:Action_01090,2:Reaction_01090} */
    private function scene(TestWorld $world): array
    {
        $lorenzo = $world->placeCharacter(new _01090(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01090 $action */
        $action = $lorenzo->getActions()[0];
        /** @var Reaction_01090 $reaction */
        $reaction = $lorenzo->getReactions()[0];
        return [$lorenzo, $action, $reaction];
    }

    private function triggered(TestWorld $world, Action_01090 $action): EventActionTriggered
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'available while the Reaction is unused' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: the Action is just a pre-activation of the Reaction, so it follows the Reaction's Used flag.
            'unavailable once the Reaction is used' => function () {
                $world = new TestWorld();
                [, $action, $reaction] = $this->scene($world);
                $reaction->setUsed($world->theah, true);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'reaction used');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'unavailable when Fate\'s Silence blanks Lorenzo' => function () {
                $world = new TestWorld();
                [$lorenzo, $action] = $this->scene($world);
                $lorenzo->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger sets the not-first-player override and grants one extra action' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->handleEvent($this->triggered($world, $action));

                Assert::true($world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER) === true, 'override');
                Assert::same(1, $world->game->globals->get(Game::EXTRA_ACTIONS), 'one extra action');
                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'overrideAsNotFirstPlayer');
                Assert::count(1, $notes, 'announced');
            },

            'trigger spends the Reaction' => function () {
                $world = new TestWorld();
                [, $action, $reaction] = $this->scene($world);

                $action->handleEvent($this->triggered($world, $action));

                Assert::true($reaction->Used, 'reaction used');
            },

            // WHY: "Always available to use" — the Action itself is never marked used, so Dusk/other resets are
            // the only thing gating a repeat (via the Reaction). A used Action would also lock out CardAction::isAvailable.
            'trigger leaves the Action itself unused' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->handleEvent($this->triggered($world, $action));

                Assert::false($action->Used, 'action not used');
            },

            'repeat is possible again after Dusk resets the Reaction' => function () {
                $world = new TestWorld();
                [, $action, $reaction] = $this->scene($world);
                $action->handleEvent($this->triggered($world, $action));
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'locked out after use');

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);
                $action->handleEvent($dusk);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available next day');
            },

            'trigger for a different action id is ignored' => function () {
                $world = new TestWorld();
                [, $action, $reaction] = $this->scene($world);

                $event = $this->triggered($world, $action);
                $event->actionId = 'someOtherAction';
                $action->handleEvent($event);

                Assert::false($reaction->Used, 'reaction untouched');
                Assert::same(null, $world->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER), 'no override');
                Assert::same(null, $world->game->globals->get(Game::EXTRA_ACTIONS), 'no extra action');
            },
        ];
    }
}
