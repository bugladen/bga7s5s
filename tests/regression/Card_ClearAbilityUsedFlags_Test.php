<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01109;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01131;

class Card_ClearAbilityUsedFlags_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_ClearAbilityUsedFlags';
    }

    public function tests(): array
    {
        return [
            // WHY: Played Risk Action left Used=true; mid-day discard recycle must
            // clear it so a redrawn copy is usable before dusk.
            'clears used Action on Iron and Velvet' => function () {
                $world = new TestWorld();
                /** @var _01131 $risk */
                $risk = $world->placeCard(new _01131(), $world->game->getPlayerDiscardDeckName(1), 1);
                $action = $risk->getActions()[0];
                $action->Used = true;

                Assert::true($risk->clearAbilityUsedFlags(), 'reported clear');
                Assert::false($action->Used, 'Action Used cleared');
            },

            'clears used Reaction on Night of Drinking' => function () {
                $world = new TestWorld();
                /** @var _01109 $risk */
                $risk = $world->placeCard(new _01109(), $world->game->getPlayerDiscardDeckName(1), 1);
                $reaction = $risk->getReactions()[0];
                $reaction->Used = true;

                Assert::true($risk->clearAbilityUsedFlags(), 'reported clear');
                Assert::false($reaction->Used, 'Reaction Used cleared');
            },

            'clears used Maneuver without touching unused Action' => function () {
                $world = new TestWorld();
                /** @var _01131 $risk */
                $risk = $world->placeCard(new _01131(), Game::LOCATION_HAND, 1);
                $action = $risk->getActions()[0];
                $maneuver = $risk->getManeuvers()[0];
                $action->Used = false;
                $maneuver->Used = true;

                Assert::true($risk->clearAbilityUsedFlags(), 'reported clear');
                Assert::false($maneuver->Used, 'Maneuver Used cleared');
                Assert::false($action->Used, 'Action stayed unused');
            },

            'returns false when nothing was used' => function () {
                $world = new TestWorld();
                /** @var _01109 $risk */
                $risk = $world->placeCard(new _01109(), Game::LOCATION_HAND, 1);

                Assert::false($risk->clearAbilityUsedFlags(), 'no-op');
            },
        ];
    }
}
