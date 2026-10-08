<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01065;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01065_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01065';
    }

    /** @return array{0:_01065,1:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card,2:GenericCharacter,3:GenericCharacter,4:Reaction_01065} */
    private function armed(TestWorld $world): array
    {
        $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
        $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        $weapon->Engaged = false;
        $henri->Attachments[] = $weapon->Id;
        $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_01065 $reaction */
        $reaction = $henri->getReactions()[0];
        return [$henri, $weapon, $defender, $bystander, $reaction];
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    private function intervened(TestWorld $world, int $newTargetId): EventCharacterIntervened
    {
        $event = new EventCharacterIntervened();
        $event->newTargetId = $newTargetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function blocked(callable $fn): bool
    {
        try {
            $fn();
        } catch (UserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'offers when Henri challenges with ready Weapon and another enemy is present' => function () {
                $world = new TestWorld();
                [$henri, , $defender, , $reaction] = $this->armed($world);

                $reaction->handleEvent($this->issued($world, $henri->Id, $defender->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($henri->Id, $transitions[0]->sourceId, 'source Henri');
            },

            'does not offer when the defender is the only enemy at the location' => function () {
                $world = new TestWorld();
                [$henri, , $defender, $bystander, $reaction] = $this->armed($world);
                $bystander->Location = Game::LOCATION_CITY_FORUM;

                $reaction->handleEvent($this->issued($world, $henri->Id, $defender->Id));

                Assert::count(0, $world->theah->queuedEvents, 'nobody to prevent');
            },

            'does not offer when another character issues the challenge' => function () {
                $world = new TestWorld();
                [, , $defender, , $reaction] = $this->armed($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->issued($world, $other->Id, $defender->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not Henri');
            },

            'does not offer without an unengaged Weapon' => function () {
                $world = new TestWorld();
                [$henri, $weapon, $defender, , $reaction] = $this->armed($world);
                $weapon->Engaged = true;

                $reaction->handleEvent($this->issued($world, $henri->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'engaged Weapon');

                $henri->Attachments = [];
                $weapon->Engaged = false;
                $reaction->handleEvent($this->issued($world, $henri->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no Weapon');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [$henri, , $defender, , $reaction] = $this->armed($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->issued($world, $henri->Id, $defender->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons target enemies other than the challenged defender plus Decline' => function () {
                $world = new TestWorld();
                [, $weapon, $defender, $bystander, $reaction] = $this->armed($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $defender->Id);

                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_map(fn($b) => $b['reaction'], $buttons);

                Assert::true(in_array("prevent-{$bystander->Id}-weapon-{$weapon->Id}", $ids, true), 'bystander');
                Assert::false(in_array("prevent-{$defender->Id}-weapon-{$weapon->Id}", $ids, true), 'defender excluded');
                Assert::true(in_array('decline', $ids, true), 'decline');
            },

            'prevent choice engages the Weapon and marks Used' => function () {
                $world = new TestWorld();
                [$henri, $weapon, , $bystander, $reaction] = $this->armed($world);

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "prevent-{$bystander->Id}-weapon-{$weapon->Id}"
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($weapon->Id, $engages[0]->cardId, 'Weapon engaged');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: the stored preventedCharacterId is what blocks the later intervene.
            'prevented character cannot intervene against Henri challenge' => function () {
                $world = new TestWorld();
                [$henri, $weapon, , $bystander, $reaction] = $this->armed($world);
                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "prevent-{$bystander->Id}-weapon-{$weapon->Id}"
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $henri->Id);

                $event = $this->intervened($world, $bystander->Id);
                Assert::true($this->blocked(fn() => $reaction->eventCheck($event)), 'blocked');
            },

            'other characters may still intervene' => function () {
                $world = new TestWorld();
                [$henri, $weapon, $defender, $bystander, $reaction] = $this->armed($world);
                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "prevent-{$bystander->Id}-weapon-{$weapon->Id}"
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $henri->Id);

                $event = $this->intervened($world, $defender->Id);
                Assert::false($this->blocked(fn() => $reaction->eventCheck($event)), 'not prevented');
            },

            'prevention only applies when Henri is the challenger' => function () {
                $world = new TestWorld();
                [, $weapon, , $bystander, $reaction] = $this->armed($world);
                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "prevent-{$bystander->Id}-weapon-{$weapon->Id}"
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $bystander->Id + 500);

                $event = $this->intervened($world, $bystander->Id);
                Assert::false($this->blocked(fn() => $reaction->eventCheck($event)), 'different challenger');
            },

            'prevention is forgotten at end of day' => function () {
                $world = new TestWorld();
                [$henri, $weapon, , $bystander, $reaction] = $this->armed($world);
                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "prevent-{$bystander->Id}-weapon-{$weapon->Id}"
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $henri->Id);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                $event = $this->intervened($world, $bystander->Id);
                Assert::false($this->blocked(fn() => $reaction->eventCheck($event)), 'cleared');
            },

            'decline engages nothing and does not mark Used' => function () {
                $world = new TestWorld();
                [, , , , $reaction] = $this->armed($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
