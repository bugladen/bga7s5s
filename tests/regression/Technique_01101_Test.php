<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01101;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01101;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_01101_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01101';
    }

    /**
     * Host wears Gallegos Blade; duel is live.
     *
     * @return array{0:_01101,1:GenericCharacter,2:GenericCharacter,3:Technique_01101}
     */
    private function duel(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $blade = $world->placeCard(new _01101(), Game::LOCATION_CITY_DOCKS, 1);
        $blade->AttachedToId = $host->Id;
        $host->Attachments[] = $blade->Id;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01101 $technique */
        $technique = $blade->getTechniques()[0];
        return [$blade, $host, $foe, $technique];
    }

    private function activated(Technique_01101 $technique): bool
    {
        $prop = new \ReflectionProperty(Technique_01101::class, 'IsActivated');
        $prop->setAccessible(true);
        return (bool)$prop->getValue($technique);
    }

    private function resolve(TestWorld $world, Technique_01101 $technique): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available in a duel' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            // WHY: Technique::isAvailableToPlayer does not gate on controller — the duel UI
            // only offers techniques from the acting participant's cards. Pin current base behaviour.
            'in-duel availability is not gated by playerId at the Technique layer' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(2, $world->theah), 'parent returns true for any player');
            },

            'resolve activates the technique' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                $this->resolve($world, $technique);
                Assert::true($this->activated($technique), 'activated');
            },

            'resolve for another technique id does not activate' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                $event = new EventResolveTechnique();
                $event->techniqueId = 'someOtherTechnique';
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::false($this->activated($technique), 'not activated');
            },

            // WHY: Technique text is "-1 Parry" on calculate for this technique id only.
            'calculate subtracts 1 Parry for this technique' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);

                $mine = new EventDuelCalculateTechniqueValues();
                $mine->techniqueId = $technique->Id;
                $mine->parry = 3;
                $mine->thrust = 2;
                $mine->theah = $world->theah;
                $technique->handleEvent($mine);
                Assert::same(2, $mine->parry, '-1 Parry');
                Assert::same(2, $mine->thrust, 'Thrust untouched');
                Assert::count(1, $mine->explanations, 'explained');

                $other = new EventDuelCalculateTechniqueValues();
                $other->techniqueId = 'other';
                $other->parry = 3;
                $other->theah = $world->theah;
                $technique->handleEvent($other);
                Assert::same(3, $other->parry, 'other technique untouched');
            },

            // WHY (Technique_01101 comment): -1 gamble reveal is adversary-only so same-round
            // host Gamble does not cancel the blade's passive +1.
            'activated technique lowers only the adversary gamble reveal' => function () {
                $world = new TestWorld();
                [$blade, $host, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                [$foeReveal, $foeWhy] = $world->theah->getNumberOfGambleCardsToReveal($foe);
                // base 2, blade passive does not apply to foe, technique -1 → 1
                Assert::same(1, $foeReveal, 'adversary -1');
                Assert::contains($blade->getInjectCode(), $foeWhy, 'explained by blade');

                [$hostReveal] = $world->theah->getNumberOfGambleCardsToReveal($host);
                // base 2 + blade passive +1; technique must NOT subtract for host → 3
                Assert::same(3, $hostReveal, 'host keeps +1 passive, no technique -1');
            },

            'inactive technique does not change adversary reveal' => function () {
                $world = new TestWorld();
                [, , $foe] = $this->duel($world);
                [$foeReveal] = $world->theah->getNumberOfGambleCardsToReveal($foe);
                Assert::same(2, $foeReveal, 'base');
            },

            // WHY: clears after the adversary's round ends (actor is not the owning character).
            'adversary end of round deactivates' => function () {
                $world = new TestWorld();
                [, $host, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $event = new EventDuelEndOfRound();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($this->activated($technique), 'cleared after adversary round');
            },

            'owning character end of round keeps the technique activated' => function () {
                $world = new TestWorld();
                [, $host, , $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $event = new EventDuelEndOfRound();
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($this->activated($technique), 'still active through host round');
            },

            'technique cancel clears activation' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                Assert::false($this->activated($technique), 'canceled');
            },

            'duel end clears activation' => function () {
                $world = new TestWorld();
                [, , , $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);

                Assert::false($this->activated($technique), 'duel end');
            },

            'unequipping the blade clears activation' => function () {
                $world = new TestWorld();
                [$blade, , , $technique] = $this->duel($world);
                $this->resolve($world, $technique);

                $unequip = new EventAttachmentUnequipped();
                $unequip->attachmentId = $blade->Id;
                $unequip->theah = $world->theah;
                $technique->handleEvent($unequip);

                Assert::false($this->activated($technique), 'unequipped');
            },
        ];
    }
}
