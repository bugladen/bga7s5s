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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03035_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03035';
    }

    /**
     * @return array{0:Maneuver_03035,1:GenericCharacter,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03035(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Maneuver_03035 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$maneuver, $actor, $ally];
    }

    public function tests(): array
    {
        return [
            // WHY: The character prompt and the Riposte/Thrust choice are stacked ahead of
            // calculate. Default (before a choice) is +2 Thrust. Choosing Riposte is +1 Riposte.
            // Cancel puts the default back.
            'wounding another character at the location chooses Riposte or Thrust' => function () {
                $world = new TestWorld();
                [$maneuver, $actor, $ally] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'ally present');
                [$self] = $maneuver->isValidTargetForAbility($world->game, $actor);
                Assert::false($self, 'not the participant');
                [$ok] = $maneuver->isValidTargetForAbility($world->game, $ally);
                Assert::true($ok, 'own other character');

                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $activated->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($activated);
                Assert::same('03035', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'stacked prompt');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->thrust, 'default thrust');
                Assert::same(0, $calc->riposte, 'no riposte yet');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03035, 'x', $ally->Id);
                Assert::same('03035_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice stacked ahead of the first prompt');
                Assert::same([null], array_slice($world->game->gamestate->transitions, -1), 'bare next state');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03035_2, 'x', 1);
                Assert::same($ally->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'ally wounded');

                $chosen = new EventDuelCalculateManeuverValues();
                $chosen->theah = $world->theah;
                $chosen->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($chosen);
                Assert::same(1, $chosen->riposte, 'riposte chosen');
                Assert::same(0, $chosen->thrust, 'not thrust');

                $cancel = new EventManeuverCanceled();
                $cancel->theah = $world->theah;
                $cancel->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($cancel);
                $after = new EventDuelCalculateManeuverValues();
                $after->theah = $world->theah;
                $after->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($after);
                Assert::same(2, $after->thrust, 'cancel restores the thrust default');
            },

            'no other character, and an invalid choice, do not wound' => function () {
                $world = new TestWorld();
                [$maneuver, , $ally] = $this->scene($world);
                $ally->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'alone');

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03035_2, 'x', 9);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid choice');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },
        ];
    }
}
