<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03047b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelAttemptGamble;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03047b_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03047b';
    }

    /**
     * @return array{0:Maneuver_03047b,1:GenericCharacter,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03047(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        /** @var Maneuver_03047b $maneuver */
        $maneuver = $risk->getManeuvers()[1];
        return [$maneuver, $actor, $adversary];
    }

    private function gamble(TestWorld $world, int $actorId): EventDuelAttemptGamble
    {
        $event = new EventDuelAttemptGamble();
        $event->theah = $world->theah;
        $event->actorId = $actorId;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: The lock lasts through the adversary's next round. Their end of round
            // does not clear it. It clears when our controller is the round actor again,
            // on cancel, and when the duel ends. The check is eventCheck, which tests call
            // directly because the harness does not walk cards.
            'the adversary cannot gamble until our controller acts again' => function () {
                $world = new TestWorld();
                [$maneuver, $actor, $adversary] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'duelist');
                $actor->addTrait($world->game, 'Hero', true);
                $actor->removeTrait($world->game, 'Duelist');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $actor->addTrait($world->game, 'Duelist', true);

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::true($maneuver->CancelAdversaryGamble, 'armed');

                $own = $this->gamble($world, $actor->Id);
                $maneuver->eventCheck($own);
                Assert::true(true, 'our gamble is fine');

                $threw = false;
                try {
                    $maneuver->eventCheck($this->gamble($world, $adversary->Id));
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'adversary blocked');

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $end->actorId = $adversary->Id;
                $maneuver->handleEvent($end);
                Assert::true($maneuver->CancelAdversaryGamble, 'their round ending does not lift it');

                $round = new EventDuelNewRound();
                $round->theah = $world->theah;
                $round->actorId = $actor->Id;
                $maneuver->handleEvent($round);
                Assert::false($maneuver->CancelAdversaryGamble, 'our controller is the actor');
                $maneuver->eventCheck($this->gamble($world, $adversary->Id));

                $maneuver->handleEvent($resolve);
                $cancel = new EventManeuverCanceled();
                $cancel->theah = $world->theah;
                $cancel->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($cancel);
                Assert::false($maneuver->CancelAdversaryGamble, 'canceled');

                $maneuver->handleEvent($resolve);
                $duelEnd = new EventDuelEnd();
                $duelEnd->theah = $world->theah;
                $maneuver->handleEvent($duelEnd);
                Assert::false($maneuver->CancelAdversaryGamble, 'duel ended');
            },
        ];
    }
}
