<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02041_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02041';
    }

    /**
     * @return array{0:_02041,1:Maneuver_02041}
     */
    private function scene(TestWorld $world, bool $scoundrel = true): array
    {
        $risk = $world->placeCard(new _02041(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(
            new GenericCharacter('Actor', $scoundrel ? ['Scoundrel'] : ['Duelist']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $world->theah->duelActor = $actor;
        /** @var Maneuver_02041 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver];
    }

    public function tests(): array
    {
        return [
            'available only when the duel actor is a Scoundrel' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world, true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'scoundrel');

                $world->theah->duelActor = $world->placeCharacter(
                    new GenericCharacter('Duelist', ['Duelist']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not scoundrel');
            },

            // WHY: Resolve switches the duel stat for the rest of the duel. challengeStat is the
            // raw CSS key; duelStat is the translated label.
            'resolve makes the duel a duel of Finesse' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                $notes = array_values(array_filter(
                    $world->game->notify->messages,
                    fn ($m) => $m['type'] === 'duelStatChanged'
                ));
                Assert::count(1, $notes, 'notify');
                Assert::same($risk->getInjectCode(), $notes[0]['args']['card_inject_code'], 'card');
                Assert::same('Finesse', $notes[0]['args']['duelStat'], 'label');
                Assert::same(Game::STAT_FINESSE, $notes[0]['args']['challengeStat'], 'css');
            },

            'activation marks the maneuver used' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);

                $event = new EventManeuverActivated();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::true($maneuver->Used, 'used');
            },

            'ignores resolve of a different maneuver' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'other';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'unchanged');
                Assert::count(0, $world->game->notify->messages, 'silent');
            },
        ];
    }
}
