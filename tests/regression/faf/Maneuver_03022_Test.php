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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03022_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03022';
    }

    /**
     * @return array{0:_03022,1:Maneuver_03022,2:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = ['Zealot']): array
    {
        $risk = $world->placeCard(new _03022(), Game::LOCATION_HAND, 1);
        $participant = $world->placeCharacter(new GenericCharacter('Participant', $traits), Game::LOCATION_CITY_DOCKS, 1);
        // WHY: getDuelOpponentId ignores its argument and returns duelOpponent.
        // Resolve treats that id as the participant who might be destroyed.
        $world->theah->duelOpponent = $participant;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_03022 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $participant];
    }

    private function resolved(TestWorld $world, Maneuver_03022 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->theah = $world->theah;
        $event->maneuverId = $maneuver->Id;
        $event->adversaryId = 1;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a maneuver on a wealth-1 Eisen risk' => function () {
                $risk = new _03022();
                Assert::instanceOf(Maneuver::class, $risk->getManeuvers()[0], 'maneuver');
                Assert::same(1, $risk->WealthCost, 'wealth');
                Assert::same(4, $risk->Thrust, 'thrust');
                Assert::true($risk->hasTrait('Faith'), 'Faith');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
            },

            // WHY: Final Strike draws only when the destroyed participant is a Zealot
            // or Hunter, and only while the duel global is still set. A new round
            // forgets the participant, so a later destruction does not fire.
            'destruction during the duel opens the En Garde pick and may draw' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $participant] = $this->scene($world);
                $this->resolved($world, $maneuver);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->theah = $world->theah;
                $destroyed->characterId = $participant->Id;
                $maneuver->handleEvent($destroyed);
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'zealot draws');
                Assert::same('03022', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'risk');

                $world = new TestWorld();
                [, $maneuver, $participant] = $this->scene($world, ['Hero', 'Pirate']);
                $this->resolved($world, $maneuver);
                $destroyed->theah = $world->theah;
                $destroyed->characterId = $participant->Id;
                $maneuver->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no zeal or hunt');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'picker still opens');

                $world->game->globals->set(Game::IN_DUEL, false);
                $world->theah->takeQueuedEvents();
                $maneuver->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedEvents, 'duel already over');

                $world->game->globals->set(Game::IN_DUEL, true);
                $fresh = new EventDuelNewRound();
                $fresh->theah = $world->theah;
                $maneuver->handleEvent($fresh);
                $maneuver->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedEvents, 'new round forgot the participant');
            },

            // WHY: En Garde clears Engaged. An engaged character is queued. An
            // unengaged character is already En Garded and is not queued. The
            // location still blocks pass while anyone remains.
            'the pick En Gardes an engaged character and refuses to pass while anyone remains' => function () {
                $world = new TestWorld();
                [, $maneuver, $participant] = $this->scene($world);
                $this->resolved($world, $maneuver);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_END_OF_ROUND_03022, 'x');
                Assert::same([$participant->Id, $bystander->Id], $args['characterIds'], 'everyone at the location');

                $threw = false;
                try {
                    $maneuver->actFromManeuverPass($world->game, States::DUEL_END_OF_ROUND_03022);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must choose');

                $participant->Engaged = true;
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_END_OF_ROUND_03022, 'x', $participant->Id);
                Assert::same($participant->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'engaged character is En Garded');

                $world->theah->takeQueuedEvents();
                $bystander->Engaged = false;
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_END_OF_ROUND_03022, 'x', $bystander->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'unengaged is treated as already En Garded');
            },

            'pass is legal when the location is empty' => function () {
                $world = new TestWorld();
                [, $maneuver, $participant] = $this->scene($world);
                $this->resolved($world, $maneuver);
                $participant->Location = Game::LOCATION_CITY_FORUM;
                $maneuver->actFromManeuverPass($world->game, States::DUEL_END_OF_ROUND_03022);
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },
        ];
    }
}
