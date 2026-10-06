<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionMethod;

class Reaction_01008_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01008';
    }

    private function isCopyable(Reaction_01008 $reaction, $ability): bool
    {
        $method = new ReflectionMethod(Reaction_01008::class, 'isCopyable');
        $method->setAccessible(true);
        return (bool)$method->invoke($reaction, $ability);
    }

    public function tests(): array
    {
        return [
            'isCopyable allow-list includes Cesca own action and Sibella' => function () {
                $reaction = new Reaction_01008();
                Assert::true($this->isCopyable($reaction, new Action_01008()), 'Action_01008 copyable');
                Assert::true($this->isCopyable($reaction, new Action_01012()), 'Action_01012 copyable');
            },

            // WHY: card text "This ability cannot be copied" — opening the window
            // would wound Cesca for no effect.
            'isCopyable excludes Fate\'s Burden and Boon' => function () {
                $reaction = new Reaction_01008();
                Assert::false($this->isCopyable($reaction, new Action_01025()), 'Fate\'s Burden not copyable');
                Assert::false($this->isCopyable($reaction, new Action_01161()), 'Boon not copyable');
            },

            'triggers when Cesca is the sorcerer ability source' => function () {
                $world = new TestWorld();
                $cesca = $world->placeCharacter(new _01008(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_01008 $reaction */
                $reaction = $cesca->getReactions()[0];
                /** @var Action_01008 $action */
                $action = $cesca->getActions()[0];

                $event = new EventSorcererAbilityPlayed();
                $event->sourceId = $cesca->Id;
                $event->abilityId = $action->Id;
                $event->performerId = $cesca->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'copy window opened');
                Assert::same($cesca->Id, $reaction->sourceId, 'source stored');
                Assert::same($action->Id, $reaction->sourceAbilityId, 'ability stored');
            },

            'trigger paths gate on isCopyable so uncopyable abilities never open the window' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/_7s5s/reactions/Reaction_01008.php');
                Assert::contains('&& $this->isCopyable($ability)', $src, 'SorcererAbilityPlayed gated');
                Assert::contains('Action_01025', $src, 'Fate\'s Burden excluded in comments/list');
                Assert::contains('Action_01161', $src, 'Boon excluded in comments');
                // Both EventSorcererAbilityPlayed and EventCharacterTargeted gates
                Assert::same(2, substr_count($src, 'isCopyable($ability)'), 'both trigger sites gated');
            },

            'isCopyable allow-list classes appear in performReaction' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/_7s5s/reactions/Reaction_01008.php');
                // Match from private function through its closing brace before announceReaction
                $start = strpos($src, 'private function isCopyable');
                $end = strpos($src, 'private function announceReaction', $start);
                Assert::true($start !== false && $end !== false, 'isCopyable / announceReaction found');
                $body = substr($src, $start, $end - $start);
                preg_match_all('/instanceof (Action_\d+|Reaction_\d+|Maneuver_\d+)/', $body, $classes);
                $unique = array_unique($classes[1]);
                Assert::true(count($unique) >= 10, 'allow-list populated');
                foreach ($unique as $class) {
                    $occurrences = substr_count($src, $class);
                    Assert::true($occurrences >= 2, "{$class} in allow-list and performReaction");
                }
            },
        ];
    }
}
