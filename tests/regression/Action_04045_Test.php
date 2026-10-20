<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04045_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04045';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde Duelist with an opposing character. The type stays off
            // auto-engage. Accepted threat is +1 only for this scheme's challenger.
            // Renown is only when that duel destroys the adversary, and the scheme is at Home.
            'issues an unrefusable combat challenge and pays renown if the adversary dies' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04045(), Game::LOCATION_PLAYER_HOME, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04045 $action */
                $action = $scheme->getActions()[0];
                $performers = $action->getPerformersForAction(1, $world->theah);
                $ids = array_map(fn($character) => $character->Id, $performers);
                Assert::same([$duelist->Id], array_values($ids), 'duelist only');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');

                $duelist->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $duelist->Engaged = false;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'opposing');
                [$own] = $action->isValidTargetForAbility($world->game, $hero);
                Assert::false($own, 'not your own');

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($scheme, $triggered);
                Assert::same(Game::STAND_YOUR_GROUND_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same('04045', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');

                $world->theah->duelActor = $duelist;
                $world->theah->duelOpponent = $foe;
                $threat = new EventGenerateChallengeThreat();
                $threat->actorId = $duelist->Id;
                $threat->actorThreat = 0;
                $world->fireOn($scheme, $threat);
                Assert::same(1, $threat->actorThreat, 'accepted threat');

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $threat->actorThreat = 0;
                $world->fireOn($scheme, $threat);
                Assert::same(0, $threat->actorThreat, 'another challenge type');

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::STAND_YOUR_GROUND_CHALLENGE_TYPE);
                $world->game->globals->set(Game::IN_DUEL, true);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $world->fireOn($scheme, $destroyed);
                Assert::same(1, $world->theah->queuedOfType(EventPlayerGainsReknown::class)[0]->playerId, 'adversary destroyed');

                $world->theah->takeQueuedEvents();
                $destroyed->characterId = $duelist->Id;
                $world->fireOn($scheme, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'challenger dying does not pay the mirror');

                $scheme->Location = Game::LOCATION_HAND;
                $destroyed->characterId = $foe->Id;
                $world->fireOn($scheme, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'scheme left home');

                $scheme->Location = Game::LOCATION_PLAYER_HOME;
                $duelist->Engaged = true;
                $blocked = false;
                try {
                    $world->fireOn($scheme, $triggered);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'engaged performer cannot issue');
            },
        ];
    }
}
