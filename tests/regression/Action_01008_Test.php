<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01008;

class Action_01008_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01008';
    }

    public function tests(): array
    {
        return [
            'available in city when owner is Sorcerer' => function () {
                $world = new TestWorld();
                $cesca = $world->placeCharacter(new _01008(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01008 $action */
                $action = $cesca->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available in city');
            },

            'unavailable at Player Home' => function () {
                $world = new TestWorld();
                $cesca = $world->placeCharacter(new _01008(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01008 $action */
                $action = $cesca->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'city action only');
            },

            'source requires Sorcerer Ability Start/Played events on all resolve paths' => function () {
                // WHY: Cesca audit — Sorcery draw + Pass paths used to skip sorcerer events,
                // so Reaction_01008 never saw 2 of 3 outcomes.
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/_7s5s/actions/Action_01008.php');
                Assert::contains('createSorcererAbilityStartEvent', $src, 'Start event present');
                Assert::contains('createSorcererAbilityPlayedEvent', $src, 'Played event present');
                Assert::contains('createActionResolvedEvent', $src, 'ActionResolved present');
                Assert::contains('actFromActionPass', $src, 'pass path implemented');

                // Count Played events: Sorcery branch, sink branch, pass branch = 3
                Assert::same(
                    3,
                    substr_count($src, 'createSorcererAbilityPlayedEvent'),
                    'Played fired on all three resolve paths'
                );
            },
        ];
    }
}
