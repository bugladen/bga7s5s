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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04024;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04024_Test extends TestCase
{
    public function name(): string
    {
        return '_04024 Diplomatic Envoy';
    }

    public function tests(): array
    {
        return [
            'is a bureaucracy welcome scheme' => function () {
                $card = new _04024();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(16, $card->Initiative, 'initiative');
                Assert::same(-1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Welcome'), 'Welcome');
            },

            // WHY: Forum renown is mandatory. The Diplomat move is contingent, and Move
            // does not engage. Combat challenges against an en garde Forum Diplomat are
            // illegal while this scheme sits at Home. eventCheck does not walk cards.
            'resolve adds forum renown and moves a diplomat without engaging' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04024(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->location, 'forum');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no diplomat');

                $diplomat = $world->placeCharacter(new GenericCharacter('Envoy', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $already = $world->placeCharacter(new GenericCharacter('There', ['Diplomat']), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04024', $transition->transition, 'pick');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'after renown');
                $ids = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04024, '', '')['ids'];
                Assert::same([$diplomat->Id], $ids, 'forum diplomat is skipped');

                $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04024, '', '', $diplomat->Id);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'to forum');
                Assert::false($move->engage, 'move only');
                Assert::same('diplomatChosen', end($world->game->gamestate->transitions), 'chosen');

                $threw = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04024, '', '', $already->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'already there');

                $challenge = new EventChallengeIssued();
                $challenge->defenderId = $diplomat->Id;
                $challenge->theah = $world->theah;
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $diplomat->Location = Game::LOCATION_CITY_FORUM;
                $blocked = false;
                try {
                    $scheme->eventCheck($challenge);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'combat challenge');

                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);
                $scheme->eventCheck($challenge);
                Assert::true(true, 'finesse remains legal');

                $diplomat->Engaged = true;
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $scheme->eventCheck($challenge);
                $scheme->Location = Game::LOCATION_HAND;
                $diplomat->Engaged = false;
                $scheme->eventCheck($challenge);
            },
        ];
    }
}
