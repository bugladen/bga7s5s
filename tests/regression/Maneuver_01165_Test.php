<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04054b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Maneuver_01165_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01165';
    }

    private function sabreOnAdversary(TestWorld $world): array
    {
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 2);
        $sabre->AttachedToId = $foe->Id;
        $foe->Attachments[] = $sabre->Id;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        return [$actor, $foe, $sabre];
    }

    public function tests(): array
    {
        return [
            // WHY: Technique_04054b gates isAvailableToPlayer on actor==owner. Adversary
            // sabre fails that while Trick's player is actor — must still list both techs.
            'available and lists both Sabre techniques despite actor-gate on Engage' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01165(), Game::LOCATION_HAND, 1);
                [, , $sabre] = $this->sabreOnAdversary($world);

                /** @var Maneuver_01165 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01165, 'duelResolveManeuver_01165');
                Assert::count(2, $args['techniques'], 'both sabre techniques');

                $ids = array_column($args['techniques'], 'id');
                foreach ($sabre->getTechniques() as $technique)
                {
                    Assert::true(in_array($technique->Id, $ids, true), $technique->Id);
                }

                foreach ($args['techniques'] as $props)
                {
                    Assert::true(isset($props['id'], $props['name'], $props['Id'], $props['Name']), 'dual-case keys');
                    Assert::same($props['id'], $props['Id'], 'Id mirrors id');
                    Assert::same($props['name'], $props['Name'], 'Name mirrors name');
                    Assert::true($props['name'] !== '', 'label not empty');
                }
            },

            // WHY: copied Engage technique must not engage the Character host (effects only)
            'copied Engage Riposte does not queue CardEngaged' => function () {
                $world = new TestWorld();
                [, , $sabre] = $this->sabreOnAdversary($world);

                /** @var Technique_04054b $engage */
                $engage = null;
                foreach ($sabre->getTechniques() as $technique)
                {
                    if ($technique instanceof Technique_04054b)
                    {
                        $engage = $technique;
                        break;
                    }
                }
                Assert::true($engage !== null, 'found 04054b');

                $event = new EventResolveTechnique();
                $event->techniqueId = $engage->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $engage->handleEvent($event);
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'normal engage cost');

                $world->theah->takeQueuedEvents();
                $engage->IsTemporaryCopy = true;
                $engage->handleEvent($event);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'copy skips engage');
            },
        ];
    }
}
