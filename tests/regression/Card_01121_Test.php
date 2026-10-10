<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01121;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;

class Card_01121_Test extends TestCase
{
    public function name(): string
    {
        return '_01121 Ren';
    }

    /**
     * Ren (p1) vs Actor (p2). Equal/more characters for the actor side so the Parry gate opens.
     *
     * @return array{0:_01121,1:GenericCharacter}
     */
    private function duel(TestWorld $world, int $round = 1): array
    {
        $ren = $world->placeCharacter(new _01121(), Game::LOCATION_CITY_DOCKS, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 2);
        // WHY: adversaryCount >= characterCount — actor side needs equal or more in play.
        $world->placeCharacter(new GenericCharacter('Actor Buddy'), Game::LOCATION_CITY_FORUM, 2);
        $world->game->globals->set(Game::DUEL_ROUND, $round);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $ren;
        return [$ren, $actor];
    }

    private function combat(TestWorld $world, int $actorId, int $adversaryId, int $parry): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->actorId = $actorId;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        if ($parry > 0) {
            $event->addParry($parry);
        }
        return $event;
    }

    private function maneuver(TestWorld $world, int $actorId, int $adversaryId, int $parry): EventDuelCalculateManeuverValues
    {
        $event = new EventDuelCalculateManeuverValues();
        $event->actorId = $actorId;
        $event->adversaryId = $adversaryId;
        $event->parry = $parry;
        $event->theah = $world->theah;
        return $event;
    }

    private function technique(TestWorld $world, int $actorId, int $adversaryId, int $parry): EventDuelCalculateTechniqueValues
    {
        $event = new EventDuelCalculateTechniqueValues();
        $event->actorId = $actorId;
        $event->adversaryId = $adversaryId;
        $event->parry = $parry;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Hero Academic Duelist Shenzhou' => function () {
                $ren = new _01121();
                Assert::instanceOf(Character::class, $ren, 'Character');
                Assert::same(4, $ren->Resolve, 'Resolve');
                Assert::same(3, $ren->Combat, 'Combat');
                Assert::same(3, $ren->Finesse, 'Finesse');
                Assert::same(0, $ren->Influence, 'Influence');
                Assert::true($ren->hasFaction('Ussura'), 'Ussura');
                Assert::true($ren->hasTrait('Hero'), 'Hero');
                Assert::true($ren->hasTrait('Academic'), 'Academic');
                Assert::true($ren->hasTrait('Duelist'), 'Duelist');
                Assert::true($ren->hasTrait('Shenzhou'), 'Shenzhou');
                Assert::same(0, $ren->ParryReductionAppliedInRound, 'flag clear');
            },

            // WHY (journal 2026-10-07-10): apply via EventHub static helpers, not card handleEvent —
            // Maneuver/Technique Parry is written during the card loop by the ability owner.
            'combat card with Parry is reduced by 1 and sets the round flag' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world);

                $event = $this->combat($world, $actor->Id, $ren->Id, 2);
                _01121::applyAdversaryParryReductionToCombatCard($event);

                Assert::same(1, $event->parry, '-1');
                Assert::count(1, $event->explanations, 'explained');
                Assert::same(1, $ren->ParryReductionAppliedInRound, 'flag = round 1');
            },

            // WHY: Ren skips when combat Parry <= 0 so the once-per-round flag stays
            // clear; a later +Parry Maneuver/Technique in the same round still gets -1.
            // (removeParry itself may store negative debt for Hand-style penalties — Ren
            // deliberately does not call it on a 0-Parry combat card.)
            '0-Parry combat card does not set the round flag' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world);

                $event = $this->combat($world, $actor->Id, $ren->Id, 0);
                _01121::applyAdversaryParryReductionToCombatCard($event);

                Assert::same(0, $event->parry, 'still 0');
                Assert::same(0, $ren->ParryReductionAppliedInRound, 'flag unset');
                Assert::same([], $event->explanations, 'no fake explanation');
            },

            'after 0-Parry combat, a +Parry Maneuver in the same round still gets -1' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world);

                _01121::applyAdversaryParryReductionToCombatCard(
                    $this->combat($world, $actor->Id, $ren->Id, 0)
                );
                Assert::same(0, $ren->ParryReductionAppliedInRound, 'combat left flag clear');

                $maneuver = $this->maneuver($world, $actor->Id, $ren->Id, 1);
                _01121::applyAdversaryParryReductionToManeuver($maneuver);

                Assert::same(0, $maneuver->parry, 'Maneuver Parry reduced');
                Assert::same(1, $ren->ParryReductionAppliedInRound, 'flag now set');
            },

            'Technique Parry is reduced once per round via the same flag' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world);

                $first = $this->technique($world, $actor->Id, $ren->Id, 2);
                _01121::applyAdversaryParryReductionToTechnique($first);
                Assert::same(1, $first->parry, 'first -1');

                $second = $this->technique($world, $actor->Id, $ren->Id, 2);
                _01121::applyAdversaryParryReductionToTechnique($second);
                Assert::same(2, $second->parry, 'second untouched');
            },

            'does not reduce when the actor controls fewer characters than Ren\'s side' => function () {
                $world = new TestWorld();
                $ren = $world->placeCharacter(new _01121(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Ren Ally'), Game::LOCATION_CITY_FORUM, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Lone Actor'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::DUEL_ROUND, 1);

                $event = $this->combat($world, $actor->Id, $ren->Id, 2);
                _01121::applyAdversaryParryReductionToCombatCard($event);

                Assert::same(2, $event->parry, 'gate closed');
                Assert::same(0, $ren->ParryReductionAppliedInRound, 'flag clear');
            },

            'does not reduce when Ren is blanked' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world);
                $ren->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = $this->combat($world, $actor->Id, $ren->Id, 2);
                _01121::applyAdversaryParryReductionToCombatCard($event);

                Assert::same(2, $event->parry, 'blanked');
            },

            // WHY: flag cleared on EventDuelEnd so the next duel starts fresh.
            'EventDuelEnd clears the round flag' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world);
                _01121::applyAdversaryParryReductionToCombatCard(
                    $this->combat($world, $actor->Id, $ren->Id, 2)
                );
                Assert::same(1, $ren->ParryReductionAppliedInRound, 'set');

                $end = new EventDuelEnd();
                $world->fireOn($ren, $end);

                Assert::same(0, $ren->ParryReductionAppliedInRound, 'cleared');
            },

            'a new duel round can reduce again after the previous round applied' => function () {
                $world = new TestWorld();
                [$ren, $actor] = $this->duel($world, 1);
                _01121::applyAdversaryParryReductionToManeuver(
                    $this->maneuver($world, $actor->Id, $ren->Id, 1)
                );
                Assert::same(1, $ren->ParryReductionAppliedInRound, 'round 1');

                $world->game->globals->set(Game::DUEL_ROUND, 2);
                $next = $this->maneuver($world, $actor->Id, $ren->Id, 1);
                _01121::applyAdversaryParryReductionToManeuver($next);

                Assert::same(0, $next->parry, 'round 2 reduces');
                Assert::same(2, $ren->ParryReductionAppliedInRound, 'flag = round 2');
            },
        ];
    }
}
